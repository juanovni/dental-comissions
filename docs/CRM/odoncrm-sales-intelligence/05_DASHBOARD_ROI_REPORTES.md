# Fase 5: Dashboard De ROI Y Reportes

## Objetivo

Dar visibilidad ejecutiva sobre dinero en pipeline, dinero ganado, fuga comercial, rendimiento de Smart Links y motivos de perdida.

## Alcance De La Fase

1. Crear widgets Filament de KPIs comerciales.
2. Crear charts de Smart Links vs citas agendadas.
3. Crear reporte PDF semanal de fuga de dinero.
4. Agregar auditoria IA de motivos de perdida.

## Filament Widgets

KPIs propuestos:

1. Dinero total en pipeline.
2. Dinero ganado este mes.
3. Leads perdidos con presupuesto alto.
4. Tasa de conversion de Smart Link a WhatsApp.
5. Leads calientes activos.

Charts propuestos:

1. Clics en Smart Links vs citas agendadas.
2. Valor estimado por etapa.
3. Leads perdidos por motivo.
4. Rendimiento por campana.

## Reporte PDF De Fuga De Dinero

Frecuencia:

Lunes automaticamente.

Criterio:

Leads en etapa `Perdido` con `estimated_value` mayor a 1000 USD.

Contenido:

1. Nombre o identificador del lead.
2. Procedimiento de interes.
3. Valor estimado.
4. Motivo de perdida.
5. Ultima actividad.
6. Recomendacion de recuperacion.

## Auditoria IA

Gemini debe analizar `lost_reason` y eventos relevantes para generar tendencias.

Salida esperada:

1. Top motivos de perdida.
2. Porcentaje aproximado por motivo.
3. Recomendaciones comerciales.
4. Alertas sobre fuga recurrente por precio, tiempo, confianza o financiacion.

## Archivos A Modificar

1. Nuevos widgets en `app/Filament/Widgets/`.
2. Servicio de ROI o ampliar `app/Services/SocialRoiService.php`.
3. Vista PDF en `resources/views/`.
4. Comando Artisan para reporte semanal.
5. Scheduler en `routes/console.php` o configuracion correspondiente.

## Criterios De Aceptacion

1. Dashboard muestra dinero total en pipeline y ganado del mes.
2. Charts cargan datos reales desde PostgreSQL.
3. PDF semanal se puede generar manualmente por comando.
4. IA genera resumen de tendencias basado en motivos de perdida.
5. Los valores monetarios se muestran en USD.

## Riesgos / Dependencias

1. Depende de que Fase 3 agregue pipeline y valor estimado.
2. Citas agendadas requieren fuente confiable de datos o campo definido.
3. DomPDF puede requerir simplificar estilos para render estable.

## Checklist De Implementacion

1. Definir metricas exactas disponibles.
2. Crear o extender servicio ROI.
3. Crear widgets KPI.
4. Crear charts.
5. Crear comando de reporte PDF.
6. Agregar plantilla PDF.
7. Integrar auditoria IA.
8. Probar generacion manual y programada.
9. Ejecutar tests.
