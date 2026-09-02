# Onboarding Y Provisionamiento Del Tenant

## Objetivo

Definir la creacion y puesta en marcha de una clinica sin convertir el alta en un formulario extenso ni bloquear el circuito clinico por configuraciones fiscales o integraciones opcionales.

La clinica es el tenant y la unidad operativa. Este flujo no introduce sucursales.

## Principios

1. El alta inicial solicita solo la informacion necesaria para crear un tenant seguro y permitir el primer acceso.
2. Los valores tecnicos derivados no se capturan como datos independientes.
3. El estado del tenant, el progreso del onboarding y la disponibilidad funcional son conceptos distintos.
4. Cada modulo se habilita cuando sus propias dependencias estan completas.
5. La facturacion electronica SRI no forma parte del alcance inicial y no bloquea el flujo clinico-comercial.
6. Toda configuracion tenant-scoped se crea de manera idempotente y auditable.
7. Las credenciales e invitaciones nunca utilizan contrasenas conocidas o predeterminadas.

## Alta Minima

### Datos solicitados

```text
Clinica
- Nombre comercial
- Pais
- Moneda
- Locale
- Zona horaria
- Subdominio

Administrador inicial
- Usuario existente o usuario nuevo
- Nombre, si es nuevo
- Correo, si es nuevo
```

Para Ecuador se sugieren:

```text
country_code: EC
currency_code: USD
locale: es-EC
timezone: America/Guayaquil
```

El pais, moneda y zona horaria se muestran para confirmacion. El locale puede conservar el valor sugerido y modificarse si la clinica lo requiere.

### Datos derivados

```text
slug <- nombre comercial
subdomain <- slug, con validacion y posibilidad de editar
primary_domain <- subdomain + dominio base
storage_prefix <- identificador inmutable del tenant
status <- administrado por el proceso de provisionamiento
```

El formulario muestra la URL resultante como vista previa. No solicita `slug`, `subdomain` y `primary_domain` como tres decisiones separadas.

### Datos que no se solicitan durante el alta

- Razon social y RUC.
- Configuracion SRI.
- Certificado de firma.
- Establecimiento o punto de emision fiscal.
- Catalogo completo de procedimientos.
- Precios definitivos.
- Horarios por profesional.
- Credenciales de WhatsApp, Meta, Google Calendar o telefonia.
- Estado interno del tenant.

Estos datos pertenecen al onboarding posterior y solo se requieren si la capacidad correspondiente sera utilizada.

## Provisionamiento Tecnico

El formulario de superadministracion debe utilizar un unico servicio de provisionamiento. No debe crear `Clinic` directamente y ejecutar tareas parciales desde la pagina Filament.

Secuencia recomendada:

1. Validar unicidad de slug, subdominio, dominio y administrador.
2. Crear la clinica en estado `provisioning`.
3. Asociar un administrador existente o crear una invitacion para uno nuevo.
4. Crear configuracion regional y operativa inicial.
5. Registrar una version revisada del paquete de pais.
6. Crear el catalogo inicial y la lista de precios como borradores.
7. Crear configuraciones CRM con automatizaciones desactivadas.
8. Solicitar el dominio a Easypanel de manera reintentable.
9. Marcar la clinica como `active` cuando el administrador pueda acceder mediante una URL valida.
10. Registrar eventos, errores y reintentos del provisionamiento.

La creacion del dominio no debe quedar acoplada a una unica llamada desde `afterCreate()`. Si existe una URL base de plataforma utilizable, un fallo del dominio personalizado no tiene que impedir el acceso al onboarding.

### Administrador inicial

Un usuario nuevo recibe una invitacion de un solo uso y con expiracion para establecer su contrasena. No se guarda ni comunica una contrasena generica como `password`.

Si el correo ya pertenece a un usuario:

- Se solicita confirmacion antes de asociarlo.
- Se crea la relacion tenant con rol `admin`.
- No se modifica su contrasena ni su rol en otros tenants.

## Estados Independientes

### Estado del tenant

`TenantStatus` controla provisionamiento y acceso general:

```text
draft
provisioning
active
suspended
provisioning_failed
```

El administrador no selecciona este estado durante el alta.

### Estado del onboarding

El onboarding utiliza estados separados:

```text
not_started
in_progress
completed
```

Completar el onboarding no es requisito para acceder al panel. Permite ocultar ayudas iniciales y confirmar que la configuracion elegida por la clinica esta lista.

