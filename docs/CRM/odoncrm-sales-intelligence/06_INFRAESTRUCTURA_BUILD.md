# Infraestructura Y Build

## Objetivo

Preparar el entorno para tiempo real con Laravel Reverb, Livewire/Echo, Docker, Vite y comandos operativos.

## Alcance

1. Instalar y configurar Laravel Reverb.
2. Configurar broadcasting.
3. Agregar servicio Reverb en Docker.
4. Actualizar Makefile.
5. Configurar cliente Echo/Reverb en assets.
6. Validar compatibilidad con Filament 4 y Livewire 3.

## Laravel Reverb

Cambios esperados:

1. Agregar dependencia si no existe.
2. Publicar configuracion de Reverb.
3. Configurar `.env` y `.env.docker`.
4. Configurar `BROADCAST_CONNECTION=reverb`.
5. Crear canales privados en `routes/channels.php`.

Variables esperadas:

```env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=odoncrm
REVERB_APP_KEY=local-key
REVERB_APP_SECRET=local-secret
REVERB_HOST=localhost
REVERB_PORT=8081
REVERB_SCHEME=http
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

## Docker

Agregar servicio recomendado:

`dental.reverb`

Comando esperado:

```bash
php artisan reverb:start --host=0.0.0.0 --port=8081
```

Exponer puerto:

`8081:8081`

## Makefile

Comandos sugeridos:

1. `make reverb`: levanta o ejecuta Reverb.
2. `make up`: debe levantar app, pgsql, vite, worker y reverb cuando aplique.
3. `make logs-reverb`: muestra logs del servicio.

## Vite / Echo

Archivo probable:

`resources/js/app.js`

Configurar Laravel Echo con Reverb para que Livewire/Filament pueda escuchar eventos privados.

## Seguridad

1. El canal `admin-notifications` debe ser privado.
2. Solo usuarios autenticados del panel admin deben poder suscribirse.
3. No emitir datos clinicos sensibles en payload broadcast.
4. El payload debe incluir IDs y resumen minimo.

## Archivos A Modificar

1. `composer.json`
2. `.env.docker`
3. `config/broadcasting.php`
4. `config/reverb.php`
5. `routes/channels.php`
6. `docker-compose.yml`
7. `docker-compose.override.yml`
8. `Makefile`
9. `resources/js/app.js`
10. `vite.config.js`

## Criterios De Aceptacion

1. Reverb inicia correctamente en Docker.
2. Laravel puede emitir un evento broadcast.
3. Livewire recibe el evento desde una pagina autenticada.
4. Usuarios no autenticados no pueden suscribirse al canal privado.
5. Vite no rompe assets existentes.

## Riesgos / Dependencias

1. Requiere variables de entorno consistentes entre Laravel, Vite y Docker.
2. Puede requerir ajustes de puertos en WSL/Linux.
3. Si Filament carga assets en panel, hay que evitar doble inicializacion de Echo.

## Checklist De Implementacion

1. Revisar si Reverb ya esta instalado.
2. Instalar dependencia si falta.
3. Publicar/configurar Reverb.
4. Configurar broadcasting.
5. Agregar Docker service.
6. Actualizar Makefile.
7. Configurar Echo en Vite.
8. Crear canal privado.
9. Probar evento manual.
10. Ejecutar tests.
