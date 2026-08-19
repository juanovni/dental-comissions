# TASK Multitenancy OdonCRM

## Estado general

Objetivo: convertir OdonCRM en una plataforma SaaS multi-clinica con una sola base PostgreSQL, aislamiento logico por `clinic_id`, dominio/subdominio unico por clinica, storage tenant-aware y RLS como segunda barrera.

Documento base: `docs/CRM/multitenancy/multitenancy-plan.md`

Decisiones: `docs/CRM/multitenancy/DECISIONS.md`

Estados sugeridos:

- `[ ]` Pendiente
- `[~]` En progreso
- `[x]` Completado
- `[!]` Bloqueado o requiere decision

## Decisiones pendientes antes de implementar

- [x] Definir dominio base de la plataforma: `odon-crm.com`.
- [x] Definir dominio base local: `localhost` con URLs como `demo.localhost:8080`.
- [x] Definir host del admin global: `app.odon-crm.com`.
- [x] Definir patron de tenants: `{subdomain}.odon-crm.com`.
- [x] Definir patron local de tenants: `{subdomain}.localhost:8080`.
- [ ] Configurar o confirmar wildcard DNS `*.odon-crm.com`.
- [x] Rotar cualquier token Cloudflare expuesto antes de usarlo.
- [x] Definir roles/permisos: rol operativo por `clinic_user`; permisos base globales reutilizables.
- [x] Confirmar que `procedures` sera tenant-scoped con precios y duraciones por clinica.
- [x] Definir cuentas Meta/WhatsApp: propiedad unica global, no pueden pertenecer a mas de una clinica.
- [x] Confirmar integraciones: se configuran despues desde el panel de la clinica por un admin de esa clinica.
- [x] Definir storage: local en primera version.
- [x] Definir politica de descargas privadas: controlador autorizado para uso interno y URLs firmadas para comparticion temporal.
- [x] Confirmar dominios personalizados: no se soportan en la primera version.
- [x] Confirmar comisiones: modulo no activo en OdonCRM, fuera de defaults minimos.
- [x] Excluir formalmente comisiones del alcance activo de Fase 2.
- [x] Definir `local_language_patterns`: `clinic_id nullable` para global + overrides por clinica. (Corregido: migración `2026_08_18_160917` agrega `clinic_id` FK + unique constraint. Patrones de sistema tienen `clinic_id = NULL` intencionalmente. Scope `forCurrentTenantWithGlobal()` retorna sistema + clínica actual.)
- [x] Confirmar defaults minimos: Clinic, dominio, admin inicial, clinic_user, permisos admin, settings base y storage prefix.
- [x] Confirmar defaults operativos: Consulta inicial precio 0 duracion 30, settings de citas, settings CRM e integraciones not_configured.
- [x] Definir estados de tenant: `draft`, `provisioning`, `active`, `suspended`, `provisioning_failed`.
- [x] Definir flujo de activacion: crear en `provisioning`, pasar a `active` si todo sale bien, o `provisioning_failed` si falla.
- [x] Definir admin inicial: crear usuario nuevo o seleccionar existente, exigiendo minimo un admin activo por clinica.
- [x] Definir horario base: lunes a viernes 09:00-17:00, intervalo 30 minutos.
- [x] Definir Settings CRM: auto-respuestas off, IA revision/manual, alertas off, plantillas editables; admin puede activar/desactivar automatico.

## Fase 0: Preparacion

- [ ] Aprobar el plan multi-tenant.
- [ ] Validar tabla final de modelos tenant-scoped.
- [ ] Validar tablas globales.
- [ ] Documentar decisiones de dominio/subdominio.
- [x] Agregar configuracion `TENANCY_BASE_DOMAIN` y `TENANCY_ADMIN_DOMAIN` por ambiente.
- [ ] Documentar estrategia Cloudflare: wildcard primero, API solo si aplica.
- [ ] Documentar estrategia de storage por `clinics/{clinic_id}/`.
- [ ] Crear checklist de pruebas de aislamiento.

## Fase 1: Base de tenancy

- [x] Crear tabla `clinics`.
- [x] Crear modelo `Clinic`.
- [x] Agregar campos `slug`, `subdomain` y `primary_domain`.
- [x] Agregar indices unicos para `slug`, `subdomain` y `primary_domain`.
- [x] Crear tabla `clinic_user`.
- [x] Asociar usuarios actuales a `Clinic #1`.
- [x] Mantener `users.role` temporalmente para no romper el sistema actual.
- [x] Crear defaults minimos para tenant nuevo: Clinic, dominio, al menos un admin activo, clinic_user, permisos admin, settings base y storage prefix.
- [x] Crear defaults operativos iniciales en `clinic.settings`: Consulta inicial precio 0 duracion 30, settings de citas, settings CRM e integraciones `not_configured`.
- [x] Crear flujo de provisionamiento: crear tenant en `provisioning`, crear/asignar admin, aplicar defaults, activar o marcar `provisioning_failed`.

