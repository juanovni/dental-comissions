<?php

namespace App\Services;

use App\Models\DoctorAssistantAssignment;
use App\Models\Procedure;
use App\Models\Professional;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenAI\Laravel\Facades\OpenAI;

class AiParsingService
{
    public function parseMessage(string $messageBody, Professional $doctor): array
    {
        $procedures = Procedure::where('is_active', true)
            ->get()
            ->map(fn ($p) => [
                'name' => $p->name,
                'code' => $p->code,
            ])
            ->toArray();

        $assistantIds = DoctorAssistantAssignment::where('doctor_id', $doctor->id)
            ->where('is_active', true)
            ->pluck('assistant_id');

        $assistants = Professional::whereIn('id', $assistantIds)
            ->get()
            ->map(fn ($a) => ['name' => $a->name])
            ->toArray();

        $today = now()->format('Y-m-d');

        $systemPrompt = $this->buildSystemPrompt($procedures, $assistants, $today);

        try {
            $response = OpenAI::chat()->create([
                'model' => config('services.openai.model', 'gpt-4o-mini'),
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $messageBody],
                ],
                'temperature' => 0.1,
                'response_format' => ['type' => 'json_object'],
            ]);

            $content = $response->choices[0]->message->content;
            $parsed = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Error decodificando respuesta JSON de OpenAI', [
                    'content' => $content,
                ]);
                return $this->defaultNeedsReview('Error al interpretar respuesta de IA');
            }

            return $this->validateParsedData($parsed);
        } catch (\Throwable $e) {
            Log::error('Error llamando OpenAI API', [
                'error' => $e->getMessage(),
            ]);

            return $this->parseLocalFallback($messageBody, $doctor)
                ?? $this->defaultNeedsReview('Error al conectar con IA: ' . $e->getMessage());
        }
    }

    private function parseLocalFallback(string $messageBody, Professional $doctor): ?array
    {
        $procedures = Procedure::where('is_active', true)->get();
        $assistants = $this->assignedAssistants($doctor);

        $patientName = $this->extractLabelValue($messageBody, ['paciente', 'patient']);
        $procedureNames = $this->extractListLabelValue($messageBody, ['procedimiento', 'procedimientos', 'procedure', 'procedures']);
        $assistantNames = $this->extractListLabelValue($messageBody, ['auxiliar', 'auxiliares', 'assistant', 'assistants']);
        $date = $this->extractDate($messageBody);

        if (empty($procedureNames)) {
            $procedureNames = $procedures
                ->filter(fn (Procedure $procedure) => $this->containsNormalized($messageBody, $procedure->name))
                ->pluck('name')
                ->values()
                ->all();
        }

        if (empty($assistantNames)) {
            $assistantNames = $assistants
                ->filter(fn (Professional $assistant) => $this->containsNormalized($messageBody, $assistant->name))
                ->pluck('name')
                ->values()
                ->all();
        }

        if (!$patientName) {
            $patientName = $this->extractPatientFromNaturalMessage($messageBody);
        }

        if (!$patientName && empty($procedureNames)) {
            return null;
        }

        return $this->validateParsedData([
            'patient_name' => $patientName ?? '',
            'procedures' => $procedureNames,
            'assistants' => $assistantNames,
            'date' => $date,
            'needs_review' => false,
            'review_notes' => '',
        ]);
    }

    private function assignedAssistants(Professional $doctor): \Illuminate\Support\Collection
    {
        $assistantIds = DoctorAssistantAssignment::where('doctor_id', $doctor->id)
            ->where('is_active', true)
            ->pluck('assistant_id');

        return Professional::whereIn('id', $assistantIds)->get();
    }

    private function extractLabelValue(string $messageBody, array $labels): ?string
    {
        foreach ($labels as $label) {
            if (preg_match('/(?:^|[\n,;])\s*' . preg_quote($label, '/') . '\s*:\s*([^\n,;]+)/iu', $messageBody, $matches)) {
                return trim($matches[1]);
            }
        }

        return null;
    }

    private function extractListLabelValue(string $messageBody, array $labels): array
    {
        $value = $this->extractLabelValue($messageBody, $labels);

        if (!$value) {
            return [];
        }

        return collect(preg_split('/\s*(?:,| y | e |\/|\+)\s*/iu', $value))
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    private function extractDate(string $messageBody): string
    {
        $labeledDate = $this->extractLabelValue($messageBody, ['fecha', 'date']);

        if ($labeledDate) {
            try {
                return Carbon::parse($labeledDate)->format('Y-m-d');
            } catch (\Throwable) {
                return now()->format('Y-m-d');
            }
        }

        $normalized = $this->normalize($messageBody);

        if (str_contains($normalized, 'ayer')) {
            return now()->subDay()->format('Y-m-d');
        }

        if (preg_match('/\b(20\d{2}-\d{2}-\d{2})\b/', $messageBody, $matches)) {
            return $matches[1];
        }

        return now()->format('Y-m-d');
    }

    private function extractPatientFromNaturalMessage(string $messageBody): ?string
    {
        if (!preg_match('/\bpara\s+(.+?)(?:\s+hoy\b|\s+ayer\b|\s+con\b|\s+fecha\b|$)/iu', $messageBody, $matches)) {
            return null;
        }

        return trim($matches[1]);
    }

    private function containsNormalized(string $haystack, string $needle): bool
    {
        return str_contains($this->normalize($haystack), $this->normalize($needle));
    }

    private function normalize(string $value): string
    {
        return Str::of($value)->lower()->ascii()->squish()->toString();
    }

    private function buildSystemPrompt(array $procedures, array $assistants, string $today): string
    {
        $procedureList = collect($procedures)
            ->map(fn ($p) => "- {$p['name']}" . ($p['code'] ? " ({$p['code']})" : ''))
            ->implode("\n");

        $assistantList = collect($assistants)
            ->map(fn ($a) => "- {$a['name']}")
            ->implode("\n");

        $assistantSection = empty($assistants)
            ? "No hay auxiliares asignados a este doctor."
            : "Auxiliares asignados al doctor:\n{$assistantList}";

        return <<<PROMPT
Eres un asistente dental. Tu tarea es extraer informacion de mensajes de doctores dental para registrar actividades.

Debes retornar SOLO un JSON valido con esta estructura exacta:
{
    "patient_name": "nombre del paciente",
    "procedures": ["nombre del procedimiento"],
    "assistants": ["nombre del auxiliar"],
    "date": "YYYY-MM-DD",
    "needs_review": false,
    "review_notes": ""
}

Procedimientos disponibles en el catalogo:
{$procedureList}

{$assistantSection}

Reglas importantes:
- Si no se menciona el nombre del paciente, pon needs_review=true y review_notes="Falta nombre del paciente"
- Si no se menciona ningun procedimiento, pon needs_review=true y review_notes="Falta procedimiento"
- Si la fecha no se menciona explicitamente, usa la fecha de hoy: {$today}
- Si se menciona "ayer", usa la fecha de ayer
- Si se menciona "hace X dias", calcula la fecha correcta
- Los nombres de procedimientos deben coincidir exactamente con el catalogo. Si no coincide exactamente, usa el mas cercano
- Los nombres de auxiliares deben coincidir con la lista de auxiliares asignados. Si no coincide, deja el array vacio y agrega nota
- Si hay ambiguedad o informacion confusa, pon needs_review=true
- procedures puede contener varios procedimientos si el doctor menciona mas de uno
- assistants puede estar vacio si no se menciona auxiliar
- No inventes informacion que no este en el mensaje
PROMPT;
    }

    private function validateParsedData(array $parsed): array
    {
        $result = [
            'patient_name' => $parsed['patient_name'] ?? '',
            'procedures' => $parsed['procedures'] ?? [],
            'assistants' => $parsed['assistants'] ?? [],
            'date' => $parsed['date'] ?? now()->format('Y-m-d'),
            'needs_review' => $parsed['needs_review'] ?? false,
            'review_notes' => $parsed['review_notes'] ?? '',
        ];

        if (empty($result['patient_name'])) {
            $result['needs_review'] = true;
            $result['review_notes'] = $this->appendNote($result['review_notes'], 'Falta nombre del paciente');
        }

        if (empty($result['procedures'])) {
            $result['needs_review'] = true;
            $result['review_notes'] = $this->appendNote($result['review_notes'], 'Falta procedimiento');
        }

        return $result;
    }

    private function defaultNeedsReview(string $reason): array
    {
        return [
            'patient_name' => '',
            'procedures' => [],
            'assistants' => [],
            'date' => now()->format('Y-m-d'),
            'needs_review' => true,
            'review_notes' => $reason,
        ];
    }

    private function appendNote(string $existing, string $new): string
    {
        return $existing ? "{$existing}; {$new}" : $new;
    }
}
