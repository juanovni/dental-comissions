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

## Fase 2: Registro Manual y Aprobacion [PENDIENTE]
- [ ] Crear modelo ActivityRecord
- [ ] Crear modelo ActivityAssistant
- [ ] Crear enum ActivityStatus
- [ ] Migracion activity_records
- [ ] Migracion activity_assistants
- [ ] Recurso Filament ActivityRecords (lista + creacion)
- [ ] Formulario: paciente, doctor, procedimiento, auxiliares
- [ ] Validar auxiliares asignados al doctor
- [ ] Calcular comision del doctor (regla parametrizable)
- [ ] Calcular comision del auxiliar por cada procedimiento asistido
- [ ] Estados: pending_confirmation, needs_review, approved, paid, cancelled
- [ ] Accion de aprobacion administrativa
- [ ] Accion de marcado como pagado
- [ ] Notas de correccion
- [ ] Filtros por fecha, profesional, estado

## Fase 3: Reportes Semanales [PENDIENTE]
- [ ] Crear modelo WeeklyReport
- [ ] Crear modelo WeeklyReportItem
- [ ] Crear enum WeeklyReportStatus
- [ ] Migracion weekly_reports
- [ ] Migracion weekly_report_items
- [ ] Recurso Filament WeeklyReports
- [ ] Generar reporte semanal manualmente
- [ ] Resumen por doctor
- [ ] Resumen por auxiliar
- [ ] Total pacientes atendidos
- [ ] Total procedimientos realizados
- [ ] Total comision generada
- [ ] Generar PDF descargable
- [ ] Filtros por rango de fechas
- [ ] Estado: draft, approved, paid
- [ ] Accion de aprobacion del reporte
- [ ] Accion de marcado como pagado

## Fase 4: Integracion WhatsApp [PENDIENTE]
- [ ] Instalar SDK WhatsApp (Twilio o Meta Cloud API)
- [ ] Crear migracion whatsapp_messages
- [ ] Crear modelo WhatsappMessage
- [ ] Crear enum WhatsappMessageStatus
- [ ] Crear enum WhatsappMessageDirection
- [ ] Crear controlador WebhookController
- [ ] Ruta webhook para recibir mensajes
- [ ] Identificar profesional por numero telefonico
- [ ] Guardar mensaje original
- [ ] Enviar respuesta automatica de confirmacion
- [ ] No permitir registrar por otros doctores
- [ ] Validar auxiliares asignados al doctor
- [ ] Manejo de confirmacion OK / CORREGIR
- [ ] Logica de reintento
- [ ] Configuracion de credenciales WhatsApp en .env

## Fase 5: Integracion IA (OpenAI) [PENDIENTE]
- [ ] Instalar openai-php/laravel
- [ ] Configurar API key en .env
- [ ] Crear servicio OpenAIMessageInterpreter
- [ ] Prompt para extraer paciente
- [ ] Prompt para extraer procedimientos
- [ ] Prompt para extraer auxiliar(es)
- [ ] Prompt para extraer fecha/hora
- [ ] Prompt para detectar ambiguedad
- [ ] Salida JSON estructurada
- [ ] Validar respuesta JSON
- [ ] Matchear procedimiento con catalogo
- [ ] Matchear auxiliar con asignaciones del doctor
- [ ] Enviar a revision si falta informacion
- [ ] Logica de fallback si IA falla
- [ ] Pruebas con casos ambiguos

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
