# Catalogos Regionales, Aranceles Y Fiscalidad

## Objetivo

Definir como OdonCRM debe adaptar terminologia, moneda, aranceles, impuestos y futura facturacion electronica a cada pais sin mezclar conceptos clinicos con reglas comerciales o fiscales.

Esta arquitectura no constituye asesoria tributaria. La clasificacion de procedimientos, tasas, retenciones, comprobantes y obligaciones debe aprobarse con el contador o asesor fiscal de cada clinica y mantenerse versionada.

## Principio Central

```text
Concepto clinico != codigo regional
Procedimiento de clinica != precio
Precio != impuesto
Presupuesto != factura
Factura != pago
```

La configuracion regional debe tener seis capas:

1. Conceptos clinicos globales.
2. Paquetes de pais versionados.
3. Catalogo propio de la clinica.
4. Listas de precios versionadas.
5. Reglas fiscales efectivas y perfil tributario de la clinica.
6. Snapshots inmutables en presupuesto y factura.

## Responsabilidad De Cada Capa

### Concepto clinico global

Define el significado clinico estable:

```text
Extraccion dental
Profilaxis
Restauracion en resina
Tratamiento endodontico
```

No contiene:

- Pais.
- Precio.
- Moneda.
- Impuesto.
- Codigo fiscal.

### Paquete de pais

Puede sugerir:

- Nombre preferido y sinonimos locales.
- Locale.
- Moneda predeterminada.
- Zonas horarias sugeridas.
- Sistemas de codificacion aplicables.
- Categorias fiscales sugeridas.
- Reglas de identificacion y direccion.
- Perfil de facturacion electronica.
- Version y fechas de vigencia.

El paquete de pais no debe imponer el precio final de la clinica ni reclasificar automaticamente un servicio sin revision profesional y fiscal.

### Catalogo de la clinica

Cada clinica decide:

- Procedimientos habilitados.
- Nombre comercial interno.
- Codigo interno.
- Duracion.
- Sesiones.
- Especialidad.
- Requisitos clinicos.
- Categoria fiscal aprobada.

Los registros siguen siendo tenant-scoped. El paquete de pais se utiliza como plantilla de provisionamiento y referencia versionada.

### Lista de precios

Contiene el importe comercial:

- Precio estandar.
- Precio particular o contado.
- Precio por convenio.
- Precio de aseguradora o pagador.
- Promocion con vigencia.

Una lista tiene una sola moneda y versiones publicadas inmutables.

### Regla fiscal

Resuelve el tratamiento tributario segun:

- Pais y jurisdiccion.
- Fecha efectiva.
- Perfil del contribuyente.
- Tipo real de bien o servicio.
- Categoria fiscal aprobada.
- Cliente o pagador.
- Fecha del hecho generador aplicable.

El impuesto no debe inferirse solamente porque la empresa sea una clinica dental.

### Snapshot comercial y fiscal

Presupuestos y facturas conservan los valores exactos utilizados en su momento:

- Nombre y codigo mostrado.
- Cantidad.
- Precio unitario.
- Descuento.
- Moneda.
- Categoria fiscal.
- Tasa y codigo externo.
- Base imponible.
- Impuesto.
- Total.
- Versiones del motor y configuraciones.

Los cambios posteriores no alteran el historial.

## Modelo De Datos Recomendado

### Referencias globales

```text
countries
currencies
clinical_procedure_concepts
clinical_procedure_descriptions
code_systems
code_system_versions
procedure_code_mappings
```

### Configuracion regional

```text
country_packs
country_pack_versions
country_procedure_profiles
tax_jurisdictions
tax_categories
tax_rule_versions
country_invoice_profile_versions
external_fiscal_catalogs
external_fiscal_catalog_versions
```

### Catalogo tenant

```text
clinic_procedures
clinic_fiscal_profiles
clinic_tax_registrations
fiscal_document_series
```

`clinic_procedures` puede evolucionar desde la tabla actual `procedures`, conservando `clinic_id` y agregando una referencia opcional al concepto global y al perfil regional.

### Precios

```text
price_lists
price_list_versions
price_list_entries
payers
payer_agreements
patient_coverages
benefit_estimate_snapshots
```

### Presupuesto y facturacion

```text
treatment_plan_revisions
treatment_plan_revision_items
quote_snapshots
invoices
invoice_lines
invoice_tax_breakdowns
invoice_adjustments
credit_notes
payments
payment_allocations
```

### Facturacion electronica

```text
e_invoice_profile_versions
e_invoice_connectors
e_invoice_credentials
e_invoice_submissions
e_invoice_transmission_events
fiscal_artifacts
```

Estas entidades describen una fase futura. La primera entrega clinico-comercial no requiere integracion SRI ni debe crear tablas o estados fiscales que todavia no tengan un caso de uso implementado.

## Resolucion De Valores

### Nombre del procedimiento

Orden recomendado:

1. Snapshot historico del presupuesto o factura.
2. Nombre personalizado por la clinica.
3. Nombre preferido del paquete de pais y locale.
4. Descripcion global.

