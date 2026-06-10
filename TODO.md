# TODO - Dental Commissions MVP

## Fase 0: Definicion Operativa [COMPLETADA]
- [x] Decisiones de negocio confirmadas
- [x] Comisiones: fija, porcentual o mixta
- [x] Doctor no registra por otros
- [x] Auxiliares pueden tener comision
- [x] Pacientes se crean automaticamente
- [x] Varios procedimientos por mensaje
- [x] Administrador aprueba antes de pagar
- [x] Comision por cada procedimiento asistido
- [x] Moneda USD, control interno
- [x] Parametrizable: procedimientos, comisiones, profesionales, auxiliares

## Fase 1: Base Administrativa [COMPLETADA]
- [x] Laravel 12 instalado
- [x] Filament 4 instalado
- [x] Configuracion PostgreSQL en .env
- [x] Enums creados (ProfessionalRole, CommissionType)
- [x] Modelo Professional
- [x] Modelo Patient
- [x] Modelo Procedure
- [x] Modelo CommissionRule
- [x] Modelo DoctorAssistantAssignment
- [x] Migracion professionals
- [x] Migracion patients
- [x] Migracion procedures
- [x] Migracion doctor_assistant_assignments
- [x] Migracion commission_rules
- [x] Recurso Filament Professionals (CRUD)
- [x] Recurso Filament Patients (CRUD)
- [x] Recurso Filament Procedures (CRUD)
- [x] Recurso Filament DoctorAssistantAssignments (CRUD)
- [x] Recurso Filament CommissionRules (CRUD)
- [x] AdminPanelProvider configurado en /admin
- [x] Sintaxis PHP verificada
- [x] Autoload verificado
- [x] docker-compose.yml con PHP 8.3 + PostgreSQL 16
- [x] Dockerfile personalizado en docker/8.3/
- [x] docker-compose.override.yml con Vite y queue worker
- [x] Makefile con comandos simplificados
- [x] .dockerignore y .env.docker
- [x] AGENTS.md actualizado con instrucciones Docker

## Fase 2: Registro Manual y Aprobacion [COMPLETADA]
- [x] Crear modelo ActivityRecord
- [x] Crear modelo ActivityAssistant
- [x] Crear enum ActivityStatus
- [x] Migracion activity_records
- [x] Migracion activity_assistants
- [x] Recurso Filament ActivityRecords (lista + creacion)
- [x] Formulario: paciente, doctor, procedimiento, auxiliares
- [x] Validar auxiliares asignados al doctor
- [x] Calcular comision del doctor (regla parametrizable)
- [x] Calcular comision del auxiliar por cada procedimiento asistido
- [x] Estados: pending_confirmation, needs_review, approved, paid, cancelled
- [x] Accion de aprobacion administrativa
- [x] Accion de marcado como pagado
- [x] Notas de correccion
- [x] Filtros por fecha, profesional, estado

## Fase 2.1: Mejora de Aprobacion Administrativa [COMPLETADA]
- [x] Agregar accion directa Aprobar en tabla de actividades
- [x] Agregar accion directa Solicitar correccion en tabla de actividades
- [x] Agregar accion directa Marcar como pagado en tabla de actividades
- [x] Mantener Editar como accion de detalle

## Fase 3: Reportes Semanales [COMPLETADA]
- [x] Crear modelo WeeklyReport
- [x] Crear modelo WeeklyReportItem
- [x] Crear enum WeeklyReportStatus
- [x] Migracion weekly_reports
- [x] Migracion weekly_report_items
- [x] Recurso Filament WeeklyReports
- [x] Generar reporte semanal manualmente
- [x] Resumen por doctor
- [x] Resumen por auxiliar
- [x] Total pacientes atendidos
- [x] Total procedimientos realizados
- [x] Total comision generada
- [x] Generar PDF descargable
- [x] Filtros por rango de fechas
- [x] Estado: draft, approved, paid
- [x] Accion de aprobacion del reporte
- [x] Accion de marcado como pagado

