<?php

namespace App\Services;

use App\Enums\WhatsappMessageDirection;
use App\Enums\WhatsappMessageStatus;
use App\Models\Professional;
use App\Models\WhatsappMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    private function getConfig(): array
    {
        return [
            'api_url' => config('services.whatsapp.api_url', 'https://graph.facebook.com/v19.0'),
            'phone_number_id' => config('services.whatsapp.phone_number_id', ''),
            'access_token' => config('services.whatsapp.access_token', ''),
            'verify_token' => config('services.whatsapp.verify_token', 'dental-commissions-verify'),
        ];
    }

    public function verifyWebhook(string $mode, string $token): bool
    {
        return $mode === 'subscribe' && $token === $this->getConfig()['verify_token'];
    }

    public function processIncomingMessage(array $payload): ?WhatsappMessage
    {
        try {
            $message = $payload['messages'][0] ?? null;
            if (!$message) {
                return null;
            }

            $fromPhone = $message['from'] ?? '';
            $body = $message['text']['body'] ?? '';
            $messageSid = $message['id'] ?? null;
            $contextId = $message['context']['id'] ?? null;

            $existing = WhatsappMessage::where('message_sid', $messageSid)->first();
            if ($existing) {
                return $existing;
            }

            $professional = WhatsappMessage::findByPhone($fromPhone);

            $toPhone = $this->getConfig()['phone_number_id'];

            $whatsappMessage = WhatsappMessage::create([
                'professional_id' => $professional?->id,
                'direction' => WhatsappMessageDirection::Incoming,
                'status' => WhatsappMessageStatus::Received,
                'from_phone' => $fromPhone,
                'to_phone' => $toPhone,
                'message_body' => $body,
                'message_sid' => $messageSid,
            ]);

            if ($contextId) {
                $originalMessage = WhatsappMessage::where('message_sid', $contextId)->first();
                if ($originalMessage) {
                    $whatsappMessage->update(['related_message_id' => $originalMessage->id]);
                    $this->processReply($whatsappMessage, $originalMessage);
                }
            } elseif ($professional) {
                $this->processWithAI($whatsappMessage, $professional);
            } else {
                $this->sendMessage($fromPhone, 'No pudimos identificar tu numero. Contacta al administrador.');
                $whatsappMessage->markAsFailed('Profesional no identificado');
            }

            return $whatsappMessage;
        } catch (\Throwable $e) {
            Log::error('Error procesando mensaje WhatsApp', [
                'error' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
                'payload' => $payload,
            ]);
            return null;
        }
    }

    public function sendConfirmation(WhatsappMessage $message): void
    {
        $name = $message->professional?->name ?? '';
        $greeting = $name ? "Hola {$name}!" : 'Hola!';
        $body = "{$greeting} Hemos recibido tu mensaje. Pronto lo procesaremos.\n\n"
            . 'Responde *OK* para confirmar o *CORREGIR* [descripcion del cambio] si necesitas hacer ajustes.';

        $this->sendMessage($message->from_phone, $body);
    }

    public function processWithAI(WhatsappMessage $message, Professional $doctor): void
    {
        $aiService = app(AiParsingService::class);
        $creationService = app(ActivityCreationService::class);

        $parsedData = $aiService->parseMessage($message->message_body, $doctor);

        $activity = $creationService->create($parsedData, $doctor, $message);

        if ($activity) {
            $summary = $this->buildActivitySummary($activity, $parsedData);
            $this->sendMessage($message->from_phone, $summary);
            $message->markAsParsed($parsedData);
        } else {
            $this->sendMessage($message->from_phone, 'No pudimos procesar tu mensaje. El administrador lo revisara.');
            $message->markAsNeedsReview('Error al crear actividad desde IA');
        }
    }

    private function buildActivitySummary($activity, array $parsedData): string
    {
        $patientName = $activity->patient->full_name ?? $parsedData['patient_name'] ?? 'N/A';
        $procedureName = $activity->procedure->name ?? 'N/A';
        $date = \Carbon\Carbon::parse($parsedData['date'] ?? $activity->activity_date)->format('d/m/Y');
        $doctorCommission = number_format($activity->doctor_commission_amount ?? 0, 2);
        $assistantCount = count($parsedData['assistants'] ?? []);

        $summary = "*Actividad registrada:*\n";
        $summary .= "Paciente: {$patientName}\n";
        $summary .= "Procedimiento: {$procedureName}\n";
        $summary .= "Fecha: {$date}\n";
        $summary .= "Comision doctor: \${$doctorCommission}\n";

        if ($assistantCount > 0) {
            $summary .= "Auxiliares: " . implode(', ', $parsedData['assistants']) . "\n";
            $summary .= "Comision auxiliares: \$" . number_format($activity->assistant_commission_total ?? 0, 2) . "\n";
        }

        if ($parsedData['needs_review'] ?? false) {
            $summary .= "\n*Nota:* Este registro requiere revision del administrador.\n";
            $summary .= "Motivo: {$parsedData['review_notes']}\n";
        }

        $summary .= "\nResponde *OK* para confirmar o *CORREGIR* [cambio].";

        return $summary;
    }

    public function processReply(WhatsappMessage $reply, WhatsappMessage $original): void
    {
        $text = strtolower(trim($reply->message_body));

        if ($text === 'ok') {
            $original->markAsConfirmed();
            $reply->markAsConfirmed();
            $this->sendMessage($reply->from_phone, 'Confirmado! Tu registro ha sido guardado.');
        } elseif (str_starts_with($text, 'corregir')) {
            $notes = trim(substr($reply->message_body, 8));
            $original->markAsNeedsReview($notes ?: 'Solicitud de correccion sin detalles');
            $reply->markAsNeedsReview($notes);
            $this->sendMessage($reply->from_phone, 'Recibido. Tu registro ha sido enviado a revision. El administrador lo revisara.');
        } else {
            $this->sendMessage($reply->from_phone, 'No entendimos tu respuesta. Responde *OK* para confirmar o *CORREGIR* [cambio].');
        }
    }

    public function sendMessage(string $toPhone, string $body): bool
    {
        $cfg = $this->getConfig();

        if (empty($cfg['phone_number_id']) || empty($cfg['access_token'])) {
            Log::warning('WhatsApp no configurado. Mensaje no enviado.', [
                'to' => $toPhone,
                'body' => $body,
            ]);
            return false;
        }

        try {
            $response = Http::withToken($cfg['access_token'])
                ->post("{$cfg['api_url']}/{$cfg['phone_number_id']}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $toPhone,
                    'type' => 'text',
                    'text' => ['body' => $body],
                ]);

            if ($response->successful()) {
                $responseData = $response->json();
                $messageId = $responseData['messages'][0]['id'] ?? null;

                WhatsappMessage::create([
                    'professional_id' => WhatsappMessage::findByPhone($toPhone)?->id,
                    'direction' => WhatsappMessageDirection::Outgoing,
                    'status' => WhatsappMessageStatus::Sent,
                    'from_phone' => $cfg['phone_number_id'],
                    'to_phone' => $toPhone,
                    'message_body' => $body,
                    'message_sid' => $messageId,
                ]);

                return true;
            }

            Log::error('Error enviando mensaje WhatsApp', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('Excepcion enviando mensaje WhatsApp', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
