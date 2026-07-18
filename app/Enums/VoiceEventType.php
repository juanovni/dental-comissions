<?php

namespace App\Enums;

enum VoiceEventType: string
{
    case SessionStarted = 'session_started';
    case UserMessage = 'user_message';
    case AssistantMessage = 'assistant_message';
    case ToolCalled = 'tool_called';
    case ToolResult = 'tool_result';
    case AppointmentCreated = 'appointment_created';
    case HandoffRequested = 'handoff_requested';
    case SessionEnded = 'session_ended';
    case Error = 'error';

    public function label(): string
    {
        return match ($this) {
            self::SessionStarted => 'Sesion iniciada',
            self::UserMessage => 'Mensaje del usuario',
            self::AssistantMessage => 'Mensaje del asistente',
            self::ToolCalled => 'Tool invocado',
            self::ToolResult => 'Resultado del tool',
            self::AppointmentCreated => 'Cita creada',
            self::HandoffRequested => 'Transferencia solicitada',
            self::SessionEnded => 'Sesion finalizada',
            self::Error => 'Error',
        };
    }
}
