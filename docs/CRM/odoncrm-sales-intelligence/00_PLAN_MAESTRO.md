# OdonCRM Sales Intelligence - Plan Maestro

## Objetivo

Convertir el CRM social actual en un sistema comercial orientado a intencion, seguimiento en tiempo real, pipeline de ingresos y reportes ejecutivos.

## Contexto Actual

El sistema ya cuenta con una base funcional para leads sociales, Smart Links, landing publica, tracking de eventos, scoring, alertas internas, WhatsApp con token, Filament 4 y PostgreSQL.

## Fases

1. Fase 1: Inteligencia de Smart Links y Tiempo Real.
2. Fase 2: Bandeja de Entrada Split-View.
3. Fase 3: Pipeline Comercial Kanban.
4. Fase 4: Pulso del Cliente y Timeline de Actividad.
5. Fase 5: Dashboard de ROI y Reportes.
6. Infraestructura y Build: Reverb, Docker, Vite y soporte operativo.

## Orden Recomendado

1. Implementar infraestructura base de Reverb junto con Fase 1.
2. Implementar Fase 1 para convertir la landing en sensor activo de intencion.
3. Implementar Fase 2 para consumir alertas en vivo dentro de Filament.
4. Implementar Fase 4 para visualizar el historial de comportamiento del lead.
5. Implementar Fase 3 para gestionar oportunidades e ingresos.
6. Implementar Fase 5 para medir ROI, fuga de dinero y tendencias.

## Principios De Implementacion

1. Mantener cambios pequenos y verificables por fase.
2. No romper el flujo actual de Smart Links ni WhatsApp.
3. Reutilizar `social_comments`, `social_link_events`, `social_lead_alerts` y servicios existentes antes de crear nuevas tablas.
4. Mantener UI en espanol.
5. Usar Enums para nuevos estados persistentes.
6. Mantener compatibilidad con Filament 4 y Livewire 3.
7. Agregar migraciones descriptivas con timestamp.

## Dependencias Globales

1. Laravel Reverb para tiempo real.
2. Broadcasting configurado en Laravel.
3. Canal privado para notificaciones administrativas.
4. Vite configurado para cliente Echo/Reverb.
5. Docker y Makefile actualizados para levantar WebSockets.

## Validacion General

1. `make test` o comando equivalente debe pasar al cierre de cada fase.
2. Cada nueva migracion debe ejecutarse correctamente en PostgreSQL.
3. Cada flujo critico debe probarse manualmente en `/admin` y en `/v/{trackingToken}`.
4. No se deben exponer tokens o datos sensibles en logs publicos.
