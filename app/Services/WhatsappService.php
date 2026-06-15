<?php

namespace App\Services;

use App\Enums\ProfessionalRole;
use App\Enums\WhatsappMessageDirection;
use App\Enums\WhatsappMessageStatus;
use App\Models\ActivityRecord;
use App\Models\DoctorAssistantAssignment;
use App\Models\Professional;
use App\Models\WhatsappMessage;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
            if (! $message) {
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

            $socialComment = app(SocialConversionService::class)->processIncomingMessage($whatsappMessage);

            if ($socialComment) {
                $whatsappMessage->markAsProcessed();

                if ($socialComment->converted_patient_id) {
                    $this->sendMessage($fromPhone, 'Gracias. Identificamos tu ficha y daremos seguimiento a tu solicitud.');
                } else {
                    $this->sendMessage($fromPhone, 'Gracias. Recibimos tu codigo y el equipo creara o validara tu ficha para continuar.');
                }

                return $whatsappMessage;
            }

            if ($contextId) {
                $contextMessage = WhatsappMessage::where('message_sid', $contextId)->first();
                $originalMessage = $contextMessage?->direction === WhatsappMessageDirection::Incoming
                    ? $contextMessage
                    : $this->findPendingOriginalForReply($whatsappMessage);

                if ($originalMessage) {
                    $whatsappMessage->update([
                        'related_message_id' => $originalMessage->id,
                    ]);
                    $this->processReply($whatsappMessage, $originalMessage);
                }
            } elseif ($professional) {
                $originalMessage = $this->findPendingOriginalForReply($whatsappMessage);

                if ($originalMessage) {
                    $whatsappMessage->update(['related_message_id' => $originalMessage->id]);
                    $this->processReply($whatsappMessage, $originalMessage);
                } else {
                    $this->processWithAI($whatsappMessage, $professional);
                }
            } else {
                $this->sendMessage($fromPhone, 'No pudimos identificar tu numero. Contacta al administrador.');
                $whatsappMessage->markAsFailed('Profesional no identificado');
            }

            return $whatsappMessage;
        } catch (\Throwable $e) {
            Log::error('Error procesando mensaje WhatsApp', [
                'error' => $e->getMessage(),
                'file' => $e->getFile().':'.$e->getLine(),
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
            .'Responde *OK* para confirmar o *CORREGIR* [descripcion del cambio] si necesitas hacer ajustes.';

        $this->sendMessage($message->from_phone, $body);
    }

    public function processWithAI(WhatsappMessage $message, Professional $sender): void
    {
        $doctor = $this->resolveDoctorForSender($sender, $message->message_body);

        if (! $doctor) {
            $error = $this->unresolvedDoctorMessage($sender);

            $message->markAsNeedsReview($error);
            $this->sendMessage($message->from_phone, $error);

            return;
        }

        $aiService = app(AiParsingService::class);
        $creationService = app(ActivityCreationService::class);

        $parsedData = $aiService->parseMessage($message->message_body, $doctor);
        $parsedData = $this->appendSenderAssistant($parsedData, $sender);

        $activity = $creationService->create($parsedData, $doctor, $message);

        if ($activity) {
            $summary = $this->buildActivitySummary($activity, $parsedData);
            $this->sendMessage($message->from_phone, $summary);
            $message->markAsParsed($parsedData);
        } else {
            $message->refresh();
            $error = $message->error_message ?: 'Error al crear actividad desde IA';

            if (str_contains(strtolower($error), 'falta metodo de pago')) {
                $this->sendMessage(
                    $message->from_phone,
                    'No pudimos registrar la actividad porque falta el metodo de pago. Envia nuevamente el mensaje indicando efectivo, transferencia, credito o debito.',
                );

                return;
            }

            $this->sendMessage($message->from_phone, 'No pudimos procesar tu mensaje. El administrador lo revisara.');

            if (! $message->error_message) {
                $message->markAsNeedsReview($error);
            }
        }
    }

    private function resolveDoctorForSender(Professional $sender, string $messageBody): ?Professional
    {
        if ($sender->role === ProfessionalRole::Doctor) {
            return $sender;
        }

        if ($sender->role !== ProfessionalRole::Assistant) {
            return null;
        }

        $assignedDoctors = $this->assignedDoctorsForAssistant($sender);

        if ($assignedDoctors->count() === 1) {
            return $assignedDoctors->first();
        }

        if ($assignedDoctors->isEmpty()) {
            return null;
        }

        $doctorName = $this->extractDoctorName($messageBody);

        if ($doctorName) {
            return $this->matchAssignedDoctorByName($assignedDoctors, $doctorName);
        }

        $doctorName = $this->extractDoctorNameFromStart($messageBody);

        return $doctorName ? $this->matchAssignedDoctorByName($assignedDoctors, $doctorName) : null;
    }

    private function assignedDoctorsForAssistant(Professional $assistant): Collection
    {
        return DoctorAssistantAssignment::query()
            ->with('doctor')
            ->where('assistant_id', $assistant->id)
            ->where('is_active', true)
            ->get()
            ->pluck('doctor')
            ->filter(fn (?Professional $doctor): bool => $doctor?->is_active && $doctor->role === ProfessionalRole::Doctor)
            ->values();
    }

    private function extractDoctorName(string $messageBody): ?string
    {
        if (! preg_match('/(?:^|[\n,;])\s*doctor\s*:\s*([^\n,;]+)/iu', $messageBody, $matches)) {
            return null;
        }

        return trim($matches[1]);
    }

    private function extractDoctorNameFromStart(string $messageBody): ?string
    {
        if (! preg_match('/^\s*((?:dr|dra|doctor|doctora)\.?\s+[^\n,;]+)/iu', $messageBody, $matches)) {
            return null;
        }

        return trim($matches[1]);
    }

    private function matchAssignedDoctorByName(Collection $assignedDoctors, string $doctorName): ?Professional
    {
        $normalizedDoctorName = $this->normalizeProfessionalName($doctorName);

        $exactMatch = $assignedDoctors->first(
            fn (Professional $doctor): bool => $this->normalizeProfessionalName($doctor->name) === $normalizedDoctorName,
        );

        if ($exactMatch) {
            return $exactMatch;
        }

        $partialMatches = $assignedDoctors->filter(
            fn (Professional $doctor): bool => str_starts_with(
                $this->normalizeProfessionalName($doctor->name),
                $normalizedDoctorName,
            ),
        );

        return $partialMatches->count() === 1 ? $partialMatches->first() : null;
    }

    private function appendSenderAssistant(array $parsedData, Professional $sender): array
    {
        if ($sender->role !== ProfessionalRole::Assistant) {
            return $parsedData;
        }

        $assistants = $parsedData['assistants'] ?? [];
        $normalizedSenderName = $this->normalizeProfessionalName($sender->name);

        $alreadyIncluded = collect($assistants)->contains(
            fn (string $assistantName): bool => $this->normalizeProfessionalName($assistantName) === $normalizedSenderName,
        );

        if (! $alreadyIncluded) {
            $assistants[] = $sender->name;
        }

        $parsedData['assistants'] = $assistants;

        return $parsedData;
    }

    private function unresolvedDoctorMessage(Professional $sender): string
    {
        if ($sender->role !== ProfessionalRole::Assistant) {
            return 'No pudimos registrar la actividad porque tu perfil no esta configurado como doctor o auxiliar.';
        }

        $assignedDoctors = $this->assignedDoctorsForAssistant($sender);

        if ($assignedDoctors->isEmpty()) {
            return 'No pudimos registrar la actividad porque no tienes doctores asignados activos. Contacta al administrador.';
        }

        return "No pudimos registrar la actividad porque perteneces a varios doctores. Envia nuevamente el mensaje indicando el doctor, por ejemplo:\n\n"
            ."Doctor: Dr. Carlos Ramirez\n"
            ."Paciente: Maria Perez\n"
            ."Procedimiento: Limpieza dental\n"
            .'Pago: efectivo';
    }

    private function normalizeProfessionalName(string $name): string
    {
        return Str::of($name)
            ->lower()
            ->ascii()
            ->replaceMatches('/\b(dr|dra|doctor|doctora)\.?\s+/u', '')
            ->squish()
            ->toString();
    }

    private function buildActivitySummary($activity, array $parsedData): string
    {
        $patientName = $activity->patient->full_name ?? $parsedData['patient_name'] ?? 'N/A';
        $procedureName = $activity->procedure->name ?? 'N/A';
        $paymentMethodName = $activity->paymentMethod->name ?? $parsedData['payment_method'] ?? 'N/A';
        $date = Carbon::parse($parsedData['date'] ?? $activity->activity_date)->format('d/m/Y');
        $doctorCommission = number_format($activity->doctor_commission_amount ?? 0, 2);
        $assistantCount = count($parsedData['assistants'] ?? []);

        $summary = "*Actividad pre-registrada:*\n";
        $summary .= "Paciente: {$patientName}\n";
        $summary .= "Procedimiento: {$procedureName}\n";
        $summary .= "Metodo de pago: {$paymentMethodName}\n";
        $summary .= "Fecha: {$date}\n";
        $summary .= "Comision doctor: \${$doctorCommission}\n";

        if ($assistantCount > 0) {
            $summary .= 'Auxiliares: '.implode(', ', $parsedData['assistants'])."\n";
        }

        if ($parsedData['needs_review'] ?? false) {
            $summary .= "\n*Nota:* Este registro requiere revision del administrador.\n";
            $summary .= "Motivo: {$parsedData['review_notes']}\n";
        }

        $summary .= "\nResponde *OK* para confirmar y guardar definitivamente, "
            .'o *CORREGIR* [cambio] para enviarla a revision.';

        return $summary;
    }

    private function findPendingOriginalForReply(WhatsappMessage $reply): ?WhatsappMessage
    {
        $text = strtolower(trim($reply->message_body));

        if ($text !== 'ok' && ! str_starts_with($text, 'corregir')) {
            return null;
        }

        return WhatsappMessage::query()
            ->where('from_phone', $reply->from_phone)
            ->where('direction', WhatsappMessageDirection::Incoming->value)
            ->where('status', WhatsappMessageStatus::Parsed->value)
            ->where('id', '<', $reply->id)
            ->latest('id')
            ->first();
    }

    public function processReply(WhatsappMessage $reply, WhatsappMessage $original): void
    {
        $text = strtolower(trim($reply->message_body));
        $activity = $this->findActivityForMessage($original);

        if ($text === 'ok') {
            $activity?->approve();
            $original->markAsConfirmed();
            $reply->markAsConfirmed();
            $this->sendMessage($reply->from_phone, 'Confirmado! Tu actividad ha sido guardada definitivamente.');
        } elseif (str_starts_with($text, 'corregir')) {
            $notes = trim(substr($reply->message_body, 8));
            $activity?->requestCorrection($notes ?: 'Solicitud de correccion sin detalles');
            $original->markAsNeedsReview($notes ?: 'Solicitud de correccion sin detalles');
            $reply->markAsNeedsReview($notes);
            $this->sendMessage($reply->from_phone, 'Recibido. Tu registro ha sido enviado a revision. El administrador lo revisara.');
        } else {
            $this->sendMessage($reply->from_phone, 'No entendimos tu respuesta. Responde *OK* para confirmar o *CORREGIR* [cambio].');
        }
    }

    private function findActivityForMessage(WhatsappMessage $message): ?ActivityRecord
    {
        return ActivityRecord::query()
            ->where('notes', 'like', '%Msg ID: '.$message->message_sid.'%')
            ->latest('id')
            ->first();
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
