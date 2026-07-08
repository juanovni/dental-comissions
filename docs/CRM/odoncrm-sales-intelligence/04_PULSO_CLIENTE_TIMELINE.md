# Fase 4: Pulso Del Cliente - Timeline De Actividad

## Objetivo

Dar visibilidad completa del comportamiento del paciente: que abrio, que vio, cuanto avanzo, si volvio y cuando contacto por WhatsApp.

## Estado Actual Del Sistema

La tabla actual de eventos es `social_link_events`. Los eventos se relacionan con `social_comments` por `social_comment_id`.

## Alcance De La Fase

1. Crear componente visual de timeline.
2. Mapear eventos a iconos, colores y texto humano.
3. Mostrar progreso de video.
4. Agregar analisis de comportamiento con Gemini.
5. Integrar timeline en la bandeja split-view y/o recurso de lead social.

## Mapeo De Eventos

1. `view`: Icono de ojo, texto `Abrio el Smart Link`.
2. `revisit`: Icono de retorno, texto `Volvio a abrir el Smart Link`.
3. `engagement_ping`: Icono de reloj, texto `Permanecio en la landing`.
4. `duration_threshold`: Icono de fuego, texto `Supero el umbral de interes`.
5. `video_start`: Icono de video, texto `Inicio el video`.
6. `video_25`: Barra 25%, texto `Vio 25% del video`.
7. `video_50`: Barra 50%, texto `Vio 50% del video`.
8. `video_75`: Barra 75%, texto `Vio 75% del video`.
9. `video_complete`: Barra 100%, texto `Completo el video`.
10. `whatsapp_click`: Icono WhatsApp, texto `Hizo clic para continuar por WhatsApp`.

## Componente Filament

Opcion A:

Crear Entry personalizado para Infolists.

Opcion B:

Crear componente Livewire reutilizable para timeline.

Recomendacion inicial:

Usar componente Livewire por simplicidad y reutilizacion en varias paginas.

## IA Insights

Boton: `Analizar comportamiento`.

Prompt debe incluir:

1. Lead y procedimiento sugerido.
2. Lista ordenada de eventos.
3. Duracion total aproximada.
4. Veces que reabrio link.
5. Hitos de video alcanzados.
6. Clics en WhatsApp.

Salida esperada:

1. Resumen en lenguaje comercial.
2. Nivel de intencion: bajo, medio, alto.
3. Objecion probable.
4. Proxima accion recomendada.

## Archivos A Modificar

1. Nuevo componente Livewire o Entry personalizado.
2. Vista Blade del timeline.
3. `app/Services/GeminiJsonService.php` o servicio dedicado de insights.
4. Pagina de inbox o recurso social donde se incruste el timeline.

## Criterios De Aceptacion

1. El timeline muestra eventos ordenados de mas reciente a mas antiguo o cronologico configurable.
2. Cada evento tiene label en espanol, timestamp relativo y metadatos relevantes.
3. Los eventos de video muestran progreso visual.
4. El boton de IA genera un resumen accionable.
5. El componente no rompe si el lead no tiene eventos.

## Riesgos / Dependencias

1. Si `engagement_ping` se guarda muchas veces, el timeline puede saturarse.
2. Conviene agrupar pings o mostrar solo eventos relevantes.
3. El analisis IA debe evitar exponer datos innecesarios.

## Checklist De Implementacion

1. Definir componente Livewire o Infolist Entry.
2. Crear mapper de eventos a labels/iconos.
3. Renderizar timeline con Tailwind CSS.
4. Agrupar eventos repetitivos.
5. Agregar boton de analisis Gemini.
6. Integrar en bandeja o recurso.
7. Probar lead sin eventos y con muchos eventos.
8. Ejecutar tests.