## Fase 4: Integracion WhatsApp [COMPLETADA]
- [x] Instalar SDK WhatsApp (Twilio o Meta Cloud API)
- [x] Crear migracion whatsapp_messages
- [x] Crear modelo WhatsappMessage
- [x] Crear enum WhatsappMessageStatus
- [x] Crear enum WhatsappMessageDirection
- [x] Crear controlador WebhookController
- [x] Ruta webhook para recibir mensajes
- [x] Identificar profesional por numero telefonico
- [x] Guardar mensaje original
- [x] Enviar respuesta automatica de confirmacion
- [x] No permitir registrar por otros doctores
- [x] Validar auxiliares asignados al doctor
- [x] Manejo de confirmacion OK / CORREGIR
- [x] Logica de reintento
- [x] Configuracion de credenciales WhatsApp en .env

## Fase 5: Integracion IA (Google Gemini) [COMPLETADA]
- [x] Integrar Google Gemini API via Http facade
- [x] Configurar API key en .env
- [x] Crear servicio AiParsingService
- [x] Prompt para extraer paciente
- [x] Prompt para extraer procedimientos
- [x] Prompt para extraer auxiliar(es)
- [x] Prompt para extraer fecha/hora
- [x] Prompt para detectar ambiguedad
- [x] Salida JSON estructurada
- [x] Validar respuesta JSON
- [x] Matchear procedimiento con catalogo
- [x] Matchear auxiliar con asignaciones del doctor
- [x] Enviar a revision si falta informacion
- [x] Logica de fallback si IA falla
- [x] Pruebas con casos ambiguos

## Fase 6: Piloto Operativo [PENDIENTE]
- [ ] Levantar entorno con `make up`
- [ ] Verificar que app responde en http://localhost:8080
- [ ] Ejecutar `make migrate` (si no se hace automaticamente)
- [ ] Crear usuario administrador con `php artisan make:filament-user`
- [ ] Cargar catalogo de procedimientos
- [ ] Cargar reglas de comision
- [ ] Registrar 2 doctores
- [ ] Registrar 2 auxiliares
- [ ] Asignar auxiliares a doctores
- [ ] Configurar WhatsApp Business API
- [ ] Probar 1 semana de registros manuales
- [ ] Probar 1 semana de registros por WhatsApp
- [ ] Comparar contra metodo actual
- [ ] Medir tasa de error de IA
- [ ] Ajustar reglas y reportes
- [ ] Documentar aprendizajes

## Modulo Reputacion Digital - Fase 1: Validacion Meta [COMPLETADA]
- [x] Definir alcance inicial: Instagram y Facebook
- [x] Definir modo semiautomatico sin eliminacion automatica
- [x] Documentar prerrequisitos de cuenta Meta
- [x] Documentar permisos esperados de Meta Graph API
- [x] Identificar endpoints iniciales para paginas, publicaciones y comentarios
- [x] Definir capacidades a validar por plataforma
- [x] Definir flujo tecnico propuesto
- [x] Definir contrato JSON de clasificacion IA
- [x] Documentar reglas de seguridad del MVP
- [x] Documentar riesgos y mitigaciones
- [x] Crear documento docs/social-reputation-meta-validation.md

## Modulo Reputacion Digital - Fase 2: Flujo funcional [COMPLETADA]
- [x] Definir sincronizacion programada como entrada principal
- [x] Definir webhooks de Meta como entrada complementaria
- [x] Definir frecuencia de sincronizacion cada 5 minutos
- [x] Definir importacion inicial de ultimos 30 dias
- [x] Definir estados de comentarios
- [x] Definir clasificaciones IA orientadas a reputacion y conversion
- [x] Definir prioridades y riesgo reputacional
- [x] Definir acciones sugeridas y canales de respuesta
- [x] Definir reglas de revision humana obligatoria
- [x] Definir reglas de ocultamiento manual sin eliminacion
- [x] Definir contrato JSON de IA para comentarios
- [x] Definir metricas del MVP e historial requerido
- [x] Documentar casos operativos y fuera de alcance
- [x] Crear documento docs/social-reputation-flow.md