## Fase 2: Migraciones de datos

- [x] Agregar `clinic_id nullable` a tablas tenant-scoped activas.
- [x] Backfill de datos actuales a `Clinic #1` en tablas activas.
- [x] Validar conteos por tabla antes y despues del backfill.
- [x] Crear indices por `clinic_id`.
- [x] Ajustar uniques globales a uniques por tenant cuando aplique.
- [x] Preparar cambio futuro de `clinic_id` a `NOT NULL` en tablas activas tenant-scoped. (Completado en Fase 8: migración `2026_08_18_165916`).

## Fase 3: Contexto y scopes

- [x] Crear `TenantContext`.
- [x] Crear trait `BelongsToTenant`.
- [x] Definir comportamiento fail-closed cuando no exista tenant en el panel tenant.
- [x] Preparar scope inicial en `Patient`, `Professional`, `Appointment` y `Procedure` sin activarlo globalmente todavia.
- [x] Crear middleware inicial de resolucion de tenant por request/host.
- [x] Activar resolucion tenant solo en rutas publicas controladas.
- [x] Auditar usos de `withoutGlobalScopes()`.
- [x] Agregar tests base de aislamiento por modelo.

## Fase 4: Dominio, host y Filament

- [x] Crear middleware `ResolveClinicFromHost`.
- [x] Resolver tenant por `primary_domain` o `subdomain`.
- [x] Rechazar mismatch host/tenant en rutas tenant-aware controladas.
- [x] Rechazar hosts no reconocidos sin devolver datos de ninguna clinica.
- [x] Configurar Filament Tenancy con `Clinic::class`.
- [x] Sincronizar tenant de Filament con `TenantContext`.
- [x] Bloquear cambios de tenant incompatibles con el host actual.
- [x] Crear o separar dashboard admin global para crear tenants.
- [x] Adaptar resources principales de Filament.
- [x] Revisar pages custom, widgets y selects.
- [x] Soportar host-based local real para panel global y panel tenant.

## Fase 4.5: Aislamiento del panel tenant

- [x] Aplicar scoping tenant en resources del panel tenant base.
- [x] Aplicar scoping tenant en pages custom clave del panel tenant.
- [x] Revisar widgets tenant base para que filtren por clinica actual de forma consistente.
- [x] Corregir `ViewSocialCommentTest` y su vista si hace falta.
- [x] Seguir con mas pages/widgets tenant que aun tengan queries manuales sin filtrar, especialmente social/ROI.
- [x] Agregar tests focalizados de aislamiento del panel tenant por recurso/pagina.

## Fase 5: Integraciones publicas

- [x] Adaptar rutas publicas para resolver tenant por host.
- [x] Corregir check-in publico para filtrar por clinica.
- [x] Adaptar webhook WhatsApp por `phone_number_id`.
- [x] Agregar `clinic_id` a `whatsapp_messages`.
- [x] Adaptar Meta webhook por page/account id.
- [x] Adaptar OAuth Meta con `state` firmado por clinica.
- [x] Adaptar Google Calendar OAuth con `state` firmado por clinica.
- [x] Adaptar Telnyx/Pity Voice por numero destino.
- [x] Scopear validaciones de doctores, pacientes, procedimientos y citas en integraciones publicas principales.

## Fase 6: Jobs, commands y cache

- [x] Agregar `clinicId` a jobs tenant-scoped.
- [x] Ejecutar jobs dentro de `TenantContext::run()`.
- [x] Limpiar contexto al terminar jobs/workers.
- [x] Adaptar commands para aceptar `--clinic=`.
- [x] Si no hay `--clinic=`, iterar clinicas activas.
- [x] Aislar errores por clinica en scheduled commands.
- [x] Incluir `clinic_id` en cache keys tenant-scoped.
- [x] Revisar broadcasting y notificaciones.

## Fase 7: Auditoria

- [ ] Crear tabla `audit_logs`.
- [ ] Registrar acciones sensibles de pacientes, citas e integraciones.
- [ ] Registrar `tenant.created`.
- [ ] Registrar `tenant.domain.updated`.
- [ ] Registrar `tenant.domain.verified`.
- [ ] Registrar `tenant.switch`.
- [ ] Evitar guardar informacion clinica sensible completa en metadata.

