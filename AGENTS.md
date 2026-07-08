# Dental Commissions MVP

## Descripcion
Sistema de control interno de procedimientos odontologicos y comisiones para una clinica dental.
Doctores y auxiliares registran actividades por WhatsApp, la IA interpreta el mensaje, y el
administrador revisa y aprueba antes de generar pagos semanales.

## Stack
- Backend: Laravel 12
- Admin Panel: Filament 4
- Base de datos: PostgreSQL
- IA: Google Gemini API (Http facade)
- WhatsApp: Meta WhatsApp Cloud API (Http facade)
- PDF: barryvdh/laravel-dompdf
- Moneda: USD (solo control interno)

## Reglas de Negocio
- La comision del auxiliar se paga por cada procedimiento asistido
- Un doctor solo puede registrar sus propios procedimientos
- Los auxiliares tambien pueden recibir comision
- Los pacientes se crean automaticamente si no existen
- Un mensaje WhatsApp puede contener varios procedimientos para un paciente
- El administrador debe aprobar antes de pagar
- El administrador principal tiene varios doctores y cada doctor tiene auxiliares asignados
- No se envian costos por WhatsApp, solo se registran actividades
- La comision se paga por cada procedimiento asistido
- Cualquier valor o registro debe ser parametrizable

## Convenciones
- PSR-12 para codigo PHP
- Enums para estados y tipos (App\Enums)
- Modelos con $fillable y casts() explicitos
- Migraciones con timestamp descriptivo YYYY_MM_DD_NNNNNN
- Recursos Filament agrupados por dominio (App\Filament\Resources\Entidad)
- Paginas CRUD en App\Filament\Resources\Entidad\Pages
- Providers Filament en App\Providers\Filament
- Idioma principal: espanol (APP_LOCALE=es)
- Etiquetas de UI en espanol

## Archivos Clave
- Enums: app/Enums/ (ProfessionalRole, CommissionType, ActivityStatus, WeeklyReportStatus, WhatsappMessageStatus, WhatsappMessageDirection)
- Models: app/Models/ (Professional, Patient, Procedure, CommissionRule, DoctorAssistantAssignment, ActivityRecord, ActivityAssistant, WeeklyReport, WeeklyReportItem, WhatsappMessage)
- Services: app/Services/ (WhatsappService)
- Controllers: app/Http/Controllers/ (WebhookController)
- Resources: app/Filament/Resources/ (Professionals, Patients, Procedures, CommissionRules, DoctorAssistantAssignments, ActivityRecords, WeeklyReports)
- Migrations: database/migrations/2026_06_02_*
- Providers: app/Providers/Filament/AdminPanelProvider.php
- Panel admin: /admin
- Login: requerido para acceder al panel
- Webhook WhatsApp: /webhook/whatsapp (GET verificacion, POST recepcion)

## Estados Planeados
- Activity: pending_confirmation, needs_review, approved, paid, cancelled
- WeeklyReport: draft, approved, paid
- Professional: is_active, can_register_via_whatsapp

## Comandos Utiles (sin Docker)
- composer install
- composer install --ignore-platform-req=ext-xml --ignore-platform-req=ext-dom --ignore-platform-req=ext-xmlwriter --ignore-platform-req=ext-xmlreader --ignore-platform-req=ext-intl --ignore-platform-req=ext-zip
- php artisan package:discover
- php artisan migrate
- php artisan make:filament-user
- php artisan serve

## Entorno Docker (recomendado para WSL)
El proyecto incluye docker-compose.yml listo para WSL/Linux. PHP 8.3 con todas las
extensiones necesarias, PostgreSQL 16 y Vite para assets.

Servicios:
- dental.app: PHP 8.3 + Composer + Node 22 (puerto 8080)
- dental.pgsql: PostgreSQL 16 (puerto 5432)
- dental.vite: Vite dev server (puerto 5173) [solo dev]
- dental.worker: Queue worker [solo dev]

Archivos:
- docker-compose.yml: servicios principales
- docker-compose.override.yml: servicios adicionales de desarrollo
- docker/8.3/Dockerfile: imagen PHP 8.3 con extensiones
- docker/8.3/php.ini: configuracion PHP
- docker/8.3/supervisord.conf: supervisor
- docker/8.3/start-container: script de inicio
- docker/pgsql/create-testing-database.sh: crea DB de testing
- .env.docker: plantilla de variables de entorno
- Makefile: comandos simplificados

Uso:
- make up: levanta los contenedores
- make build: construye la imagen
- make shell: abre bash en el contenedor app
- make migrate: ejecuta migraciones
- make fresh: fresh migrate con seed
- make test: ejecuta tests
- make composer cmd=install: ejecuta composer
- make npm cmd=install: ejecuta npm
- make down: detiene los contenedores
- make clean: elimina todo (contenedores, volumenes, imagenes)

Acceso:
- App: http://localhost:8080
- Admin: http://localhost:8080/admin
- Vite: http://localhost:5173
- PostgreSQL: localhost:5432 (user/pass: dental/dental, db: dental_commissions_mvp)

## Requisitos del Servidor
Para ejecutar el proyecto se requieren las siguientes extensiones PHP:
- ext-xml, ext-dom, ext-xmlwriter, ext-xmlreader
- ext-intl
- ext-zip
- pdo_pgsql
- openssl, mbstring, bcmath, ctype, json, pcre, tokenizer

## Limitaciones del Entorno Local Sin Docker
Este proyecto fue creado en un entorno sin acceso sudo para instalar extensiones PHP del
sistema. Por eso se uso --ignore-platform-reqs al instalar dependencias. Para desarrollo
local se recomienda usar Docker (ver seccion "Entorno Docker"). Para produccion se debe
usar un servidor con las extensiones instaladas correctamente.
