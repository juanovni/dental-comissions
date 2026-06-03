# Dental Commissions MVP

## Descripcion
Sistema de control interno de procedimientos odontologicos y comisiones para una clinica dental.
Doctores y auxiliares registran actividades por WhatsApp, la IA interpreta el mensaje, y el
administrador revisa y aprueba antes de generar pagos semanales.

## Stack
- Backend: Laravel 12
- Admin Panel: Filament 4
- Base de datos: PostgreSQL
- IA: OpenAI API (pendiente)
- WhatsApp: Meta WhatsApp Cloud API o Twilio (pendiente)
- PDF: DomPDF o Browsershot (pendiente)
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
- Enums: app/Enums/ (ProfessionalRole, CommissionType)
- Models: app/Models/ (Professional, Patient, Procedure, CommissionRule, DoctorAssistantAssignment)
- Resources: app/Filament/Resources/
- Migrations: database/migrations/2026_06_02_*
- Providers: app/Providers/Filament/AdminPanelProvider.php
- Panel admin: /admin
- Login: requerido para acceder al panel

## Estados Planeados
- Activity: pending_confirmation, needs_review, approved, paid, cancelled
- WeeklyReport: draft, approved, paid
- Professional: is_active, can_register_via_whatsapp

## Comandos Utiles
- composer install
- composer install --ignore-platform-req=ext-xml --ignore-platform-req=ext-dom --ignore-platform-req=ext-xmlwriter --ignore-platform-req=ext-xmlreader --ignore-platform-req=ext-intl --ignore-platform-req=ext-zip
- php artisan package:discover
- php artisan migrate
- php artisan make:filament-user
- php artisan serve

## Requisitos del Servidor
Para ejecutar el proyecto se requieren las siguientes extensiones PHP:
- ext-xml, ext-dom, ext-xmlwriter, ext-xmlreader
- ext-intl
- ext-zip
- pdo_pgsql
- openssl, mbstring, bcmath, ctype, json, pcre, tokenizer

## Limitaciones del Entorno Local
Este proyecto fue creado en un entorno sin acceso sudo para instalar extensiones PHP del
sistema. Por eso se uso --ignore-platform-reqs al instalar dependencias. Para produccion
se debe usar un servidor con las extensiones instaladas correctamente.
