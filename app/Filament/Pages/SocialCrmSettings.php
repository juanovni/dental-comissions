<?php

namespace App\Filament\Pages;

use App\Models\SocialCrmSetting;
use App\Services\SocialCrmSettingsService;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\On;

class SocialCrmSettings extends Page
{
    protected string $view = 'filament.pages.social-crm-settings';

    protected static string|\UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?string $navigationLabel = 'Configuración guiada CRM';

    protected static ?string $slug = 'crm-settings-guide';

    protected static ?string $title = 'Configuración guiada CRM';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?int $navigationSort = 22;

    public ?array $data = [];

    public static function getNavigationBadge(): ?string
    {
        $dryRun = app(SocialCrmSettingsService::class)->autoReplyDryRun();

        return $dryRun ? 'Modo prueba' : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public function getSubheading(): HtmlString
    {
        return new HtmlString('<span class="text-sm font-normal text-muted-foreground">Organiza las configuraciones operativas del CRM social sin editar JSON manualmente.</span>');
    }

    public function mount(): void
    {
        $this->form->fill($this->loadSettings());
    }

    #[On('social-crm-automatic-mode-updated')]
    public function refreshAutomaticModeSettings(): void
    {
        $this->form->fill($this->loadSettings());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->citasSection(),
                $this->respuestasAutomaticasSection(),
                $this->mensajesSection(),
                $this->seguimientoComercialSection(),
                $this->alertasYScoringSection(),
                $this->smartLinkSection(),
            ])
            ->statePath('data');
    }

    public function settingsNavigationItems(): array
    {
        return [
            [
                'id' => 'citas',
                'label' => 'Citas',
                'description' => 'Horarios y disponibilidad',
            ],
            [
                'id' => 'respuestas-automaticas',
                'label' => 'Respuestas automáticas',
                'description' => 'Reglas de auto-respuesta',
            ],
            [
                'id' => 'mensajes-identidad',
                'label' => 'Mensajes e identidad',
                'description' => 'Plantillas y nombre visible',
            ],
            [
                'id' => 'seguimiento-comercial',
                'label' => 'Seguimiento comercial',
                'description' => 'Urgencia y motivos de pérdida',
            ],
            [
                'id' => 'alertas-scoring',
                'label' => 'Alertas y scoring',
                'description' => 'Puntajes y avisos operativos',
            ],
            [
                'id' => 'smart-link',
                'label' => 'Smart Link',
                'description' => 'Duración, ping y alertas',
            ],
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $this->persistSettings($state);

        Notification::make()
            ->title('Configuración guardada')
            ->body('Los cambios se han aplicado correctamente.')
            ->success()
            ->send();
    }

    private function persistSettings(array $state): void
    {
        foreach ($state as $key => $value) {
            $existing = SocialCrmSetting::query()->where('key', $key)->first();

            if ($existing) {
                $existing->update([
                    'value' => $this->castForDb($value, $existing->value_type),
                ]);
            } else {
                $valueType = match (true) {
                    is_bool($value) => 'boolean',
                    is_int($value) => 'integer',
                    is_array($value) => 'array',
                    default => 'string',
                };

                SocialCrmSetting::create([
                    'key' => $key,
                    'setting_group' => $this->inferGroup($key),
                    'label' => $this->inferLabel($key),
                    'value_type' => $valueType,
                    'value' => $this->castForDb($value, $valueType),
                    'is_active' => true,
                ]);
            }
        }

        app(SocialCrmSettingsService::class)->clearCache();
    }

    private function saveSectionAction(string $name): Action
    {
        return Action::make($name)
            ->label('Guardar configuración')
            ->icon('heroicon-o-check')
            ->color('primary')
            ->action('save');
    }

    private function citasSection(): Section
    {
        return Section::make('Citas')
            ->id('citas')
            ->icon('heroicon-o-calendar-days')
            ->description('Define los horarios, duración y slots disponibles para agendar citas automáticas.')
            ->schema([
                Toggle::make('social_appointment_propose_slots')
                    ->label('Proponer slots reales')
                    ->helperText('Muestra horarios reales disponibles en la respuesta del bot cuando el paciente muestra interés en agendar.'),
                Toggle::make('social_appointment_auto_confirm')
                    ->label('Auto-confirmar cita')
                    ->helperText('Si está activo, crea la cita automáticamente cuando el paciente acepta un slot.'),
                CheckboxList::make('social_appointment_clinic_days')
                    ->label('Días laborables')
                    ->helperText('Días de la semana en que la clínica atiende.')
                    ->options([
                        0 => 'Domingo',
                        1 => 'Lunes',
                        2 => 'Martes',
                        3 => 'Miércoles',
                        4 => 'Jueves',
                        5 => 'Viernes',
                        6 => 'Sábado',
                    ])
                    ->columns(7),
                TextInput::make('social_appointment_clinic_open')
                    ->label('Hora de apertura')
                    ->helperText('Hora de inicio de atención (formato HH:MM).')
                    ->type('time'),
                TextInput::make('social_appointment_clinic_close')
                    ->label('Hora de cierre')
                    ->helperText('Hora de fin de atención (formato HH:MM).')
                    ->type('time'),
                TextInput::make('social_appointment_slot_duration')
                    ->label('Duración de cita (minutos)')
                    ->helperText('Duración de cada espacio en minutos.')
                    ->numeric()
                    ->minValue(15),
                TextInput::make('social_appointment_lead_time_hours')
                    ->label('Anticipación mínima (horas)')
                    ->helperText('Mínimo de horas de anticipación desde ahora para ofrecer un slot.')
                    ->numeric()
                    ->minValue(0),
                TextInput::make('social_appointment_max_slots_offer')
                    ->label('Máximo de slots a ofrecer')
                    ->helperText('Cantidad máxima de slots que el bot propone al paciente.')
                    ->numeric()
                    ->minValue(1),
            ])
            ->columns(2)
            ->footerActions([
                $this->saveSectionAction('save_citas'),
            ])
            ->footerActionsAlignment(Alignment::End);
    }

    private function respuestasAutomaticasSection(): Section
    {
        return Section::make('Respuestas Automáticas')
            ->id('respuestas-automaticas')
            ->icon('heroicon-o-chat-bubble-left-right')
            ->description('Controla cómo y cuándo el sistema responde automáticamente a comentarios en redes sociales.')
            ->schema([
                Toggle::make('social_auto_reply_enabled')
                    ->label('Auto-respuestas activadas')
                    ->helperText('Activa o desactiva las respuestas automáticas en comentarios de Facebook/Instagram.'),
                Toggle::make('social_auto_reply_dry_run')
                    ->label('Modo dry-run')
                    ->helperText('Cuando está activo, genera el mensaje pero no lo publica en Meta. Solo guarda auditoría.'),
                Toggle::make('social_auto_reply_use_ai')
                    ->label('Usar IA para generar respuesta')
                    ->helperText('Si está desactivado, usa la plantilla estática sin IA.'),
                TextInput::make('social_auto_reply_max_attempts')
                    ->label('Máximo de reintentos')
                    ->helperText('Número máximo de intentos de publicación en Meta antes de marcar como fallido.')
                    ->numeric()
                    ->minValue(1),
                Toggle::make('social_auto_reply_use_smart_link')
                    ->label('Usar Smart Link en vez de WhatsApp directo')
                    ->helperText('Si está activo, el comentario lleva al Smart Link. Si está desactivado, lleva directamente a WhatsApp.'),
                Select::make('social_auto_reply_allowed_classifications')
                    ->label('Clasificaciones que activan auto-respuesta')
                    ->helperText('Lista de clasificaciones de comentario que disparan respuesta automática.')
                    ->multiple()
                    ->options([
                        'sales_lead' => 'Lead de ventas',
                        'commercial_question' => 'Consulta comercial',
                        'medical_sensitive' => 'Consulta médica sensible',
                        'complaint' => 'Queja',
                        'spam' => 'Spam',
                        'positive_opinion' => 'Opinión positiva',
                        'negative_opinion' => 'Opinión negativa',
                    ]),
            ])
            ->columns(2)
            ->footerActions([
                $this->saveSectionAction('save_respuestas_automaticas'),
            ])
            ->footerActionsAlignment(Alignment::End);
    }

    private function mensajesSection(): Section
    {
        return Section::make('Mensajes e Identidad')
            ->id('mensajes-identidad')
            ->icon('heroicon-o-chat-bubble-left-ellipsis')
            ->description('Nombre de la clínica, cabeceras y plantillas usadas para responder a leads.')
            ->schema([
                Section::make('Identidad de la clínica')
                    ->description('Nombre y cabecera que aparecen en los mensajes automáticos.')
                    ->schema([
                        TextInput::make('social_auto_reply_company_name')
                            ->label('Nombre de la empresa/clínica')
                            ->helperText('Nombre que aparece en la cabecera del mensaje automático.')
                            ->maxLength(255),
                        Textarea::make('social_auto_reply_header_template')
                            ->label('Plantilla de cabecera')
                            ->helperText('Primera línea del mensaje. Variable disponible: {empresa}.')
                            ->rows(2),
                    ]),
                Section::make('Plantillas de mensajes')
                    ->description('Textos utilizados para responder a leads y derivar a WhatsApp.')
                    ->schema([
                        Textarea::make('social_auto_reply_template')
                            ->label('Plantilla de respuesta automática')
                            ->helperText('Cuerpo del mensaje automático. Variables: {empresa}, {smart_link}, {whatsapp_link}, {tracking_token}, {procedure_name}, {lead_first_name}.')
                            ->rows(4),
                        Textarea::make('social_whatsapp_reply_template')
                            ->label('Texto para derivar a WhatsApp')
                            ->helperText('Variables disponibles: {token}, {platform}, {whatsapp_link}, {smart_link}.')
                            ->rows(3),
                        TextInput::make('social_whatsapp_autocopy_toast')
                            ->label('Mensaje de confirmación al copiar link')
                            ->helperText('Toast visible para secretaria cuando el navegador copia el link.')
                            ->maxLength(255),
                    ]),
            ])
            ->footerActions([
                $this->saveSectionAction('save_mensajes_identidad'),
            ])
            ->footerActionsAlignment(Alignment::End);
    }

    private function seguimientoComercialSection(): Section
    {
        return Section::make('Seguimiento Comercial')
            ->id('seguimiento-comercial')
            ->icon('heroicon-o-phone-arrow-up-right')
            ->description('Define umbrales de urgencia, tiempos de contacto y seguimiento automático.')
            ->schema([
                Section::make('Reglas de seguimiento')
                    ->description('Define umbrales de urgencia, tiempos de contacto y seguimiento automático.')
                    ->schema([
                        TextInput::make('social_sales_urgent_score_threshold')
                            ->label('Puntaje mínimo para lead urgente')
                            ->helperText('Si el lead supera este puntaje, se marca como urgente.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100),
                        TextInput::make('social_sales_max_hours_without_contact')
                            ->label('Horas máximas sin contacto')
                            ->helperText('Si un lead caliente supera estas horas sin contacto, se genera alerta.')
                            ->numeric()
                            ->minValue(1),
                        TextInput::make('social_sales_default_follow_up_hours')
                            ->label('Horas para seguimiento por defecto')
                            ->helperText('Tiempo predeterminado para programar un seguimiento.')
                            ->numeric()
                            ->minValue(1),
                        Select::make('social_sales_lost_reasons')
                            ->label('Motivos de pérdida')
                            ->helperText('Razones predefinidas para marcar un lead como perdido.')
                            ->multiple()
                            ->options([
                                'sin_respuesta' => 'Sin respuesta',
                                'precio' => 'Precio',
                                'fuera_de_zona' => 'Fuera de zona',
                                'ya_atendido' => 'Ya atendido',
                                'no_califica' => 'No califica',
                                'no_contesta_whatsapp' => 'No contesta WhatsApp',
                                'ya_se_atendio' => 'Ya se atendió en otra clínica',
                                'muy_caro' => 'Muy caro',
                                'no_urgencia' => 'Sin urgencia',
                                'no_aplica' => 'No aplica',
                            ]),
                    ])->columns(2),
                Section::make('Seguimiento por clic en WhatsApp')
                    ->description('Configura el comportamiento cuando un lead hace clic en el enlace de WhatsApp pero no envía mensaje.')
                    ->schema([
                        TextInput::make('social_whatsapp_click_follow_up_minutes')
                            ->label('Minutos sin mensaje tras clic')
                            ->helperText('Minutos de espera tras un clic en WhatsApp sin recibir mensaje para generar alerta.')
                            ->numeric()
                            ->minValue(5),
                        Toggle::make('social_whatsapp_follow_up_auto_reply_enabled')
                            ->label('Auto-respuesta de seguimiento')
                            ->helperText('Envía un mensaje de seguimiento en el comentario original si el lead hizo clic en WhatsApp pero no envió mensaje.'),
                        Textarea::make('social_whatsapp_follow_up_auto_reply_template')
                            ->label('Plantilla de seguimiento')
                            ->helperText('Variables: {author_name}, {platform}.')
                            ->rows(3),
                    ]),
            ])
            ->footerActions([
                $this->saveSectionAction('save_seguimiento_comercial'),
            ])
            ->footerActionsAlignment(Alignment::End);
    }

    private function alertasYScoringSection(): Section
    {
        return Section::make('Alertas y Scoring')
            ->id('alertas-scoring')
            ->icon('heroicon-o-bell-alert')
            ->description('Controla alertas operativas y puntajes de interacción del lead.')
            ->schema([
                Section::make('Alertas')
                    ->description('Controla la generación de alertas operativas sobre leads.')
                    ->schema([
                        Toggle::make('social_alerts_enabled')
                            ->label('Alertas de leads activas')
                            ->helperText('Permite activar o pausar la generación de alertas operativas.'),
                        TextInput::make('social_alert_check_frequency_minutes')
                            ->label('Frecuencia de revisión (minutos)')
                            ->helperText('Referencia operativa para el comando programado.')
                            ->numeric()
                            ->minValue(1),
                    ])->columns(2),
                Section::make('Puntajes (Scoring)')
                    ->description('Define los puntajes asignados a cada acción del lead. El lead caliente se determina al superar el umbral.')
                    ->schema([
                        TextInput::make('social_score_token_generated')
                            ->label('Puntos por token generado')
                            ->helperText('Puntaje asignado cuando se genera un token de seguimiento.')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('social_score_smart_link_click')
                            ->label('Puntos por clic en Smart Link')
                            ->helperText('Puntaje asignado cuando el lead hace clic en el Smart Link.')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('social_score_smart_link_revisit')
                            ->label('Puntos por reingreso a Smart Link')
                            ->helperText('Puntaje asignado cuando el lead vuelve a abrir el Smart Link.')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('social_score_reheated_revisit_bonus')
                            ->label('Bonus por reingreso después de recalentamiento')
                            ->helperText('Puntos extra cuando un lead recalentado vuelve a interactuar.')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('social_hot_lead_threshold')
                            ->label('Puntaje mínimo para lead caliente')
                            ->helperText('Cuando el puntaje total del lead supera este valor, se marca como caliente.')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100),
                    ])->columns(2),
            ])
            ->footerActions([
                $this->saveSectionAction('save_alertas_scoring'),
            ])
            ->footerActionsAlignment(Alignment::End);
    }

    private function smartLinkSection(): Section
    {
        return Section::make('Smart Link')
            ->id('smart-link')
            ->icon('heroicon-o-link')
            ->description('Configura los umbrales de duración y puntajes asociados a la interacción con el Smart Link.')
            ->schema([
                TextInput::make('social_smart_link_duration_threshold_seconds')
                    ->label('Umbral de duración (segundos)')
                    ->helperText('A partir de cuántos segundos de visualización se considera una visita de calidad.')
                    ->numeric()
                    ->minValue(10),
                TextInput::make('social_smart_link_ping_seconds')
                    ->label('Intervalo de ping (segundos)')
                    ->helperText('Cada cuántos segundos se registra actividad mientras el lead visualiza el Smart Link.')
                    ->numeric()
                    ->minValue(5),
                TextInput::make('social_smart_link_duration_score')
                    ->label('Puntos por duración')
                    ->helperText('Puntaje adicional cuando el lead supera el umbral de duración.')
                    ->numeric()
                    ->minValue(0),
                Textarea::make('social_smart_link_duration_alert')
                    ->label('Texto de alerta por alta permanencia')
                    ->helperText('Mensaje de alerta cuando un lead pasa mucho tiempo en el Smart Link.')
                    ->rows(2),
            ])
            ->columns(2)
            ->footerActions([
                $this->saveSectionAction('save_smart_link'),
            ])
            ->footerActionsAlignment(Alignment::End);
    }

    private function loadSettings(): array
    {
        $keys = $this->allSettingKeys();

        $settings = SocialCrmSetting::query()
            ->whereIn('key', $keys)
            ->where('is_active', true)
            ->get()
            ->keyBy('key');

        $data = [];

        foreach ($keys as $key) {
            $setting = $settings->get($key);

            if ($setting && $setting->value !== null) {
                $data[$key] = $this->castFromDb($setting->value, $setting->value_type);
            } else {
                $data[$key] = $this->defaultValue($key);
            }
        }

        return $data;
    }

    private function allSettingKeys(): array
    {
        return [
            // Citas
            'social_appointment_propose_slots',
            'social_appointment_auto_confirm',
            'social_appointment_clinic_days',
            'social_appointment_clinic_open',
            'social_appointment_clinic_close',
            'social_appointment_slot_duration',
            'social_appointment_lead_time_hours',
            'social_appointment_max_slots_offer',
            // Respuestas automáticas
            'social_auto_reply_enabled',
            'social_auto_reply_dry_run',
            'social_auto_reply_use_ai',
            'social_auto_reply_max_attempts',
            'social_auto_reply_use_smart_link',
            'social_auto_reply_allowed_classifications',
            // Mensajes
            'social_auto_reply_company_name',
            'social_auto_reply_header_template',
            'social_auto_reply_template',
            'social_whatsapp_reply_template',
            'social_whatsapp_autocopy_toast',
            // Seguimiento comercial
            'social_sales_urgent_score_threshold',
            'social_sales_max_hours_without_contact',
            'social_sales_default_follow_up_hours',
            'social_sales_lost_reasons',
            'social_whatsapp_click_follow_up_minutes',
            'social_whatsapp_follow_up_auto_reply_enabled',
            'social_whatsapp_follow_up_auto_reply_template',
            // Alertas y scoring
            'social_alerts_enabled',
            'social_alert_check_frequency_minutes',
            'social_score_token_generated',
            'social_score_smart_link_click',
            'social_score_smart_link_revisit',
            'social_score_reheated_revisit_bonus',
            'social_hot_lead_threshold',
            // Smart Link
            'social_smart_link_duration_threshold_seconds',
            'social_smart_link_ping_seconds',
            'social_smart_link_duration_score',
            'social_smart_link_duration_alert',
        ];
    }

    private function castFromDb(mixed $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'array' => is_array($value) ? $value : [],
            default => (string) $value,
        };
    }

    private function castForDb(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $value,
            'array' => is_array($value) ? $value : [],
            default => (string) ($value ?? ''),
        };
    }

    private function defaultValue(string $key): mixed
    {
        return match ($key) {
            'social_appointment_propose_slots' => false,
            'social_appointment_auto_confirm' => false,
            'social_appointment_clinic_days' => [1, 2, 3, 4, 5],
            'social_appointment_clinic_open' => '09:00',
            'social_appointment_clinic_close' => '18:00',
            'social_appointment_slot_duration' => 45,
            'social_appointment_lead_time_hours' => 2,
            'social_appointment_max_slots_offer' => 3,
            'social_auto_reply_enabled' => false,
            'social_auto_reply_dry_run' => true,
            'social_auto_reply_use_ai' => true,
            'social_auto_reply_max_attempts' => 2,
            'social_auto_reply_use_smart_link' => true,
            'social_auto_reply_allowed_classifications' => ['sales_lead', 'commercial_question'],
            'social_auto_reply_company_name' => 'Clínica Dental',
            'social_auto_reply_header_template' => '👋 Te saluda {empresa}',
            'social_auto_reply_template' => 'Hola, con gusto te ayudamos. Te dejamos la información inicial y el acceso para continuar por WhatsApp aquí: {smart_link}',
            'social_whatsapp_reply_template' => 'Hola! Gracias por escribirnos. Para darte una orientación personalizada, abre este enlace: {smart_link}. Tu código de atención es {token}. Si prefieres WhatsApp directo: {whatsapp_link}',
            'social_whatsapp_autocopy_toast' => 'Link copiado. Pégalo ahora en el chat de Instagram.',
            'social_sales_urgent_score_threshold' => 75,
            'social_sales_max_hours_without_contact' => 4,
            'social_sales_default_follow_up_hours' => 24,
            'social_sales_lost_reasons' => ['sin_respuesta'],
            'social_whatsapp_click_follow_up_minutes' => 30,
            'social_whatsapp_follow_up_auto_reply_enabled' => false,
            'social_whatsapp_follow_up_auto_reply_template' => 'Hola {author_name}, vi que abriste el enlace de WhatsApp pero no me enviaste mensaje. ¿Te quedó alguna duda? Puedes responder aquí mismo o escribirme al WhatsApp cuando gustes.',
            'social_alerts_enabled' => true,
            'social_alert_check_frequency_minutes' => 10,
            'social_score_token_generated' => 30,
            'social_score_smart_link_click' => 15,
            'social_score_smart_link_revisit' => 10,
            'social_score_reheated_revisit_bonus' => 10,
            'social_hot_lead_threshold' => 75,
            'social_smart_link_duration_threshold_seconds' => 60,
            'social_smart_link_ping_seconds' => 15,
            'social_smart_link_duration_score' => 20,
            'social_smart_link_duration_alert' => 'Paciente está muy interesado en los resultados visuales.',
            default => null,
        };
    }

    private function inferGroup(string $key): string
    {
        return match (true) {
            str_contains($key, 'appointment') => 'appointments',
            str_contains($key, 'auto_reply') => 'auto_reply',
            str_contains($key, 'whatsapp') && ! str_contains($key, 'follow_up') => 'whatsapp_bridge',
            str_contains($key, 'sales') => 'sales',
            str_contains($key, 'score'), str_contains($key, 'hot_lead') => 'scoring',
            str_contains($key, 'alert') => 'alerts',
            str_contains($key, 'smart_link') => 'smart_link',
            str_contains($key, 'whatsapp') => 'auto_reply',
            default => 'general',
        };
    }

    private function inferLabel(string $key): string
    {
        $labels = [
            'social_sales_urgent_score_threshold' => 'Puntaje mínimo para lead urgente',
            'social_sales_max_hours_without_contact' => 'Horas máximas sin contacto',
            'social_sales_default_follow_up_hours' => 'Horas para seguimiento por defecto',
            'social_sales_lost_reasons' => 'Motivos de pérdida',
            'social_score_reheated_revisit_bonus' => 'Bonus por reingreso después de recalentamiento',
            'social_smart_link_duration_threshold_seconds' => 'Umbral de duración del Smart Link',
            'social_smart_link_ping_seconds' => 'Intervalo de ping del Smart Link',
            'social_smart_link_duration_score' => 'Puntos por duración del Smart Link',
            'social_smart_link_duration_alert' => 'Alerta de alta permanencia en Smart Link',
        ];

        return $labels[$key] ?? str($key)
            ->replace('social_', '')
            ->replace('_', ' ')
            ->title()
            ->toString();
    }
}
