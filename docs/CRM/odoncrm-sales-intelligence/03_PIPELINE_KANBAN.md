# Fase 3: Pipeline Comercial Kanban

## Objetivo

Pasar de gestion de mensajes a gestion de oportunidades comerciales, midiendo ingresos estimados, etapas y dinero ganado/perdido.

## Alcance De La Fase

1. Agregar etapa comercial al lead.
2. Agregar valor estimado.
3. Registrar motivo de perdida.
4. Crear vista Kanban en Filament.
5. Calcular totales por etapa.
6. Definir logica de temperatura del lead.

## Base De Datos

Tabla objetivo inicial: `social_comments`.

Campos propuestos:

1. `pipeline_stage` string o enum persistido.
2. `estimated_value` decimal nullable/default 0.
3. `lost_reason` string nullable, si no existe ya con el alcance requerido.

Enum recomendado:

`app/Enums/SocialPipelineStage.php`

Valores:

1. `new`: Nuevo.
2. `qualified`: Calificado.
3. `appointment`: Cita.
4. `proposal`: Presupuesto.
5. `won`: Ganado.
6. `lost`: Perdido.

## Logica De Calor Del Lead

Lead frio:

Sin interaccion por mas de 48 horas.

Lead caliente:

Abrio Smart Link hace menos de 10 minutos.

Lead en seguimiento:

Tiene cita de valoracion agendada para hoy.

## Query Scopes

Agregar scopes o metodos de consulta en `SocialComment` para:

1. Leads por etapa.
2. Total `estimated_value` por etapa.
3. Leads calientes.
4. Leads frios.
5. Leads en seguimiento.

## Cambios Frontend/UI

Vista Kanban en Filament:

1. Columnas por etapa.
2. Tarjetas con nombre, procedimiento, score, valor estimado y temperatura.
3. Drag & Drop para cambiar etapa.
4. Al soltar en `Ganado`, mostrar confetti.
5. Al soltar en `Perdido`, pedir `lost_reason`.

## Archivos A Modificar

1. Nueva migracion para campos de pipeline.
2. `app/Enums/SocialPipelineStage.php`
3. `app/Models/SocialComment.php`
4. Nueva pagina Filament Kanban o extension de recurso social.
5. Vista Blade/Livewire asociada.

## Criterios De Aceptacion

1. Cada lead puede moverse entre etapas.
2. El valor estimado se suma correctamente por columna.
3. Cambiar a `Ganado` persiste estado y muestra feedback visual.
4. Cambiar a `Perdido` exige o permite registrar motivo.
5. Los estados usan Enum y labels en espanol.

## Riesgos / Dependencias

1. Drag & Drop en Filament puede requerir paquete o componente personalizado.
2. Hay que proteger transiciones comerciales sensibles con permisos.
3. `lost_reason` ya aparece en modelo actual; revisar migraciones antes de crear duplicados.

## Checklist De Implementacion

1. Revisar campos existentes para evitar duplicados.
2. Crear enum de pipeline.
3. Crear migracion.
4. Actualizar modelo y casts.
5. Crear vista Kanban.
6. Implementar drag & drop.
7. Agregar calculos por etapa.
8. Probar transiciones y persistencia.
9. Ejecutar tests.
