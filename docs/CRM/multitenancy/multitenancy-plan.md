# Plan de migracion multi-tenant para OdonCRM

## Objetivo

Convertir OdonCRM de una aplicacion Laravel + Filament para una sola clinica en una plataforma SaaS multi-clinica, usando una sola base de datos PostgreSQL con aislamiento logico fuerte por `clinic_id` y un dominio/subdominio unico por clinica como entrada tenant-aware. El dominio base confirmado para la plataforma es `odon-crm.com`.

La migracion debe ser incremental y debe mantener funcionando al cliente actual como `Clinic #1` durante todo el proceso.

## Conclusion tecnica

La arquitectura propuesta es viable:

```text
Laravel + Filament + PostgreSQL
Single Database
clinic_id por entidad tenant-scoped
Filament Tenancy para el panel
TenantContext propio para procesos fuera de Filament
Dominio/subdominio unico por clinica para entrada tenant-aware
Policies / Gates
PostgreSQL RLS como segunda barrera
```

No conviene hacer una conversion masiva en un solo cambio. El proyecto actual esta disenado como single-tenant: no existe `Clinic`, `clinic_id`, `TenantContext`, scopes tenant, tenancy de Filament ni integraciones por clinica.

El mayor riesgo no esta solo en las tablas. Tambien hay procesos fuera del panel que hoy operan globalmente:

- Webhooks de Meta.
- Webhooks de WhatsApp.
- Google Calendar OAuth y sync.
- Telnyx / Pity Voice.
- Jobs y queues.
- Scheduled commands.
- Links publicos.
- Resolucion de tenant por dominio/subdominio.
- Archivos e imagenes por clinica.
- Cache de settings.
- Dashboards y widgets Filament.

## Estado actual del proyecto

Archivos clave revisados:

- `app/Providers/Filament/AdminPanelProvider.php`: no usa `->tenant(Clinic::class)` ni middleware de tenant.
- `app/Models/User.php`: el usuario tiene `role` global y `professional_id`, pero no relacion con clinicas.
- `app/Models/Patient.php`: pacientes sin `clinic_id`.
- `app/Models/Appointment.php`: citas sin `clinic_id`.
- `app/Models/SocialAccount.php`: cuentas Meta/Facebook/Instagram globales.
- `app/Models/SocialCrmSetting.php`: settings globales y cache global.
- `app/Models/CalendarIntegration.php`: `clinicGoogle()` crea una unica integracion global.
- `config/services.php`: WhatsApp, Meta, Telnyx, Google OAuth y AI usan configuracion global.
- `routes/web.php`: webhooks, OAuth callbacks y links publicos no resuelven tenant.
- `app/Http/Controllers/PublicCheckInController.php`: recibe `{clinicSlug}`, pero no filtra citas por clinica.
- `app/Jobs/SendSocialCommentAutoReply.php`: el job transporta solo `socialCommentId`, no `clinicId`.

## Tablas que deben llevar clinic_id

Estas tablas contienen datos operativos, clinicos, comerciales, credenciales o eventos propios de una clinica.

### Clinica y operacion dental

- `professionals`
- `patients`
- `procedures`
- `doctor_assistant_assignments`

`procedures` deberia ser tenant-scoped porque procedimientos, duraciones, precios y reglas deben ser parametrizables por clinica.

### Citas y flujo de paciente

- `appointments`
- `appointment_events`
- `appointment_notes`
- `appointment_reminders`
- `appointment_check_in_attempts`
- `appointment_slot_offers`
- `appointment_slot_holds`

Aunque algunas tablas puedan inferir clinica por `appointment_id`, conviene guardar `clinic_id` directo para seguridad, consultas, indices y RLS.

### Social CRM / Meta / leads

- `social_accounts`
- `social_posts`
- `social_comments`
- `social_comment_actions`
- `social_identities`
- `social_lead_alerts`
- `social_link_events`
- `social_reply_templates`
- `social_moderation_rules`
- `social_crm_settings`

### WhatsApp

- `whatsapp_messages`

### Google Calendar

- `calendar_integrations`

### Voz / Pity Voice / Telnyx

- `voice_calls`
- `voice_events`

