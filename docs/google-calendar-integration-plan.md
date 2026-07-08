# Google Calendar Integration — Plan de Implementación

## Objetivo
Integrar Google Calendar vía OAuth 2.0 para consultar disponibilidad de horarios por doctor, evitar agendamientos conflictivos, y sincronizar citas.

---

## Fase 1 — Infraestructura Base ✅

**Archivos:**
- `database/migrations/2026_07_01_000002_add_google_calendar_oauth_to_professionals_table.php`
- `app/Models/Professional.php` — campos `google_calendar_*`, métodos `hasGoogleCalendar()`, `getGoogleCalendarTokenDecrypted()`, `setGoogleCalendarToken()`, `disconnectGoogleCalendar()`
- `config/services.php` — sección `google_oauth`
- `.env` / `.env.example` / `.env.docker` / `.env.easypanel.example` — variables `GOOGLE_OAUTH_*`

**Qué hace:**
- Migración ejecutada con columnas: `google_calendar_email`, `google_calendar_token` (encriptado), `google_calendar_token_expires_at`, `google_calendar_enabled`.
- Modelo con casts (`datetime`, `boolean`) y helpers para manejo de tokens.
- Config con scopes `calendar.readonly` + `userinfo.email`, access_type `offline`, prompt `consent`.

---

## Fase 2 — GoogleCalendarService ✅

**Archivos:**
- `composer.json` — dependencia `google/apiclient:^2.19`
- `app/Services/GoogleCalendarService.php`
- `tests/Feature/Services/GoogleCalendarServiceTest.php`

**Métodos del servicio:**

| Método | Descripción |
|--------|-------------|
| `client()` | GoogleClient configurado con credenciales OAuth |
| `clientForProfessional(Professional)` | Client con token del doctor; refresh automático si expiró |
| `getAuthorizationUrl(Professional)` | URL de OAuth para conectar un doctor |
| `exchangeCode(Professional, string $code)` | Intercambia code por token, obtiene email vía OAuth2, persiste |
| `getUserEmail(GoogleClient)` | Extrae email del usuario autenticado (mockeable) |
| `refreshToken(Professional)` | Refresca access_token usando refresh_token |
| `revokeToken(Professional)` | Revoca token y desconecta |
| `listEvents(Professional, Carbon $start, Carbon $end)` | Lista eventos de Google Calendar en rango |
| `isSlotAvailable(Professional, Carbon $start, Carbon $end)` | Verifica overlap contra eventos existentes |
| `availableSlots(Professional, Carbon $date, int $duration, ?$dayStart, ?$dayEnd)` | Genera slots libres con step de 30min |

---

## Fase 3 — UI OAuth en Filament ✅

**Archivos:**
- `app/Http/Controllers/GoogleCalendarAuthController.php` — callback OAuth
- `routes/web.php` — ruta `GET /auth/google/callback`
- `app/Filament/Pages/GoogleCalendarIntegration.php` — página Filament
- `resources/views/filament/pages/google-calendar-integration.blade.php`
- `tests/Feature/Http/GoogleCalendarAuthControllerTest.php`

**Qué hace:**
- Página en `/admin/integrations/google-calendar` bajo navegación **Configuración**
- Lista de doctores con cards: avatar, nombre, email, estado (Conectado/No conectado)
- Botón **Conectar** → redirige a Google OAuth consent screen
- Botón **Desconectar** → revoca token y limpia la cuenta
- Callback sin autenticación (Google redirige directamente)

---

## Fase 4 — Disponibilidad en Agendamiento ✅

**Archivos:**
- `app/Services/AppointmentAvailabilityService.php` — nuevo método `nextAvailableSlotsForDoctor()`
- `app/Filament/Resources/SocialComments/SocialCommentResource.php` — modal "Crear cita" con selector de doctor y display de disponibilidad
- `tests/Feature/Services/AppointmentAvailabilityServiceTest.php`

**Qué hace:**
- `nextAvailableSlotsForDoctor(Professional)` itera días clínicos, genera slots de `$duration` minutos, filtra contra appointments locales y Google Calendar
- En el modal de creación de cita: al seleccionar un doctor, se muestran los próximos slots libres (de ambos sistemas)
- El admin ve disponibilidad en tiempo real antes de elegir fecha

---

## Fase 5 — [PENDIENTE]

*Espacio para la siguiente fase.*

---

## Fase 6 — [PENDIENTE]

*Espacio para la siguiente fase.*
