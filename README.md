# Dental Commissions MVP

Sistema desarrollado con Laravel para la gestión de operaciones odontológicas.

El proyecto utiliza Docker para proporcionar todo el entorno de desarrollo:

- PHP 8.4
- Laravel
- Composer
- Node.js 22
- Vite
- PostgreSQL 16
- Laravel Reverb
- Supervisor

Por lo tanto, no es necesario instalar PHP, Composer, Node.js o PostgreSQL directamente en el sistema operativo.

---

## Requisitos

Antes de comenzar necesitas tener instalado:

- Git
- Docker
- Docker Compose

Si trabajas desde Windows se recomienda utilizar:

- WSL 2
- Ubuntu
- Docker Desktop con integración WSL habilitada

---

## 1. Clonar el proyecto

```bash
git clone <URL_DEL_REPOSITORIO>

```
## 2. Entrar al proyecto:

```bash
cd dental-commissions
```
---

## 3. Iniciar la primera vez

```bash
docker compose down
docker compose build --no-cache
docker compose up -d

docker compose exec dental.app composer install
docker compose exec dental.app npm install
docker compose exec dental.app npm run build
docker compose exec dental.app php artisan key:generate
docker compose exec dental.app php artisan migrate:fresh --seed