### Comisiones y pagos

Si estos modulos se mantienen o se reactivan, deben ser tenant-scoped:

- `commission_rules`
- `activity_records`
- `activity_assistants`
- `weekly_reports`
- `weekly_report_items`
- `payment_methods`
- `payment_method_commission_rates`

## Tablas globales

Estas tablas pueden ser globales:

- `clinics`
- `users`, si se usa pivot `clinic_user`.
- `clinic_user`
- `role_permissions`, si los permisos por rol son iguales para toda la plataforma.
- `jobs`
- `failed_jobs`
- `job_batches`
- `cache`
- `cache_locks`
- `sessions`
- `password_reset_tokens`

Tablas con decision pendiente:

- `local_language_patterns`: puede ser global, o tener `clinic_id nullable` para defaults globales y overrides por clinica.
- `role_permissions`: puede pasar a tenant-scoped si cada clinica puede personalizar permisos.

## Modelo Clinic

Crear `App\Models\Clinic` y tabla `clinics`.

Campos recomendados:

```text
id
name
slug
subdomain
primary_domain
country
currency
is_active
settings
created_at
updated_at
```

Reglas de dominio:

- Cada clinica debe tener una entrada unica para acceder al sistema.
- Para tenants internos de la plataforma, la entrada recomendada es un subdominio unico: `{subdomain}.dominio.com`.
- `subdomain` debe ser unico, estable, en minusculas y validado contra palabras reservadas como `www`, `app`, `admin`, `api`, `mail`, `support`, `static` y `assets`.
- `subdomain` puede proponerse desde `slug` al crear el tenant, pero debe guardarse separado porque el slug comercial puede cambiar.
- `primary_domain` debe guardar el host canonico de entrada, por ejemplo `clinica-a.dominio.com`.
- Si en el futuro se permiten dominios personalizados, conviene crear una tabla `clinic_domains` en vez de sobrecargar `clinics`.

Relaciones principales:

```php
public function users(): BelongsToMany
public function patients(): HasMany
public function appointments(): HasMany
public function professionals(): HasMany
public function socialAccounts(): HasMany
public function integrations(): HasMany
```

Tabla opcional futura para dominios personalizados:

```text
clinic_domains
id
clinic_id
domain
type
is_primary
verification_status
verified_at
created_at
updated_at
```

Para la primera version, no es obligatorio crear `clinic_domains` si solo se usaran subdominios propios bajo el dominio principal de la plataforma.

## Relacion User con Clinic

Recomendacion: usar pivot `clinic_user`, no `users.clinic_id`.

Motivos:

- Un usuario puede pertenecer a varias clinicas.
- Un superadmin puede operar globalmente.
- Un doctor o recepcionista podria trabajar en mas de una clinica.
- Los roles pueden ser diferentes por clinica.
- El rol operativo de un usuario debe salir de `clinic_user.role`, no de `users.role`, para que solo afecte a esa clinica.

Tabla propuesta:

```text
clinic_user
id
clinic_id
user_id
role
is_default
is_active
permissions
created_at
updated_at
```

Durante la migracion, `users.role` puede mantenerse para no romper el funcionamiento actual. Despues, el rol operativo deberia moverse progresivamente al pivot.

Regla de permisos:

- `clinic_user.role` define el rol del usuario dentro de una clinica especifica.
- Un mismo usuario puede ser admin en una clinica y recepcionista en otra.
- `role_permissions` puede mantenerse como catalogo global de permisos base por rol.
- Si en el futuro una clinica necesita personalizar permisos, agregar overrides tenant-scoped sin mezclar permisos entre clinicas.

## Filament Tenancy

Configurar tenancy en `app/Providers/Filament/AdminPanelProvider.php`:

```php
->tenant(Clinic::class)
```

El modelo `User` debe implementar el contrato de Filament para usuarios con tenants, con metodos conceptuales:

```php
public function getTenants(Panel $panel): Collection
public function canAccessTenant(Model $tenant): bool
```

Ademas, se debe crear middleware para sincronizar Filament con `TenantContext`:

```php
TenantContext::set(Filament::getTenant());
```