## Modulo Reputacion Digital - Fase 3: Base de datos [COMPLETADA]
- [x] Crear enums del modulo social
- [x] Crear modelo SocialAccount
- [x] Crear modelo SocialPost
- [x] Crear modelo SocialComment
- [x] Crear modelo SocialCommentAction
- [x] Crear modelo SocialModerationRule
- [x] Crear modelo SocialReplyTemplate
- [x] Crear migracion social_accounts con tokens cifrables e indices
- [x] Crear migracion social_posts con payload original e indice unico
- [x] Crear migracion social_comments con clasificacion IA, riesgo reputacional e indice unico
- [x] Crear migracion social_comment_actions para historial administrativo
- [x] Crear migracion social_moderation_rules para reglas configurables
- [x] Crear migracion social_reply_templates para plantillas de respuesta
- [x] Definir relaciones Eloquent principales
- [x] Agregar casts explicitos, JSON, fechas y enums

## Modulo Reputacion Digital - Fase 4: Integracion inicial con Meta [COMPLETADA]
- [x] Agregar configuracion `services.meta`
- [x] Agregar variables `META_*` en archivos de entorno
- [x] Crear servicio MetaSocialService
- [x] Listar paginas autorizadas desde Meta Graph API
- [x] Crear/actualizar cuentas Facebook automaticamente
- [x] Detectar cuenta Instagram Business conectada a una pagina
- [x] Crear/actualizar cuentas Instagram automaticamente
- [x] Sincronizar publicaciones recientes de ultimos 30 dias
- [x] Sincronizar comentarios de publicaciones
- [x] Guardar posts y comentarios con `updateOrCreate`/idempotencia
- [x] Preservar estados administrativos de comentarios existentes al resincronizar
- [x] Crear comando `social:sync-comments`
- [x] Programar sincronizacion cada 5 minutos
- [x] Crear webhook complementario `/webhook/meta/social`
- [x] Excluir webhook Meta social de CSRF

## Modulo Reputacion Digital - Fase 5: Clasificacion con IA [COMPLETADA]
- [x] Crear servicio SocialCommentClassificationService
- [x] Implementar prompt de IA para reputacion digital dental
- [x] Validar contrato JSON de clasificacion
- [x] Guardar classification, sentiment, priority y reputation_risk
- [x] Guardar suggested_action, response_channel y suggested_reply
- [x] Marcar comentarios como classified o review_required
- [x] Registrar ai_response, ai_reason y processed_at
- [x] Registrar accion classify en social_comment_actions
- [x] Implementar fallback local si Gemini falla
- [x] Crear comando `social:classify-comments`
- [x] Programar clasificacion cada 5 minutos
- [x] Agregar plan de prueba con `/test/meta/comment`

## Modulo Reputacion Digital - Fase 6: Panel administrativo [COMPLETADA]
- [x] Crear recurso Filament SocialCommentResource
- [x] Agregar bandeja de comentarios con filtros por red, estado, clasificacion, prioridad y riesgo
- [x] Mostrar prioridad, riesgo reputacional, estado y revision humana con badges/iconos
- [x] Crear vista detalle del comentario con publicacion, autor, clasificacion IA y respuesta sugerida
- [x] Permitir editar estado, accion sugerida, canal, respuesta sugerida y motivo IA
- [x] Agregar accion manual interna Marcar revisado
- [x] Agregar accion manual interna Ignorar
- [x] Agregar accion manual interna Escalar
- [x] Agregar accion manual interna Spam interno
- [x] Registrar acciones manuales en social_comment_actions con usuario administrador
- [x] Crear recurso Filament SocialAccountResource para revisar cuentas y estado de sincronizacion
- [x] Mantener acciones sin modificar Meta directamente en esta fase
- [x] Crear Bandeja de Reputacion minimalista con cards, metricas, filtros rapidos y acciones inline
