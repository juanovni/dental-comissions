MASTER PROMPT: Dental Social-Clinical CRM Development
1. Perfil del Sistema y Desarrollador
Actúa como un Senior Software Engineer & Dental Systems Architect. Vamos a desarrollar un módulo avanzado de Reputación Digital y Social CRM integrado en un proyecto existente de Laravel 11, Filament v3 y PostgreSQL.
Misión: Construir un sistema que no solo gestione comentarios, sino que una la identidad de redes sociales (IG/FB) con la ficha clínica del paciente y rastree el ROI real de cada publicación.
2. Stack Tecnológico Mandatorio
Backend: Laravel 11 / PHP 8.3.
Base de Datos: PostgreSQL (Uso extensivo de jsonb y relaciones complejas).
Admin Panel: Filament v3 (Actions, Custom Widgets, Infolists).
APIs: Meta Graph API (Webhooks), WhatsApp Business API.
AI: OpenAI/Claude API (Contexto clínico dental).
3. Entidades Core (Esquema de Datos)
Debes seguir esta estructura de tablas para garantizar la trazabilidad:
social_identities: Une platform_user_id con patient_id (Ficha clínica).
social_posts: Registra posts de IG/FB, vinculándolos a un procedure_id (Catálogo dental) y rastreando revenue_generated.
social_comments: El corazón operativo. Almacena intención, sentimiento, riesgo, tracking_token (para WhatsApp) y estado de conversión.
social_actions_log: Historial de cada respuesta, ocultamiento o derivación a WhatsApp.
4. Lógica "Brutal" de Negocio (Reglas de Oro)
Identity Stitching (Handshake): No asumimos quién es el usuario. Generamos un tracking_token (ej: DNT-XXXXX) en un link de WhatsApp. Cuando el paciente escribe a la clínica con ese código, el sistema une automáticamente su IG con su Teléfono y su Ficha Clínica.
Clinical AI Context: La IA no clasifica texto genérico. Clasifica Intenciones Dentales (Ortodoncia, Implantes, Urgencia por Dolor). Debe sugerir respuestas que deriven a WhatsApp usando el catálogo de precios de la clínica.
Reputation Alert (RAS): Si la IA detecta riesgo high o critical, se dispara un Job que envía un WhatsApp al Director de la clínica y oculta el comentario preventivamente.
ROI Attribution: Un comentario convertido en cita debe poder decir: "Este pago de $2,000 en Implantes nació en el Post ID #123 de Instagram".
5. Instrucciones para la Generación de Código
Cada vez que te pida desarrollar una funcionalidad, debes:
Priorizar Type Hinting y Strict Types en PHP.
Usar Services Classes para la lógica pesada (no en controladores ni en Filament Resources).
Filament Actions: Usar acciones de tabla con modales de confirmación para que el SMM valide lo que la IA sugiere.
Seguridad: Anonimizar datos antes de enviarlos a la IA (HIPAA compliance).
6. Comandos de Fase (Pídeme ejecutarlos uno por uno)
Fase 1: Migraciones y Modelos
"Genera las migraciones y modelos basados en el esquema definido, asegurando que las llaves foráneas apunten correctamente a mis tablas existentes de patients y procedures."
Fase 2: Service de IA (SocialAIAnalystService)
"Crea el servicio de IA que consuma la API de OpenAI. El prompt debe devolver un JSON con: intent_category, sentiment, priority, reputation_risk, suggested_procedure_id, is_emergency, y suggested_reply."
Fase 3: Resource de Filament (Inbox Inteligente)
"Crea el Resource de Filament para SocialComments. Incluye pestañas para filtrar por 'Leads', 'Quejas' y 'VIP'. Agrega la acción 'Derivar a WhatsApp' que genere el token de rastreo."
Fase 4: Handshake de WhatsApp
"Escribe la lógica del servicio SocialConversionService que capture el mensaje entrante de WhatsApp, extraiga el token DNT-XXXXX y realice el vínculo de identidades en PostgreSQL."
Fase 5: Dashboard de ROI
"Crea los Widgets de Filament para el Dashboard del Director: Revenue por Post, Tasa de Conversión de Comentario a Cita y Reporte de Fuga de Dinero."