Esto es necesario porque Filament solo cubre el panel. OdonCRM tiene procesos fuera del panel que tambien deben conocer la clinica actual.

## TenantContext

Crear un servicio propio `TenantContext` para resolver y transportar la clinica actual en cualquier proceso.

API recomendada:

```php
TenantContext::set(Clinic $clinic): void;
TenantContext::get(): ?Clinic;
TenantContext::id(): ?int;
TenantContext::require(): Clinic;
TenantContext::clear(): void;
TenantContext::run(Clinic|int $clinic, Closure $callback): mixed;
```

Uso conceptual:

```php
TenantContext::run($clinicId, function () use ($commentId) {
    $comment = SocialComment::query()->findOrFail($commentId);

    app(SocialAutoReplyService::class)->handle($comment);
});
```

Reglas:

- Todo request tenant-scoped debe tener tenant.
- Todo webhook debe resolver tenant antes de procesar.
- Todo job tenant-scoped debe transportar `clinicId`.
- Todo command debe iterar clinicas activas o aceptar `--clinic=`.
- El contexto debe limpiarse al terminar el proceso para evitar contaminacion en workers.

## Resolucion de tenant por dominio

Cada tenant debe tener un host unico de entrada. El patron recomendado para la primera version es:

```text
{subdomain}.dominio.com
```

Ejemplos:

```text
clinica-a.dominio.com
clinica-b.dominio.com
demo.dominio.com
```

Middleware recomendado:

```text
ResolveClinicFromHost
```

Responsabilidades:

- Leer el host actual del request.
- Buscar la clinica activa por `primary_domain` o por `subdomain`.
- Rechazar hosts no reconocidos con `404` o una pagina controlada.
- Ejecutar `TenantContext::set($clinic)` antes de llegar a controladores, recursos o servicios tenant-scoped.
- No resolver tenant por parametros manipulables si el host ya define una clinica.
- Limpiar `TenantContext` al terminar el request.

Reglas:

- El dominio principal de administracion global es `app.odon-crm.com` y debe estar separado de los hosts tenant-scoped `{subdomain}.odon-crm.com`.
- Si se usa Filament Tenancy, el tenant de Filament debe sincronizarse con el tenant resuelto por host para evitar que un usuario cambie a una clinica distinta desde un host que no le corresponde.
- Un usuario solo puede acceder a un host tenant si pertenece a esa clinica o es superadmin.
- Links publicos deben incluir tenant por host y, si tambien usan slugs, deben validar que el recurso pertenece a la clinica del host.

Flujo de creacion de tenant:

1. Validar nombre y subdominio solicitado.
2. Crear registro en `clinics` con `subdomain` y `primary_domain`.
3. Crear relaciones iniciales en `clinic_user`.
4. Crear configuracion inicial por clinica.
5. Crear prefijos de almacenamiento logico si aplica.
6. Confirmar que el host resuelve hacia la aplicacion.

No se debe crear una clinica activa si su dominio de entrada no puede resolverse de forma confiable hacia la aplicacion.

## Cloudflare y DNS

Para subdominios propios de la plataforma, no conviene crear un registro DNS por cada tenant si Cloudflare permite usar wildcard DNS.

Configuracion recomendada una sola vez:

```text
*.dominio.com -> aplicacion
```

Con ese wildcard, cualquier tenant nuevo puede usar su subdominio sin llamar a la API de Cloudflare:

```text
clinica-a.dominio.com
clinica-b.dominio.com
```

La aplicacion resuelve el tenant desde el host y no desde el registro DNS individual.

La API de Cloudflare solo deberia usarse si:

- No se usara wildcard DNS.
- Se necesitan registros individuales por tenant por una razon operativa concreta.
- Se van a soportar dominios personalizados de clientes.
- Se automatizara verificacion DNS para dominios externos.

Reglas de seguridad para Cloudflare:

- El token de Cloudflare debe vivir solo en `.env`, por ejemplo `CLOUDFLARE_API_TOKEN`.
- Nunca debe guardarse en Git, documentacion, seeders, logs ni tickets.
- Debe tener permisos minimos: idealmente solo `Zone DNS Edit` sobre la zona especifica.
- Si un token fue pegado en una conversacion, documento o repositorio, debe considerarse comprometido y rotarse antes de usarlo.
- La creacion de DNS debe ser idempotente: si el registro ya existe, no debe fallar el flujo.

