<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiJsonService
{
    public function generate(string $systemPrompt, string $userPrompt): string
    {
        $apiKey = config('services.gemini.api_key');

        if (!$apiKey) {
            throw new \RuntimeException('Gemini API key no configurada.');
        }

        $apiUrl = rtrim((string) config('services.gemini.api_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $model = config('services.gemini.model', 'gemini-2.0-flash');
        $url = "{$apiUrl}/models/{$model}:generateContent?key=" . urlencode((string) $apiKey);

        $response = Http::timeout((int) config('services.gemini.request_timeout', 30))
            ->post($url, [
                'systemInstruction' => [
                    'parts' => [
                        ['text' => $systemPrompt],
                    ],
                ],
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            ['text' => $userPrompt],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'responseMimeType' => 'application/json',
                ],
            ])
            ->throw();

        $content = data_get($response->json(), 'candidates.0.content.parts.0.text');

        if (!is_string($content) || trim($content) === '') {
            throw new \RuntimeException('Gemini no devolvio contenido interpretable.');
        }

        return $content;
    }
}
