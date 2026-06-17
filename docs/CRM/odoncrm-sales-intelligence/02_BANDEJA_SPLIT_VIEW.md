# Fase 2: Bandeja de Entrada Split-View

## Objetivo

Crear un centro de mando en Filament para gestionar leads sociales con lista, conversacion, perfil, timeline y actualizacion en vivo.

## Estado Actual Del Sistema

Existen paginas Filament relacionadas con inbox social, hot leads y alertas. Tambien existen `social_comments`, scoring, alertas y recursos para comentarios sociales.

## Alcance De La Fase

1. Crear o evolucionar una pagina Filament tipo inbox 360.
2. Integrar Livewire 3 con eventos de Reverb.
3. Refrescar leads cuando llegue `LeadActivityDetected`.
4. Mostrar mini-CRM lateral con puntaje, estado, procedimiento, alertas y timeline resumido.
5. Agregar accion de Gemini para sugerir respuesta basada en historial.

## Cambios Backend

### Filament Page

Ruta propuesta:

`app/Filament/Resources/SocialLeads/Pages/Inbox.php`

Alternativa minimalista:

Extender la pagina existente `app/Filament/Pages/SocialInbox.php` si ya cubre parte del flujo.

### Livewire/Reverb

Implementar escucha:

```php
#[On('echo-private:admin-notifications,LeadActivityDetected')]
public function handleLeadActivityDetected(array $payload): void
{
    // Refrescar lista, resaltar tarjeta y reordenar lead activo.
}
```

## Cambios Frontend/UI

Layout recomendado en Filament:

1. Columna 1: lista de leads/chats.
2. Columna 2: conversacion o detalle del comentario/hilo.
3. Columna 3: perfil, scoring, timeline y acciones.

Comportamiento visual:

1. Lead con actividad nueva sube al primer lugar.
2. Tarjeta pulsa en verde durante unos segundos.
3. Hot leads muestran indicador rojo o badge destacado.
4. Leads recalentados muestran indicador amarillo.

## Acciones IA

Boton: `Sugerir respuesta basada en historial`.

Entrada para Gemini:

1. Texto del comentario original.
2. Respuestas existentes.
3. Timeline de eventos del Smart Link.
4. Procedimiento sugerido.
5. Estado de conversion.

Salida esperada:

1. Respuesta breve para WhatsApp o red social.
2. Tono recomendado.
3. Proxima accion comercial.

## Archivos A Modificar

1. `app/Filament/Pages/SocialInbox.php` o nuevo `app/Filament/Resources/SocialLeads/Pages/Inbox.php`
2. `resources/views/filament/pages/social-inbox.blade.php` o nueva vista dedicada.
3. `app/Services/GeminiJsonService.php`
4. `app/Services/SocialLeadOperationsService.php` si existe o se crea.
5. `routes/channels.php`

## Criterios De Aceptacion

1. La bandeja muestra lista, conversacion y perfil en 3 columnas en desktop.
2. En mobile el layout se adapta sin romper navegacion.
3. Al llegar `LeadActivityDetected`, el lead afectado se actualiza sin recargar la pagina.
4. El `interest_score` visible coincide con el valor actual en base de datos.
5. El boton de IA genera una sugerencia util y auditable.

## Riesgos / Dependencias

1. Depende de Reverb y broadcast privado funcionando.
2. La pagina puede volverse pesada si carga demasiados eventos por lead.
3. Se debe paginar o lazy-load conversaciones y timeline.

## Checklist De Implementacion

1. Elegir si se extiende pagina existente o se crea una nueva.
2. Crear layout split-view.
3. Agregar listener Livewire Echo.
4. Implementar resaltado visual de actividad.
5. Agregar mini-CRM lateral.
6. Agregar accion Gemini.
7. Probar actividad en vivo desde Smart Link.
8. Ejecutar tests.