Si se decide crear registros individuales, el flujo debe ser asincrono y auditable:

```text
Tenant creado -> DNS pendiente -> Cloudflare crea registro -> DNS activo -> tenant activo
```

No bloquearia toda la creacion del tenant esperando propagacion DNS en tiempo real.

## Almacenamiento de archivos por tenant

Los archivos e imagenes deben separarse logicamente por clinica usando prefijos basados en `clinic_id`. En la primera version, el almacenamiento sera local.

Prefijo recomendado:

```text
clinics/{clinic_id}/
```

Ejemplos:

```text
clinics/1/patients/123/profile.jpg
clinics/1/appointments/456/consent.pdf
clinics/1/social/comments/999/attachment.png
clinics/2/patients/123/profile.jpg
```

No usar solo `slug` como prefijo principal porque puede cambiar. `clinic_id` es estable. Si se necesita legibilidad, guardar el slug en metadata, no depender de el para autorizacion.

Reglas:

- Todo upload tenant-scoped debe guardar archivos bajo `clinics/{clinic_id}/`.
- El path debe generarse desde `TenantContext`, no desde input del usuario.
- Archivos clinicos, consentimientos, imagenes de pacientes y adjuntos sensibles deben ser privados por defecto.
- El acceso interno debe pasar por controlador autorizado y policies.
- Las URLs temporales firmadas deben reservarse para comparticion temporal fuera del panel.
- No exponer archivos sensibles directamente desde `public/storage`.
- Evitar nombres originales si contienen datos del paciente.
- Incluir `clinic_id` en metadata si se usa S3 u otro object storage.
- Al borrar o desactivar una clinica, no eliminar archivos automaticamente sin una politica explicita de retencion.

Tests minimos de archivos:

- Usuario de Clinica A no puede descargar archivo de Clinica B.
- Un upload desde Clinica A se guarda bajo `clinics/{clinic_a_id}/`.
- Un path manipulado no permite escribir fuera del prefijo de la clinica actual.
- URLs firmadas vencidas no permiten acceso.
- Descargas internas validan usuario autenticado, clinica, rol y permiso antes de servir el archivo.

## Trait BelongsToTenant

Crear un trait para modelos con `clinic_id`.

Responsabilidades:

- Agregar global scope por `clinic_id`.
- Autoasignar `clinic_id` en `creating`.
- Exponer relacion `clinic()`.
- Permitir bypass solo en puntos controlados.

Ejemplo conceptual:

```php
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function ($query): void {
            $clinicId = app(TenantContext::class)->id();

            if ($clinicId) {
                $query->where($query->getModel()->getTable().'.clinic_id', $clinicId);
            }
        });

        static::creating(function ($model): void {
            $clinicId = app(TenantContext::class)->id();

            if (! $model->clinic_id && $clinicId) {
                $model->clinic_id = $clinicId;
            }
        });
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}
```

El comportamiento cuando no existe tenant debe definirse con cuidado. Para modelos tenant-scoped en runtime normal, es preferible fallar cerrado antes que devolver datos globales accidentalmente.

## Prevencion de fugas entre tenants

No depender solo de `where('clinic_id', ...)` manual.

Capas recomendadas:

```text
Request / webhook / job / command
Authentication
TenantContext
Global Scope
Policies / Gates
Validaciones scoped
Eloquent
PostgreSQL RLS
Database
```

Acciones concretas:

- Agregar `clinic_id` en entidades tenant-scoped.
- Aplicar `BelongsToTenant`.
- Filtrar todos los selects y relationship fields de Filament por tenant.
- Cambiar `exists:table,id` por reglas scoped al tenant.
- Incluir `clinic_id` en cache keys.
- Incluir `clinic_id` en jobs.
- Resolver tenant en webhooks por cuenta externa, numero o token.
- Usar policies en recursos y acciones.
- Activar PostgreSQL RLS al final, cuando la app ya pase tests de aislamiento.

## Policies y Gates

Crear o ajustar policies para:

- `ClinicPolicy`
- `PatientPolicy`
- `AppointmentPolicy`
- `ProfessionalPolicy`
- `ProcedurePolicy`
- `SocialAccountPolicy`
- `SocialCommentPolicy`
- `WhatsappMessagePolicy`
- `CalendarIntegrationPolicy`
- `VoiceCallPolicy`
- `UserPolicy`
- `IntegrationPolicy`
- `AuditLogPolicy`

Regla base:

```php
return $user->isSuperAdmin()
    || $user->clinics()->whereKey($model->clinic_id)->exists();
```

Ademas, validar permisos funcionales existentes como `appointments.view`, `appointments.update`, `integrations.update`, etc.

## PostgreSQL Row Level Security

RLS debe implementarse como segunda barrera, no como primera fase.

Patron conceptual:

```sql
ALTER TABLE patients ENABLE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation_patients ON patients
USING (clinic_id = current_setting('app.current_clinic_id')::bigint);
```

Laravel debe setear el tenant actual en la conexion:

```sql
SET LOCAL app.current_clinic_id = '1';
```

Consideraciones:

- Preferir `SET LOCAL` dentro de transacciones cuando sea posible.
- Asegurar contexto en workers y commands.
- Definir una estrategia para superadmin.
- No activar RLS hasta que `clinic_id`, scopes, policies y tests esten estables.
- Auditar consultas que usen `withoutGlobalScopes()`.

## Adaptacion de Meta

Problema actual:

- `MetaSocialWebhookController` procesa payload sin tenant.
- `MetaAuthController` guarda cuentas sociales globales.
- `SocialAccount` usa unique global por `platform` y `external_account_id`.

Cambios:

- Agregar `clinic_id` a `social_accounts`.
- Resolver tenant en webhooks por `page_id`, `instagram_business_account_id` o `external_account_id`.
- Procesar cada evento dentro de `TenantContext::run($clinicId, ...)`.
- Firmar `state` de OAuth con `clinic_id`, `user_id` y nonce.
- Guardar tokens Meta por clinica.
- Revisar unique: mantener global si una pagina solo puede conectarse una vez en toda la plataforma, o cambiar a `unique(clinic_id, platform, external_account_id)` si se permite repeticion controlada.

## Adaptacion de WhatsApp

Problema actual:

- `WhatsappService` usa `config('services.whatsapp.*')` global.
- `WebhookController` no resuelve tenant desde `metadata.phone_number_id`.
- `WhatsappMessage` busca mensajes y profesionales globalmente.

Cambios:

- Crear integracion WhatsApp por clinica.
- Resolver tenant por `phone_number_id` del payload.
- Guardar `clinic_id` en `whatsapp_messages`.
- Cambiar busquedas por telefono para que sean scoped.
- Cambiar unique de `message_sid` a `unique(clinic_id, message_sid)` o mantener global si Meta garantiza unicidad universal.

## Adaptacion de Google Calendar

Problema actual:

- `CalendarIntegration::clinicGoogle()` usa una sola fila global.
- OAuth callback usa `state === 'clinic'`.

Cambios:

- Agregar `clinic_id` a `calendar_integrations`.
- Cambiar unique `provider` por `unique(clinic_id, provider)`.
- `GoogleCalendarService::clinicIntegration()` debe usar `TenantContext`.
- OAuth `state` debe estar firmado con `clinic_id`.
- Tokens de doctores quedan protegidos cuando `professionals` tenga `clinic_id`.

## Adaptacion de Pity Voice / Telnyx

Problema actual:

- Telnyx usa configuracion global.
- `TelnyxVoiceWebhookController` no resuelve tenant por numero destino.
- `VoiceToolService` valida `doctor_id` y `procedure_id` globalmente.

Cambios:

- Mapear numero destino Telnyx a clinica.
- Agregar `clinic_id` a `voice_calls` y `voice_events`.
- Procesar eventos dentro de `TenantContext`.
- Scopear validaciones de doctores, procedimientos, pacientes y citas.
- El token de herramientas de voz debe ser por clinica o incluir `clinic_id` firmado.

## Jobs y queues

Todo job tenant-scoped debe transportar `clinicId`.

