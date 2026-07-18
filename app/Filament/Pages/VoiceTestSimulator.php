<?php

namespace App\Filament\Pages;

use App\Models\VoiceCall;
use App\Services\VoiceAiService;
use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Livewire\Attributes\On;

class VoiceTestSimulator extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-phone';
    protected static string|\UnitEnum|null $navigationGroup = 'Pity Voice';
    protected static ?string $navigationLabel = 'Simulador de llamada';
    protected static ?string $title = 'Pity Voice — Simulador de llamada';
    protected static ?string $slug = 'voice-test-simulator';
    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.voice-test-simulator';

    public ?string $phone = '';
    public ?string $message = '';
    public array $conversation = [];
    public array $toolCalls = [];
    public ?string $callId = null;
    public bool $started = false;
    public bool $ended = false;
    public bool $sending = false;

    public function mount(): void
    {
        $this->form->fill();
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('phone')
                ->label('Telefono del paciente')
                ->placeholder('+593999999999')
                ->required()
                ->rule('regex:/^\+[0-9]{7,15}$/')
                ->visible(fn (): bool => !$this->started),
        ];
    }

    public function startCall(): void
    {
        $this->validate();

        $service = app(VoiceAiService::class);
        $result = $service->startConversation($this->phone, $callId);

        $this->callId = $callId;
        $this->started = true;
        $this->conversation[] = [
            'type' => 'assistant',
            'text' => $result['message'],
        ];
    }

    public function sendMessage(): void
    {
        if (blank($this->message) || $this->ended || $this->sending) {
            return;
        }

        $this->sending = true;

        $userText = $this->message;
        $this->message = '';

        $this->conversation[] = [
            'type' => 'user',
            'text' => $userText,
        ];

        try {
            $service = app(VoiceAiService::class);
            $result = $service->sendMessage((int) $this->callId, $userText);

            foreach ($result['tool_calls'] as $tc) {
                $this->toolCalls[] = $tc;
            }

            if (filled($result['message'])) {
                $this->conversation[] = [
                    'type' => 'assistant',
                    'text' => $result['message'],
                ];
            }

            $this->ended = $result['ended'] ?? false;
        } catch (\Throwable $e) {
            $this->conversation[] = [
                'type' => 'assistant',
                'text' => 'Lo siento, ocurrio un error inesperado. Por favor intenta de nuevo.',
            ];
        } finally {
            $this->sending = false;
        }
    }

    public function resetCall(): void
    {
        $this->phone = '';
        $this->message = '';
        $this->conversation = [];
        $this->toolCalls = [];
        $this->callId = null;
        $this->started = false;
        $this->ended = false;
        $this->sending = false;
        $this->form->fill();
    }

    public function getCallProperty(): ?VoiceCall
    {
        if (!$this->callId) {
            return null;
        }

        return VoiceCall::with('events')->find((int) $this->callId);
    }
}
