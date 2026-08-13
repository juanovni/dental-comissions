# Decisiones Multitenancy OdonCRM

## Decisiones confirmadas

- Dominio base de la plataforma: `odon-crm.com`.
- El dominio esta administrado en Cloudflare.
- Token Cloudflare expuesto fue rotado.
- Host del admin global de plataforma: `app.odon-crm.com`.
- En local se usara `localhost` como dominio base, por ejemplo `app.localhost:8080` y `demo.localhost:8080`.
- Cada tenant debe tener una entrada unica para acceder al sistema.
- Patron de tenants: `{subdomain}.odon-crm.com`.
- Patron local de tenants: `{subdomain}.localhost:8080`.
- El `subdomain` puede proponerse desde el `slug` ingresado al crear el tenant, pero debe guardarse como campo separado.
- El `subdomain` debe ser unico, estable y no deberia cambiar automaticamente si luego cambia el nombre o slug comercial de la clinica.
- Los roles operativos deben vivir en `clinic_user` y afectar solo a la clinica de esa relacion.
- Los permisos base pueden mantenerse como catalogo global reutilizable, salvo que despues se necesiten overrides por clinica.
- Las integraciones externas se configuran despues desde el panel tenant-scoped de la clinica.
- Solo usuarios con rol admin dentro de la clinica pueden configurar integraciones de esa clinica.
- El almacenamiento de archivos sera local en la primera version.
- Los archivos clinicos o sensibles deben ser privados por defecto, incluso usando storage local.
- Las descargas privadas internas deben pasar por controlador autorizado con validacion de tenant, usuario, rol y permisos.
- Las URLs firmadas deben usarse solo para compartir archivos temporalmente fuera del panel, con expiracion.
- No se soportaran dominios personalizados en la primera version.
- `procedures` sera tenant-scoped; cada clinica tendra sus propios procedimientos, precios y duraciones.
- El modulo de comisiones no se considera activo en OdonCRM y no debe formar parte de los defaults minimos del tenant.
- Las cuentas externas Meta/WhatsApp tendran propiedad unica global: una misma Page ID, Instagram Business Account ID o WhatsApp phone number ID no puede pertenecer a mas de una clinica.
- Defaults minimos para activar tenant: `Clinic`, dominio, admin inicial, `clinic_user`, permisos admin, settings base y storage prefix.
- Defaults operativos iniciales: procedimiento base `Consulta inicial`, settings de citas y settings CRM seguros.
- Las integraciones nacen como `not_configured` y se configuran despues desde el panel de la clinica.
- Estados del tenant: `draft`, `provisioning`, `active`, `suspended`, `provisioning_failed`.
- Al crear un tenant desde el admin global, el flujo normal inicia en `provisioning` y pasa automaticamente a `active` si todo sale bien.
- Si falla el alta del tenant, queda en `provisioning_failed` para revision o reintento desde el admin global.
- Solo tenants en estado `active` permiten acceso normal al panel de la clinica.
- Tenants en `suspended` conservan datos pero bloquean acceso normal de usuarios de la clinica.
- Al crear tenant, se puede crear un usuario admin nuevo o seleccionar un usuario existente, pero siempre debe quedar al menos un admin activo en `clinic_user`.
- El aislamiento principal de datos sera logico por `clinic_id` en una sola base PostgreSQL.
- Los archivos tenant-scoped deben guardarse bajo prefijo `clinics/{clinic_id}/`.
- La creacion de tenants debe hacerse desde un dashboard administrador global, no desde el panel de una clinica.

## Decisiones recomendadas pendientes de confirmacion

- Configurar wildcard DNS `*.odon-crm.com` apuntando a la aplicacion.
- No crear registros DNS individuales por tenant mientras exista wildcard DNS.
- En produccion, si `*.odon-crm.com` apunta a la app, crear un tenant con subdomain `dental` habilita automaticamente `dental.odon-crm.com` sin llamar a Cloudflare por tenant.
- Usar la API de Cloudflare solo en casos donde no se use wildcard.
- Activar un tenant solo cuando tenga dominio valido, al menos un admin activo y defaults minimos confirmados.

## Seguridad Cloudflare

- Cualquier token Cloudflare expuesto en conversacion, documento, log o repositorio debe considerarse comprometido y rotarse.
- El token actual debe tener permisos minimos sobre la zona `odon-crm.com`.
- Guardar el token solo en `.env` como `CLOUDFLARE_API_TOKEN` si finalmente se necesita.

## Defaults minimos por tenant

Cada tenant debe crearse con datos minimos para ser accesible y funcional:

- Registro en `clinics`.
- `slug`, `subdomain` y `primary_domain` unicos.
- `country`, `currency`, `timezone` y `status`.
- Usuario administrador inicial, creado nuevo o seleccionado de usuarios existentes.
- Relacion `clinic_user` activa.
- Rol `admin` y permisos iniciales.
- Configuracion base de clinica.
- Prefijo de storage logico `clinics/{clinic_id}/`.

Defaults operativos iniciales:

- Procedimiento base editable: `Consulta inicial`, precio `0`, duracion `30` minutos.
- Settings de citas seguros.
- Settings CRM seguros.
- Integraciones en estado `not_configured`.

No incluir en defaults iniciales:

- Comisiones.
- Dominios personalizados.
- Credenciales Meta, WhatsApp, Google Calendar o Telnyx.
- Datos clinicos o pacientes demo reales.

## Estados del tenant

Estados definidos:

- `draft`: tenant incompleto o guardado como borrador. No permite acceso normal.
- `provisioning`: tenant en proceso de creacion de dominio, admin inicial, settings, defaults y storage prefix. No permite acceso normal.
- `active`: tenant listo y accesible desde `{subdomain}.odon-crm.com`.
- `suspended`: tenant bloqueado temporalmente. Conserva datos, pero usuarios de la clinica no pueden acceder normalmente.
- `provisioning_failed`: fallo durante el alta del tenant. No permite acceso normal y requiere revision o reintento desde el admin global.

Flujo recomendado:

```text
draft -> provisioning -> active
provisioning -> provisioning_failed
provisioning_failed -> provisioning
active -> suspended
suspended -> active
```

## Settings de citas iniciales

- Horario laboral base: lunes a viernes, `09:00` a `17:00`.
- Intervalo de agenda: `30` minutos.
- Duracion default de cita: `30` minutos.

## Pendiente antes de implementar

- Confirmar wildcard DNS en Cloudflare.

## Ambientes

Configuracion local recomendada:

```env
APP_URL=http://app.localhost:8080
TENANCY_BASE_DOMAIN=localhost
TENANCY_ADMIN_DOMAIN=app.localhost
```

URLs locales:

```text
http://app.localhost:8080/admin
http://demo.localhost:8080/admin
http://clinica-a.localhost:8080/admin
```

Configuracion de produccion recomendada:

```env
APP_URL=https://app.odon-crm.com
TENANCY_BASE_DOMAIN=odon-crm.com
TENANCY_ADMIN_DOMAIN=app.odon-crm.com
```

URLs de produccion:

```text
https://app.odon-crm.com/admin
https://demo.odon-crm.com/admin
https://clinica-a.odon-crm.com/admin
```

Reglas:

- No hardcodear `odon-crm.com` en el codigo.
- Resolver tenant usando `TENANCY_BASE_DOMAIN` y `TENANCY_ADMIN_DOMAIN`.
- Normalizar el host del request removiendo el puerto antes de comparar, por ejemplo `demo.localhost:8080` -> `demo.localhost`.
- Guardar `subdomain` en base de datos como identificador estable.
- El dominio completo se deriva del ambiente cuando aplique.