Ejemplo para `SendSocialCommentAutoReply`:

```php
public function __construct(
    public int $clinicId,
    public int $socialCommentId,
) {}

public function handle(SocialAutoReplyService $service): void
{
    TenantContext::run($this->clinicId, function () use ($service): void {
        $comment = SocialComment::query()->find($this->socialCommentId);

        if ($comment) {
            $service->handle($comment);
        }
    });
}
```

No confiar en IDs globales dentro de jobs.

## Scheduled jobs y commands

Commands actuales que deben adaptarse:

- `appointments:send-reminders`
- `social:sync-comments`
- `social:sync-accounts`
- `social:classify-comments`
- `social:lead-alerts`
- `social:roi-leakage-report`

Patron recomendado:

```php
protected $signature = 'appointments:send-reminders {--clinic=}';
```

Si `--clinic` existe, procesar solo esa clinica. Si no existe, iterar todas las clinicas activas:

```php
Clinic::query()->where('is_active', true)->each(function (Clinic $clinic): void {
    TenantContext::run($clinic, fn () => app(AppointmentReminderService::class)->run());
});
```

Un error en una clinica no debe detener el procesamiento de las demas.

## Integraciones y credenciales por clinica

Crear tabla generica:

```text
clinic_integrations
id
clinic_id
provider
external_account_id
access_token
refresh_token
token_expires_at
settings
status
metadata
created_at
updated_at
```

Proveedores iniciales:

- `meta`
- `whatsapp`
- `google_calendar`
- `telnyx`
- `pity_voice`
- `gemini`, opcional.
- `openai`, opcional.

Puede mantenerse global en `.env`:

- Meta App ID / App Secret.
- Google OAuth Client ID / Secret.
- Gemini/OpenAI API key si la plataforma paga centralmente.
- Telnyx API key si la plataforma usa una sola cuenta.

Debe ser por clinica:

- Page tokens.
- Page IDs.
- Instagram Business Account IDs.
- WhatsApp phone number IDs.
- WhatsApp business phone.
- Google Calendar tokens.
- Telnyx numbers.
- Voice settings por clinica.

Regla operativa:

- Las integraciones no son parte obligatoria del alta inicial del tenant.
- Se configuran despues desde el panel tenant-scoped de cada clinica.
- Solo usuarios con rol admin en `clinic_user` para esa clinica pueden crear, actualizar o desconectar integraciones.
- Un admin de Clinica A no puede ver ni modificar integraciones de Clinica B.

## Auditoria

Crear tabla `audit_logs`:

```text
id
clinic_id
user_id
resource_type
resource_id
ip_address
user_agent
metadata
created_at
```

Eventos prioritarios:

- `tenant.created`
- `tenant.domain.updated`
- `tenant.domain.verified`
- `tenant.switch`
- `patient.view`
- `patient.update`
- `appointment.update`
- `clinical_record.view`
- `clinical_record.update`
- `integration.update`
- `user.permission.update`
- `webhook.processed`

Regla importante: no guardar informacion clinica sensible completa dentro de logs. Usar IDs, tipos de accion y metadata minima.

## Migracion del cliente actual a Clinic #1

Pasos seguros:

1. Crear tabla `clinics`.
2. Crear `Clinic #1` para el cliente actual.
3. Asignar `slug`, `subdomain` y `primary_domain` a `Clinic #1`.
4. Confirmar que el dominio/subdominio de `Clinic #1` resuelve hacia la aplicacion.
5. Crear `clinic_user` y asociar usuarios actuales a `Clinic #1`.
6. Agregar `clinic_id nullable` a tablas tenant-scoped.
7. Poblar registros existentes con `clinic_id = 1`.
8. Validar conteos por tabla.
9. Crear indices por `clinic_id`.
10. Ajustar uniques globales.
11. Activar `BelongsToTenant` por grupos de modelos.
12. Configurar Filament Tenancy.
13. Adaptar webhooks, jobs y commands.
14. Cambiar `clinic_id` a `NOT NULL`.
15. Activar RLS.

Ejemplos de backfill:

