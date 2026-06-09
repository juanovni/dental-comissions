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

## Fase 5: Integracion IA (OpenAI) [COMPLETADA]
- [x] Instalar openai-php/laravel
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
