<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AiJsonService
{
    public function generate(string $systemPrompt, string $userPrompt): string
    {
        return match ($this->provider()) {
            'openai' => $this->generateWithOpenAi($systemPrompt, $userPrompt),
            'gemini' => app(GeminiJsonService::class)->generate($systemPrompt, $userPrompt),
            'local' => throw new \RuntimeException('Proveedor IA local seleccionado.'),
            default => throw new \RuntimeException('Proveedor IA no soportado: '.$this->provider()),
        };
    }

    private function generateWithOpenAi(string $systemPrompt, string $userPrompt): string
    {
        $apiKey = config('services.openai.api_key');

        if (! $apiKey) {
            throw new \RuntimeException('OPENAI_API_KEY no configurado.');
        }

        $apiUrl = rtrim((string) config('services.openai.api_url', 'https://api.openai.com/v1'), '/');
        $model = config('services.openai.model', 'gpt-4o-mini');

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout((int) config('services.openai.request_timeout', 30))
            ->post($apiUrl.'/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => 0.1,
                'response_format' => ['type' => 'json_object'],
            ])
            ->throw();

        $content = data_get($response->json(), 'choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new \RuntimeException('OpenAI no devolvio contenido interpretable.');
        }

        return $content;
    }

    private function provider(): string
    {
        return strtolower((string) config('services.ai.provider', 'gemini'));
    }
}