```sql
UPDATE patients SET clinic_id = 1 WHERE clinic_id IS NULL;
UPDATE professionals SET clinic_id = 1 WHERE clinic_id IS NULL;
UPDATE appointments SET clinic_id = 1 WHERE clinic_id IS NULL;
UPDATE social_accounts SET clinic_id = 1 WHERE clinic_id IS NULL;
UPDATE social_comments SET clinic_id = 1 WHERE clinic_id IS NULL;
UPDATE whatsapp_messages SET clinic_id = 1 WHERE clinic_id IS NULL;
```

## Indices y uniques a revisar

Uniques globales actuales que requieren atencion:

- `clinics.slug`.
- `clinics.subdomain`.
- `clinics.primary_domain`.
- `professionals.whatsapp_phone`.
- `social_accounts(platform, external_account_id)`.
- `social_comments(platform, external_comment_id)`.
- `social_identities(platform, platform_user_id)`.
- `social_crm_settings.key`.
- `calendar_integrations.provider`.

Reemplazos probables:

```text
clinics: unique(slug)
clinics: unique(subdomain)
clinics: unique(primary_domain)
professionals: unique(clinic_id, whatsapp_phone)
social_comments: unique(clinic_id, platform, external_comment_id)
social_identities: unique(clinic_id, platform, platform_user_id)
social_crm_settings: unique(clinic_id, key)
calendar_integrations: unique(clinic_id, provider)
```

Para `social_accounts`, definir regla de producto:

- Unique global si una cuenta Meta no puede conectarse a mas de una clinica.
- Unique por clinica si se permite repetir cuenta bajo condiciones controladas.

## Pruebas automatizadas

Tests minimos de aislamiento:

- Usuario de Clinica A no lista pacientes de Clinica B.
- Usuario de Clinica A no lista citas de Clinica B.
- Selects de Filament no muestran doctores/procedimientos/pacientes de otra clinica.
- Check-in publico con slug de Clinica A no encuentra cita de Clinica B.
- Webhook WhatsApp con `phone_number_id` de Clinica A crea mensaje en Clinica A.
- Webhook Meta con `page_id` de Clinica B crea comentario en Clinica B.
- Job con `clinicId A` no procesa comentario de Clinica B.
- Google Calendar de Clinica A no usa tokens de Clinica B.
- Voice call al numero de Clinica A no crea cita en Clinica B.
- Superadmin puede cambiar tenant; admin normal no puede acceder a tenants no asignados.
- Host `clinica-a.dominio.com` resuelve Clinica A y no permite acceder recursos de Clinica B.
- Host no reconocido no devuelve datos de ninguna clinica.
- Tenant nuevo requiere `subdomain` y `primary_domain` unicos.
- Upload de Clinica A se guarda bajo `clinics/{clinic_a_id}/`.
- Usuario de Clinica A no puede descargar archivos de Clinica B.

## Riesgos principales

- Activar scopes antes de resolver tenant en webhooks rompe procesos externos.
- Activar RLS demasiado pronto puede bloquear jobs, commands y OAuth callbacks.
- Cache global de settings puede mezclar configuracion entre clinicas.
- `PublicCheckInController` es critico porque el slug no filtra citas actualmente.
- OAuth Google y Meta deben cambiar `state` antes de operar multi-clinica.
- Jobs actuales no llevan tenant.
- Uniques globales pueden impedir datos validos por clinica.
- `withoutGlobalScopes()` puede convertirse en fuga si no se audita.
- Usuarios con rol global pueden tener permisos excesivos si no se migra a rol por clinica.
- Resolver tenant por host sin validar membresia del usuario puede permitir acceso cruzado.
- Permitir cambiar tenant en Filament sin respetar el host actual puede mezclar contexto.
- Crear DNS por API de Cloudflare en tiempo real puede fallar por propagacion o limites de API.
- Guardar archivos en rutas publicas o sin prefijo `clinic_id` puede exponer datos de otra clinica.
- Usar `slug` como prefijo principal de archivos complica cambios de nombre y migraciones.

## Orden de implementacion recomendado

### Fase 0: Preparacion

