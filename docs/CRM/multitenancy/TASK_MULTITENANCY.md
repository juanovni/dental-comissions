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
- [x] Definir host del admin global: `app.odon-crm.com`.
- [x] Definir patron de tenants: `{subdomain}.odon-crm.com`.
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
- [x] Confirmar defaults minimos: Clinic, dominio, admin inicial, clinic_user, permisos admin, settings base y storage prefix.
- [x] Confirmar defaults operativos: procedimientos base editables, settings de citas, settings CRM e integraciones not_configured.
- [x] Definir estados de tenant: `draft`, `provisioning`, `active`, `suspended`.

## Fase 0: Preparacion

- [ ] Aprobar el plan multi-tenant.
- [ ] Validar tabla final de modelos tenant-scoped.
- [ ] Validar tablas globales.
- [ ] Documentar decisiones de dominio/subdominio.
- [ ] Documentar estrategia Cloudflare: wildcard primero, API solo si aplica.
- [ ] Documentar estrategia de storage por `clinics/{clinic_id}/`.
- [ ] Crear checklist de pruebas de aislamiento.

## Fase 1: Base de tenancy

- [ ] Crear tabla `clinics`.
- [ ] Crear modelo `Clinic`.
- [ ] Agregar campos `slug`, `subdomain` y `primary_domain`.
- [ ] Agregar indices unicos para `slug`, `subdomain` y `primary_domain`.
- [ ] Crear tabla `clinic_user`.
- [ ] Asociar usuarios actuales a `Clinic #1`.
- [ ] Mantener `users.role` temporalmente para no romper el sistema actual.
- [ ] Crear defaults minimos para tenant nuevo: Clinic, dominio, admin inicial, clinic_user, permisos admin, settings base y storage prefix.
- [ ] Crear defaults operativos: procedimientos base editables, settings de citas, settings CRM e integraciones `not_configured`.
- [ ] Crear flujo de provisionamiento: crear tenant, crear admin, aplicar defaults, validar dominio, activar.

## Fase 2: Migraciones de datos

- [ ] Agregar `clinic_id nullable` a tablas tenant-scoped.
- [ ] Backfill de datos actuales a `Clinic #1`.
- [ ] Validar conteos por tabla antes y despues del backfill.
- [ ] Crear indices por `clinic_id`.
- [ ] Ajustar uniques globales a uniques por tenant cuando aplique.
- [ ] Preparar cambio futuro de `clinic_id` a `NOT NULL`.

## Fase 3: Contexto y scopes

- [ ] Crear `TenantContext`.
- [ ] Crear trait `BelongsToTenant`.
- [ ] Definir comportamiento fail-closed cuando no exista tenant.
- [ ] Activar scope primero en `Patient`, `Professional` y `Appointment`.
- [ ] Auditar usos de `withoutGlobalScopes()`.
- [ ] Agregar tests base de aislamiento por modelo.

## Fase 4: Dominio, host y Filament

- [ ] Crear middleware `ResolveClinicFromHost`.
- [ ] Resolver tenant por `primary_domain` o `subdomain`.
- [ ] Rechazar hosts no reconocidos sin devolver datos de ninguna clinica.
- [ ] Configurar Filament Tenancy con `Clinic::class`.
- [ ] Sincronizar tenant de Filament con `TenantContext`.
- [ ] Bloquear cambios de tenant incompatibles con el host actual.
- [ ] Crear o separar dashboard admin global para crear tenants.
- [ ] Adaptar resources principales de Filament.
- [ ] Revisar pages custom, widgets y selects.

## Fase 5: Integraciones publicas

- [ ] Adaptar rutas publicas para resolver tenant por host.
- [ ] Corregir check-in publico para filtrar por clinica.
- [ ] Adaptar webhook WhatsApp por `phone_number_id`.
- [ ] Agregar `clinic_id` a `whatsapp_messages`.
- [ ] Adaptar Meta webhook por page/account id.
- [ ] Adaptar OAuth Meta con `state` firmado por clinica.
- [ ] Adaptar Google Calendar OAuth con `state` firmado por clinica.
- [ ] Adaptar Telnyx/Pity Voice por numero destino.
- [ ] Scopear validaciones de doctores, pacientes, procedimientos y citas.

## Fase 6: Jobs, commands y cache

- [ ] Agregar `clinicId` a jobs tenant-scoped.
- [ ] Ejecutar jobs dentro de `TenantContext::run()`.
- [ ] Limpiar contexto al terminar jobs/workers.
- [ ] Adaptar commands para aceptar `--clinic=`.
- [ ] Si no hay `--clinic=`, iterar clinicas activas.
- [ ] Aislar errores por clinica en scheduled commands.
- [ ] Incluir `clinic_id` en cache keys tenant-scoped.
- [ ] Revisar broadcasting y notificaciones.

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

- [ ] Cambiar `clinic_id` a `NOT NULL` por grupos de tablas.
- [ ] Activar PostgreSQL RLS por grupos de tablas.
- [ ] Setear `app.current_clinic_id` en la conexion.
- [ ] Definir estrategia RLS para superadmin.
- [ ] Agregar tests especificos de RLS.
- [ ] Auditar queries con bypass.

## Pruebas minimas de aceptacion

- [ ] Usuario de Clinica A no lista pacientes de Clinica B.
- [ ] Usuario de Clinica A no lista citas de Clinica B.
- [ ] Selects de Filament no muestran doctores/procedimientos/pacientes de otra clinica.
- [ ] Host `clinica-a.dominio.com` resuelve Clinica A.
- [ ] Host no reconocido no devuelve datos de ninguna clinica.
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
