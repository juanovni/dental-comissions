# Decisiones Multitenancy OdonCRM

## Decisiones confirmadas

- Dominio base de la plataforma: `odon-crm.com`.
- El dominio esta administrado en Cloudflare.
- Host del admin global de plataforma: `app.odon-crm.com`.
- Cada tenant debe tener una entrada unica para acceder al sistema.
- Patron de tenants: `{subdomain}.odon-crm.com`.
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
- El aislamiento principal de datos sera logico por `clinic_id` en una sola base PostgreSQL.
- Los archivos tenant-scoped deben guardarse bajo prefijo `clinics/{clinic_id}/`.
- La creacion de tenants debe hacerse desde un dashboard administrador global, no desde el panel de una clinica.

## Decisiones recomendadas pendientes de confirmacion

- Configurar wildcard DNS `*.odon-crm.com` apuntando a la aplicacion.
- No crear registros DNS individuales por tenant mientras exista wildcard DNS.
- Usar la API de Cloudflare solo para dominios personalizados o casos donde no se use wildcard.
- No soportar dominios personalizados en la primera version.
- Usar estados de tenant: `draft`, `provisioning`, `active`, `suspended`.
- Activar un tenant solo cuando tenga dominio valido, admin inicial y defaults minimos.

## Seguridad Cloudflare

- Cualquier token Cloudflare expuesto en conversacion, documento, log o repositorio debe considerarse comprometido.
- Antes de usar Cloudflare API, crear un token nuevo con permisos minimos sobre la zona `odon-crm.com`.
- Guardar el token solo en `.env` como `CLOUDFLARE_API_TOKEN` si finalmente se necesita.

## Defaults minimos por tenant

Cada tenant debe crearse con datos minimos para ser accesible y funcional:

- Registro en `clinics`.
- `slug`, `subdomain` y `primary_domain` unicos.
- Usuario administrador inicial.
- Relacion `clinic_user` activa.
- Rol/permisos iniciales.
- Configuracion base de clinica.
- Defaults operativos requeridos por los modulos activos.
- Prefijo de storage logico `clinics/{clinic_id}/`.

## Pendiente antes de implementar

- Confirmar wildcard DNS en Cloudflare.
- Confirmar lista exacta de defaults operativos por tenant.