- Aprobar este plan.
- Definir dominio base de la plataforma, por ejemplo `dominio.com`.
- Usar `app.odon-crm.com` para el admin global y `{subdomain}.odon-crm.com` para tenants.
- Configurar wildcard DNS `*.dominio.com` si se confirma la estrategia de subdominios.
- Definir si `procedures` y `role_permissions` seran globales o por clinica.
- Definir si una cuenta Meta/WhatsApp puede pertenecer a mas de una clinica.

### Fase 1: Base de tenancy

- Crear `clinics`.
- Crear `clinic_user`.
- Crear modelo `Clinic`.
- Agregar `slug`, `subdomain` y `primary_domain` unicos a `clinics`.
- Asociar usuarios actuales a `Clinic #1`.
- Mantener `users.role` temporalmente.

### Fase 2: Migraciones de datos

- Agregar `clinic_id nullable` a tablas tenant-scoped.
- Backfill a `Clinic #1`.
- Crear indices por `clinic_id`.
- Ajustar uniques globales.

### Fase 3: Contexto y scopes

- Crear `TenantContext`.
- Crear `BelongsToTenant`.
- Activar primero en modelos criticos: `Patient`, `Professional`, `Appointment`.
- Agregar tests base de aislamiento.

### Fase 4: Filament

- Configurar `->tenant(Clinic::class)`.
- Sincronizar Filament tenant con `TenantContext`.
- Resolver tenant por host antes de entrar al panel tenant-scoped.
- Bloquear cambios de tenant incompatibles con el host actual.
- Adaptar resources principales.
- Revisar pages custom, widgets y selects.

### Fase 5: Integraciones publicas

- Adaptar rutas publicas para resolver tenant por host.
- Adaptar WhatsApp webhook.
- Adaptar Meta webhook y OAuth.
- Adaptar Google Calendar OAuth y sync.
- Adaptar Pity Voice / Telnyx.
- Adaptar links publicos y check-in.

### Fase 6: Jobs, commands y cache

- Agregar `clinicId` a jobs.
- Iterar clinicas en commands.
- Cambiar cache keys a tenant-aware.
- Revisar broadcasting/notificaciones.

### Fase 7: Auditoria

- Crear `audit_logs`.
- Registrar acciones sensibles.
- Registrar eventos `tenant.created`, `tenant.domain.updated`, `tenant.domain.verified` y `tenant.switch`.
- Evitar datos clinicos sensibles en metadata.

### Fase 7.5: Archivos tenant-aware

- Definir discos privados y publicos.
- Migrar uploads existentes a prefijos `clinics/{clinic_id}/` si existen archivos previos.
- Crear helpers o servicios para generar paths desde `TenantContext`.
- Proteger descargas con policies o URLs firmadas.
- Agregar tests de aislamiento de archivos.

### Fase 8: Endurecimiento

- Cambiar `clinic_id` a `NOT NULL`.
- Activar PostgreSQL RLS por grupos de tablas.
- Agregar tests especificos de RLS.
- Auditar queries con bypass.

## Paquetes externos

No recomiendo empezar con `stancl/tenancy`.

Razon:

- El plan actual es single database con `clinic_id`.
- OdonCRM tiene muchos procesos propios fuera de HTTP.
- Stancl agrega complejidad orientada a escenarios con separacion mas fuerte, dominios, bootstrap y bases por tenant.

Recomendacion inicial:

- Usar Filament Tenancy para el panel.
- Implementar `TenantContext` propio.
- Usar `clinic_id`, global scopes, policies y RLS.

`spatie/laravel-multitenancy` podria evaluarse despues si el manejo propio de tenant en jobs, cache y commands crece demasiado. No es obligatorio para la primera version.

## Decision recomendada

Avanzar con una migracion incremental basada en:

```text
Single Database
clinic_id
Dominio/subdominio unico por clinica
Filament Tenancy
TenantContext propio
BelongsToTenant
Policies
Integraciones por clinica
Storage con prefijo clinics/{clinic_id}
Auditoria
PostgreSQL RLS al final
```

El primer entregable de desarrollo deberia ser pequeno y reversible: crear `Clinic`, `clinic_user`, asociar el cliente actual como `Clinic #1`, y agregar `TenantContext` sin activar todavia RLS ni scopes en todo el sistema.