### Codigo

Un procedimiento puede tener varios codigos simultaneos:

- Codigo interno de clinica.
- Codigo clinico.
- Codigo nacional.
- Codigo requerido por convenio o aseguradora.
- Codigo fiscal.

Cada codigo conserva sistema, version y vigencia. No se deben colapsar todos en `procedures.code`.

### Precio

Orden recomendado:

1. Precio aceptado en un presupuesto vigente cuando corresponda respetarlo.
2. Arancel de convenio o pagador aplicable.
3. Lista seleccionada por el usuario.
4. Lista predeterminada de la clinica.
5. Sin precio: requerir decision del usuario.

Los precios sugeridos por pais solo se copian a una lista borrador durante el alta. Nunca deben ser un fallback oculto en tiempo de ejecucion.

### Impuesto

Orden de resolucion:

1. Identificar emisor y perfil tributario.
2. Determinar jurisdiccion y fecha fiscal.
3. Clasificar el bien o servicio real.
4. Aplicar la regla efectiva mas especifica.
5. Aplicar exenciones o condiciones autorizadas.
6. Permitir override solamente con permiso, motivo y auditoria.
7. Guardar snapshot de la decision.

La configuracion `tax_inclusive` de una lista define si el impuesto se extrae o suma al precio, no determina si legalmente aplica.

## Configuracion Por Pais

### Datos normalizados de la clinica

Se recomienda utilizar:

```text
country_code: ISO 3166-1 alpha-2
currency_code: ISO 4217
locale: BCP 47
timezone: IANA
```

Ejemplo Ecuador:

```text
country_code: EC
currency_code: USD
locale: es-EC
timezone: America/Guayaquil
```

El pais puede sugerir estos valores, pero la moneda y zona horaria deben confirmarse durante el provisionamiento. No se debe reinterpretar el historial si luego cambian.

## Ecuador

### Moneda

Ecuador utiliza dolares de los Estados Unidos. Para una clinica ecuatoriana, el valor predeterminado recomendado es `USD` con el formato de moneda correspondiente a `es-EC`.

Cada registro monetario historico debe guardar `currency_code`; no basta con consultar la moneda actual de la clinica.

### IVA

La pagina oficial del SRI consultada el 31 de agosto de 2026 muestra tasas vigentes de 0% y 13%, y 5% para materiales de construccion. La misma fuente registra que existio una tasa general de 15% desde abril de 2024, lo que demuestra que las tasas cambian en el tiempo.

Los servicios de salud aparecen entre servicios que pueden aplicar tarifa 0%, pero OdonCRM no debe clasificar automaticamente todos los cobros de una clinica dental como salud con IVA 0%.

Requieren validacion contable individual:

- Odontologia estetica o electiva.
- Blanqueamiento.
- Carillas.
- Implantes y protesis.
- Trabajos de laboratorio.
- Dispositivos y materiales.
- Medicamentos o productos vendidos.
- Planes que combinan servicios y bienes.

El sistema debe permitir facturas mixtas con clasificaciones distintas por linea.

### Hecho generador

La configuracion fiscal debe distinguir:

- Fecha de cita.
- Fecha del procedimiento realizado.
- Fecha de pago o abono.
- Fecha de emision.
- Fecha fiscal o hecho generador configurado.

La tasa se resuelve por la fecha legal aplicable, no simplemente por la fecha actual.

### Facturacion electronica SRI

La facturacion electronica queda fuera del alcance inicial. Su integracion futura debe considerar:

- XML como artefacto fiscal autoritativo.
- Firma electronica.
- Ambientes de pruebas y produccion.
- Establecimiento, punto de emision y secuencial.
- Clave de acceso y autorizacion.
- Codigos de identificacion del comprador.
- Codigos de impuesto y porcentaje.
- Formas de pago codificadas.
- Factura, nota de credito, nota de debito y otros documentos aplicables.
- Estado de generacion, firma, envio, autorizacion, rechazo y entrega.
- RIDE como representacion, no como artefacto fiscal principal.
- Version exacta de esquema y catalogos utilizada.

Una factura autorizada no se modifica. Las correcciones utilizan documentos o procesos fiscales vinculados.

Hasta que exista un conector validado, OdonCRM puede operar en modo financiero desactivado, registrar referencias de facturas emitidas externamente o ejecutar simulaciones marcadas sin validez tributaria. Ninguno de esos modos representa una autorizacion del SRI.

### Perfil tributario

Cada emisor debe conservar configuracion efectiva:

- RUC.
- Razon social y nombre comercial.
- Direccion matriz.
- Regimen.
- Obligacion de llevar contabilidad.
- Designaciones tributarias aplicables.
- Establecimientos y puntos de emision fiscales.
- Credenciales y certificado de firma.
- Ambiente SRI.

No se debe inferir el regimen solamente desde ingresos o tipo de clinica. Las actividades profesionales pueden tener reglas especificas.

## Invariantes