### Estado de integraciones

Cada integracion conserva su propio ciclo:

```text
not_configured
configuring
validated
active
error
disabled
```

Una integracion opcional no cambia `TenantStatus`.

## Asistente Posterior

### 1. Perfil de la clinica

- Nombre legal y comercial.
- Telefono y correo institucional.
- Direccion.
- Logo y datos visibles en documentos.

### 2. Equipo

- Doctores.
- Auxiliares.
- Especialidades.
- Roles y permisos.
- Horarios por profesional.
- Firma profesional cuando sea necesaria.

### 3. Agenda

- Dias y horas laborables.
- Duracion predeterminada.
- Anticipacion y ventanas de reserva.
- Politicas de confirmacion, cancelacion y reprogramacion.

### 4. Catalogo clinico

- Seleccion de procedimientos sugeridos por el paquete regional.
- Nombre interno y codigo.
- Duracion, sesiones y especialidad.
- Requisitos clinicos.
- Necesidad de caso especializado.

Los procedimientos se copian como registros tenant reales. Una plantilla JSON no constituye un catalogo operativo.

### 5. Lista de precios

- Lista estandar en la moneda confirmada.
- Precios revisados por la clinica.
- Descuentos permitidos.
- Vigencia y publicacion.

La lista se mantiene en borrador hasta que un usuario autorizado la publique. Los precios sugeridos no se utilizan como fallback oculto.

### 6. Configuracion clinica

- Antecedentes y alertas.
- Formularios de evaluacion.
- Plantillas clinicas.
- Politicas de firma y enmienda.
- Consentimientos por procedimiento cuando correspondan.
- Vigencia de revision del perfil medico y niveles de bloqueo permitidos.
- Version publicada de reglas clinicas predeterminadas.
- Politicas de interconsulta y autorizacion externa.
- Formulario minimo y permisos para urgencias o walk-ins.
- Tipos, evaluaciones de riesgo y politicas versionadas de recall.
- Productos criticos con trazabilidad obligatoria y etapas de laboratorio aplicables.

Las reglas clinicas sugeridas por un paquete regional permanecen en borrador hasta revision y publicacion por un responsable autorizado. Una actualizacion del paquete no reemplaza automaticamente reglas ya publicadas.

### 7. Operacion financiera inicial

La clinica selecciona uno de estos modos:

```text
disabled   No se registran documentos fiscales en OdonCRM.
external   La factura se emite en otro sistema y OdonCRM registra su referencia.
simulation Se prueban estados y calculos sin validez tributaria.
```

Los modos futuros `sri_test` y `sri_production` solo se habilitaran cuando exista un conector validado.

### 8. Integraciones

- WhatsApp Cloud API.
- Meta Facebook e Instagram.
- Google Calendar.
- Telefonia.

Las automatizaciones permanecen desactivadas hasta validar credenciales, consentimiento, plantillas y limites operativos.

## Habilitacion Por Capacidad

La aplicacion debe evaluar capacidades, no un unico indicador global de configuracion.

| Capacidad | Requisitos minimos |
|---|---|
| Acceder al panel | Tenant activo y usuario tenant activo |
| Registrar pacientes | Permiso y formularios basicos definidos |
| Usar agenda | Profesional, horario y procedimiento agendable |
| Agendar item de tratamiento | Item aceptado cuando aplique y evaluacion de preparacion para agenda |
| Registrar clinica | Doctor activo, permisos y configuracion clinica basica |
| Crear plan | Catalogo clinico publicado |
| Crear presupuesto | Plan, moneda y lista de precios publicada |
| Ejecutar tratamiento | Perfil revisado, bloqueos resueltos, consentimientos, secuencia, autorizaciones, laboratorio y dispositivos aplicables |
| Atender urgencia o walk-in | Profesional activo, formulario minimo de seguridad, consentimiento aplicable y permiso de encuentro urgente |
| Registrar pago interno | Modulo de pagos habilitado y metodo de pago valido |
| Referenciar factura externa | Modo `external` y datos del documento externo |
| Simular factura | Modo `simulation` y reglas de simulacion versionadas |
| Emitir al SRI | Conector, perfil, credenciales y ambiente SRI validados |

Una capacidad bloqueada debe indicar exactamente que falta y enlazar al paso de configuracion correspondiente.

## Prueba De Inicio A Fin Sin SRI

La ausencia de integracion SRI no impide validar el producto. El escenario de aceptacion inicial es:

```text
Crear tenant
-> aceptar invitacion del administrador
-> configurar perfil y equipo
-> publicar catalogo y lista de precios
-> crear paciente
-> registrar antecedentes y evaluacion
-> registrar odontograma y diagnostico
-> crear plan y revision economica
-> enviar y aceptar presupuesto total o parcialmente
-> registrar consentimiento aplicable
-> agendar
-> realizar encuentro clinico
-> registrar procedimiento realizado
-> actualizar odontograma
-> programar seguimiento
```

El escenario debe incluir al menos un bloqueo clinico resuelto de forma trazable, multiples consentimientos cuando correspondan y un recall calculado desde riesgo. Se valida ademas un encuentro urgente sin plan previo y, para clinicas que los habiliten, un caso de laboratorio o dispositivo critico.

Opcionalmente puede incluir:

```text
Pago interno
-> aplicacion al saldo
-> comprobante interno sin validez tributaria
```

O en modo externo:

```text
Factura emitida fuera de OdonCRM
-> registrar emisor, numero, fecha, total y sistema origen
-> adjuntar RIDE o XML recibido
```

OdonCRM no marca el documento externo como validado por el SRI si no realizo esa consulta.

### Reglas de simulacion

- Todo documento muestra `SIMULACION - SIN VALIDEZ TRIBUTARIA`.
- No consume secuenciales fiscales productivos.
- No genera una autorizacion SRI aparente.
- No se mezcla con documentos externos o reales.
- Puede simular exito, rechazo, timeout y reintento mediante un conector falso.
- Los comprobantes internos no se denominan factura ni comprobante autorizado.

## Brechas Del Sistema Actual

1. `ClinicResource` solicita slug, subdominio, dominio y estado como entradas independientes.
2. El pais se guarda como nombre localizado en lugar de codigo ISO.
3. No existe un campo operativo normalizado para locale.
4. `CreateClinic` crea el modelo mediante Filament y solo llama a Easypanel despues.
5. `CreateClinic` no utiliza `ClinicProvisioningService` ni solicita administrador inicial.
6. El servicio de provisionamiento puede usar una contrasena predeterminada conocida.
7. `procedure_templates` se guarda en JSON, pero no crea procedimientos tenant reales.
8. El servicio marca el tenant `active` sin representar el progreso funcional del onboarding.
9. El dominio no posee un estado y mecanismo de reintento independientes.
10. No existen listas de precios versionadas ni modos financieros activos.
11. No existen perfiles medicos estructurados ni un evaluador unico de preparacion clinica.
12. No existen consentimientos clinicos por item, interconsultas externas ni flujo urgente estructurado.
13. No existen politicas de recall por riesgo, readiness de laboratorio ni trazabilidad de dispositivos criticos.

## Orden De Implementacion

1. Unificar la creacion de tenant mediante un servicio transaccional e idempotente.
2. Sustituir la contrasena predeterminada por invitacion segura.
3. Normalizar pais, locale, moneda y zona horaria.
4. Separar estado de tenant, onboarding, dominio e integraciones.
5. Crear checklist y evaluacion de capacidades.
6. Crear procedimientos tenant reales desde un paquete regional revisado.
7. Crear y publicar listas de precios versionadas.
8. Validar el escenario clinico-comercial completo sin SRI.
9. Validar el camino urgente, bloqueos, consentimientos, autorizaciones y recall.
10. Habilitar laboratorio basico y dispositivos criticos solo para clinicas que los requieran.
11. Agregar pagos internos y referencia de facturacion externa cuando entren en alcance.
12. Implementar conectores SRI de prueba y produccion en una fase independiente.

## Criterios De Aceptacion

1. Un superadministrador crea una clinica y un administrador desde un solo flujo.
2. No se solicita ni guarda una contrasena generica.
3. El tenant puede acceder aunque tenga pasos opcionales pendientes.
4. Agenda, clinica y presupuesto muestran requisitos faltantes especificos.
5. No se activa ninguna automatizacion ni integracion sin validacion.
6. El catalogo y los precios usados pertenecen al tenant activo.
7. Una prueba completa llega desde el alta hasta el seguimiento sin SRI.
8. Ningun documento de simulacion o pago interno puede confundirse con una factura autorizada.
9. Los fallos de dominio e integraciones se reintentan sin duplicar datos.
10. Los eventos de provisionamiento y onboarding quedan auditados.
