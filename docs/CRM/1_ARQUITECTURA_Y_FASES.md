# Fases de Desarrollo: Implementación con OpenCode

### Fase 1: Estructura de Datos (El Puente)
- **Acción:** Crear migraciones de `social_identities`, `social_posts`, `social_comments` y `social_actions_log`.
- **Objetivo:** Permitir que un `patient_id` de tu sistema actual se relacione con un `platform_user_id` de Meta.

### Fase 2: El Cerebro (AI Intelligence)
- **Acción:** Implementar `SocialAIAnalystService`.
- **Objetivo:** Enviar comentarios a la IA y recibir un JSON con: Categoría, Sentimiento, Riesgo, Procedimiento Sugerido y Respuesta.

### Fase 3: Operación (SMM Command Center)
- **Acción:** Crear el recurso `SocialCommentResource` en Filament.
- **Componentes:**
    - Actions: `Derivar a WhatsApp` (con generación de Token).
    - Tabs: Clasificación por intención (Leads, Quejas, VIP).
    - Bulk Actions: Ocultar spam masivo.

### Fase 4: Conversión y Atribución (Identity Stitching)
- **Acción:** Desarrollar `SocialConversionService`.
- **Lógica:** Capturar el código `DNT-XXXXX` que llega por el Webhook de WhatsApp y unir la cuenta de IG con el teléfono del paciente en la base de datos.

### Fase 5: Director Dashboard (ROI & Analytics)
- **Acción:** Crear Widgets de Filament para:
    - Revenue por Post (Dinero real ganado).
    - Leakage Report (Dinero perdido por falta de seguimiento).
    - Alert System (Notificaciones de crisis).