1. El concepto clinico global no contiene precio ni impuesto.
2. El catalogo de la clinica siempre pertenece a un tenant.
3. Una lista de precios publicada es inmutable.
4. Una lista y sus entradas usan una sola moneda.
5. Tasas y reglas tienen vigencia efectiva sin solapamientos ambiguos.
6. Exento, tarifa cero y no objeto son resultados distintos.
7. No existe conversion de moneda implicita.
8. Presupuestos y facturas no recalculan configuracion historica.
9. Facturas emitidas no se editan ni eliminan.
10. Codigos externos conservan sistema y version.
11. Los calculos monetarios no utilizan `float` binario.
12. Cada resultado de precio e impuesto registra versiones de configuracion.
13. Las relaciones entre datos financieros respetan tenant y emisor legal.

## Provisionamiento De Una Clinica

1. Seleccionar pais mediante codigo ISO.
2. Confirmar locale, moneda y zona horaria.
3. Seleccionar una version revisada del paquete de pais.
4. Copiar procedimientos sugeridos al catalogo tenant.
5. Permitir que la clinica habilite, renombre y configure duracion.
6. Crear una lista de precios borrador.
7. Cargar precios propios o sugeridos para revision.
8. Configurar categorias fiscales con aprobacion contable.
9. Configurar convenios como listas separadas.
10. Validar cobertura, moneda y vigencias.
11. Publicar versiones iniciales con actor y fecha.
12. Habilitar facturacion solo despues de validar el perfil tributario.

Las actualizaciones del paquete de pais deben mostrar diferencias y requerir activacion controlada. Nunca modifican presupuestos ni facturas historicas.

El perfil tributario y la clasificacion fiscal no bloquean la prueba inicial del circuito clinico, plan, presupuesto, agenda, ejecucion y seguimiento. Vease `09_ONBOARDING_TENANT.md`.

## Impacto En OdonCRM Actual

### Problemas actuales

- `clinics.country` guarda nombres localizados y no codigos ISO.
- `Clinic.currency` no controla realmente los formatos monetarios.
- No existe `Clinic.locale` operativo.
- `internal_rate` se utiliza como precio o valor comercial estimado.
- La UI muestra `$`, `USD` y dos decimales hardcodeados.
- Los umbrales de alto valor usan `1000` sin considerar moneda.
- No existen impuestos, facturas ni pagos activos.
- El provisionamiento guarda una plantilla JSON que no crea procedimientos reales.
- La plantilla usa `price` y `duration_minutes`, campos que no existen actualmente en `Procedure`.
- Algunas consultas de procedimientos no aplican scope tenant explicito.
- Existen dos fuentes de timezone que pueden divergir.

### Cambios requeridos

1. Normalizar pais, moneda, locale y timezone.
2. Crear un servicio regional tenant-aware.
3. Crear un formatter monetario por locale y moneda.
4. Separar precio comercial de `internal_rate`.
5. Migrar precios a listas versionadas.
6. Agregar moneda a valores historicos existentes.
7. Corregir provisioning para crear procedimientos tenant reales.
8. Hacer tenant-scoped todas las resoluciones de catalogo.
9. Unificar timezone de clinica y agenda.
10. Crear dominios fiscales nuevos sin reutilizar tablas antiguas de comisiones.

No se debe convertir automaticamente `internal_rate` en precio comercial sin revision de la clinica.

## Fuentes Oficiales Ecuador

Consultadas el 31 de agosto de 2026:

- SRI IVA: `https://www.sri.gob.ec/impuesto-al-valor-agregado-iva`
- SRI bienes y servicios con tarifa 0%: `https://www.sri.gob.ec/o/sri-portlet-biblioteca-alfresco-internet/descargar/38e837fe-49b1-460c-983e-efd39fd04303/Bienes%20y%20servicios%20gravados%20con%20tarifa%20cero%20porciento%20del%20IVA.pdf`
- SRI facturacion electronica: `https://www.sri.gob.ec/facturacion-electronica`
- SRI contribuyentes obligados: `https://www.sri.gob.ec/web/intersri/contribuyentes-obligados-a-emitir-comprobantes-electronicos`
- SRI RUC personas naturales: `https://www.sri.gob.ec/ruc-personas-naturales`
- SRI RIMPE: `https://www.sri.gob.ec/web/intersri/rimpe`
- SRI retenciones: `https://www.sri.gob.ec/retenciones-en-la-fuente`

Las tasas, codigos, esquemas y obligaciones deben verificarse nuevamente antes de cada activacion o cambio productivo.

## Decisiones Pendientes

1. Paises que se habilitaran inicialmente.
2. Si el catalogo global utilizara una terminologia clinica externa licenciada.
3. Codigos nacionales o de pagadores requeridos por pais.
4. Tipos de listas de precio disponibles.
5. Politica de precios con impuesto incluido o separado.
6. Clasificacion fiscal de cada procedimiento en Ecuador.
7. Perfil tributario y forma juridica de cada clinica.
8. Criterios para incorporar posteriormente la facturacion electronica SRI.
9. Tratamiento de convenios, aseguradoras y copagos.
10. Politica de actualizacion y aprobacion de paquetes regionales.