## Fase 7.5: Archivos tenant-aware

- [ ] Definir discos privados y publicos.
- [ ] Crear helper o servicio para paths tenant-aware.
- [ ] Guardar uploads bajo `clinics/{clinic_id}/`.
- [ ] Evitar paths generados desde input del usuario.
- [ ] Proteger descargas internas con controlador autorizado y policies.
- [ ] Crear URLs firmadas con expiracion solo para comparticion temporal.
- [ ] Migrar archivos existentes si existen uploads previos.
- [ ] Agregar tests de aislamiento de archivos.

## Fase 8: Endurecimiento

- [x] Cambiar `clinic_id` a `NOT NULL` por grupos de tablas (migración `2026_08_18_165916`, guard: salta si clinics=0 para tests).
- [x] Activar PostgreSQL RLS por grupos de tablas (migración `2026_08_18_172326`, 25 tablas, función `current_clinic_id()` + políticas `tenant_isolation_*`).
- [x] Setear `app.current_clinic_id` en la conexión (middleware `SetRLSContext` en panel Filament + `RunsForEachClinic` para commands).
- [x] Definir estrategia RLS para superadmin: crear roles PostgreSQL `dental_app` (no-superuser, RLS enforced) y `dental_bypass` (BYPASSRLS).
- [x] Crear artisan command `app:set-current-clinic {clinic?} [--clear]` para setear contexto RLS en jobs/commands.
- [x] Agregar tests específicos de RLS (`tests/pgsql/RowLevelSecurityTest.php`, 9 tests, ejecutar con `vendor/bin/phpunit --configuration=phpunit.xml.pgsql`).
- [ ] Auditar queries con bypass.

### Notas de implementación Fase 8

- **RLS**: solo se aplica en PostgreSQL (`DB::getDriverName() === 'pgsql'`). En SQLite se ignora.
- **Superuser bypass**: el usuario `dental` (superuser de PostgreSQL en Docker) bypasea RLS automáticamente. Para que RLS funcione, la app debe conectarse como `dental_app` o usar `SET ROLE dental_app` en cada request.
- **Middleware SetRLSContext**: registrado en `tenantMiddleware` del panel Filament. Usa `SET app.current_clinic_id` (session-level) y limpia al finalizar el request.
- **Roles**: `dental_app` (LOGIN, NO superuser, NO BYPASSRLS) y `dental_bypass` (LOGIN, NO superuser, BYPASSRLS). Creados por migración `2026_08_18_173439`.
- **Tests RLS**: usan `phpunit.xml.pgsql` contra base `dental_commissions_mvp_testing`. `RefreshDatabase` + setup manual de RLS + `SET ROLE dental_app` para simular usuario no-superuser.

## Pruebas minimas de aceptacion

- [ ] Usuario de Clinica A no lista pacientes de Clinica B.
- [ ] Usuario de Clinica A no lista citas de Clinica B.
- [ ] Selects de Filament no muestran doctores/procedimientos/pacientes de otra clinica.
- [ ] Host `clinica-a.dominio.com` resuelve Clinica A.
- [x] Host no reconocido no devuelve datos de ninguna clinica.
- [ ] Usuario no asignado no puede acceder al host de una clinica.
- [ ] Tenant nuevo requiere `subdomain` y `primary_domain` unicos.
- [ ] Check-in publico con Clinica A no encuentra cita de Clinica B.
- [ ] Webhook WhatsApp crea mensajes en la clinica correcta.
- [ ] Webhook Meta crea comentarios en la clinica correcta.
- [ ] Job con `clinicId A` no procesa datos de Clinica B.
- [ ] Google Calendar de Clinica A no usa tokens de Clinica B.
- [ ] Voice call al numero de Clinica A no crea cita en Clinica B.
- [ ] Upload de Clinica A se guarda bajo `clinics/{clinic_a_id}/`.
- [ ] Usuario de Clinica A no descarga archivos de Clinica B.

## Notas operativas

- No activar RLS antes de tener `clinic_id`, scopes, policies y tests estables.
- No crear tenants activos si el dominio/subdominio no resuelve hacia la aplicacion.
- Preferir wildcard DNS antes que crear registros Cloudflare por cada tenant.
- No guardar tokens Cloudflare en documentacion ni repositorio.
- La creacion de tenants debe vivir en un dashboard global de plataforma, no dentro del panel tenant-scoped.
