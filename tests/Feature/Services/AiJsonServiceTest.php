<?php

namespace Tests\Feature\Services;

use App\Services\AiJsonService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiJsonServiceTest extends TestCase
{
    public function test_generate_uses_openai_provider(): void
    {
        config([
            'services.ai.provider' => 'openai',
            'services.openai.api_key' => 'test-openai-key',
            'services.openai.model' => 'gpt-4o-mini',
            'services.openai.api_url' => 'https://api.openai.com/v1',
        ]);

        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    ['message' => ['content' => '{"ok":true}']],
                ],
            ]),
        ]);

        $content = app(AiJsonService::class)->generate('Responde JSON.', 'Hola');

        $this->assertSame('{"ok":true}', $content);
        Http::assertSent(fn ($request): bool => $request->hasHeader('Authorization', 'Bearer test-openai-key')
            && $request['model'] === 'gpt-4o-mini'
            && $request['response_format']['type'] === 'json_object');
    }

    public function test_generate_uses_gemini_provider(): void
    {
        config([
            'services.ai.provider' => 'gemini',
            'services.gemini.api_key' => 'test-gemini-key',
            'services.gemini.api_url' => 'https://generativelanguage.googleapis.com/v1beta',
        ]);

        Http::fake([
            '*generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => '{"ok":true}']]]],
                ],
            ]),
        ]);

        $content = app(AiJsonService::class)->generate('Responde JSON.', 'Hola');

        $this->assertSame('{"ok":true}', $content);
    }
}
