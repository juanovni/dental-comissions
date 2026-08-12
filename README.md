# Dental Commissions MVP

Sistema interno para control de procedimientos odontologicos, comisiones, WhatsApp, agenda y panel administrativo con Laravel y Filament.

## Stack Local

- PHP 8.4
- Laravel 12
- Filament 4
- PostgreSQL 16
- Node.js 22
- Vite
- Laravel Reverb
- Docker Compose

No necesitas instalar PHP, Composer, Node.js ni PostgreSQL en tu sistema. Todo corre dentro de Docker.

## Requisitos

- Git
- Docker
- Docker Compose

En Windows se recomienda usar WSL 2 con Ubuntu y Docker Desktop con integracion WSL habilitada.

## Instalacion Local

1. Clonar el repositorio:

```bash
git clone <URL_DEL_REPOSITORIO>
```

2. Entrar al proyecto:

```bash
cd dental-comissions
```

3. Crear el archivo `.env` si no existe:

```bash
cp .env.docker .env
```

Si ya tienes un `.env` local configurado, no lo reemplaces.

4. Construir la imagen:

```bash
docker compose build
```

5. Levantar los contenedores:

```bash
docker compose up -d
```

6. Instalar dependencias PHP:

```bash
docker compose exec dental.app composer install
```

7. Instalar dependencias Node:

```bash
docker compose exec dental.app npm install
```

8. Generar `APP_KEY` si tu `.env` no tiene una:

```bash
docker compose exec dental.app php artisan key:generate
```

9. Ejecutar migraciones:

```bash
docker compose exec dental.app php artisan migrate --force
```

10. Limpiar caches:

```bash
docker compose exec dental.app php artisan optimize:clear
```

## Acceso

- App: http://localhost:8080
- Admin: http://localhost:8080/admin/login
- Vite: http://localhost:5173
- PostgreSQL: localhost:5432

Credenciales PostgreSQL por defecto:

- Base de datos: `dental_commissions_mvp`
- Usuario: `dental`
- Password: `dental`

## Crear Usuario Admin

Para crear un usuario del panel Filament:

```bash
docker compose exec dental.app php artisan make:filament-user
```

Luego entra en:

```text
http://localhost:8080/admin/login
```

## Comandos Rapidos Con Make

El proyecto incluye `Makefile`.

```bash
make build
make up
make down
make restart
make shell
make migrate
make fresh
make test
make logs
```

Instalar dependencias usando Make:

```bash
make composer cmd=install
make npm cmd=install
```

## Reiniciar Desde Cero

Esto elimina contenedores, volumenes e imagenes del proyecto:

```bash
make clean
make build
make up
make composer cmd=install
make npm cmd=install
make fresh
```

## Verificacion

Para validar que el admin responde:

```bash
curl -I http://localhost:8080/admin/login
```

La respuesta esperada es `HTTP/1.1 200 OK`.

## Problemas Comunes

Si aparece `vendor/autoload.php` no encontrado:

```bash
docker compose exec dental.app composer install
docker compose restart dental.app
```

Si aparece error de permisos en `storage` o `bootstrap/cache`:

```bash
docker compose exec dental.app chmod -R ugo+rwX storage bootstrap/cache
docker compose exec dental.app php artisan optimize:clear
docker compose restart dental.app
```

Si falta el manifest de Vite, usa el servidor de desarrollo incluido:

```bash
docker compose up -d dental.vite
```

O genera assets estaticos:

```bash
docker compose exec dental.app npm run build
```

Si cambias archivos Docker, reconstruye la imagen:

```bash
docker compose build dental.app
docker compose up -d
```
