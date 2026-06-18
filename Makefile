.PHONY: help up down build restart shell migrate seed fresh test pint composer npm install clean logs reverb logs-reverb vite worker logs-vite logs-worker

APP_SERVICE = dental.app
DB_SERVICE = dental.pgsql
REVERB_SERVICE = dental.reverb
VITE_SERVICE = dental.vite
WORKER_SERVICE = dental.worker

help:
	@echo "Dental Commissions MVP - Docker commands"
	@echo ""
	@echo "  make up          Start all containers"
	@echo "  make down        Stop all containers"
	@echo "  make build       Build images"
	@echo "  make restart     Restart all containers"
	@echo "  make shell       Open shell in app container"
	@echo "  make migrate     Run migrations"
	@echo "  make seed        Run seeders"
	@echo "  make fresh       Fresh migrate with seed"
	@echo "  make test        Run test suite"
	@echo "  make pint        Format PHP code"
	@echo "  make composer    Run composer (e.g. make composer cmd=install)"
	@echo "  make npm         Run npm (e.g. make npm cmd=install)"
	@echo "  make logs        Tail app container logs"
	@echo "  make reverb      Run Reverb server"
	@echo "  make logs-reverb Tail Reverb container logs"
	@echo "  make vite        Run Vite dev server"
	@echo "  make worker      Run queue worker"
	@echo "  make logs-vite   Tail Vite container logs"
	@echo "  make logs-worker Tail worker container logs"
	@echo "  make clean       Remove all containers, volumes and images"

up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build --no-cache

restart:
	docker compose restart

shell:
	docker compose exec $(APP_SERVICE) bash

migrate:
	docker compose exec $(APP_SERVICE) php artisan migrate --force

seed:
	docker compose exec $(APP_SERVICE) php artisan db:seed --force

fresh:
	docker compose exec $(APP_SERVICE) php artisan migrate:fresh --seed --force

test:
	docker compose exec $(APP_SERVICE) php artisan test

pint:
	docker compose exec $(APP_SERVICE) ./vendor/bin/pint

composer:
	docker compose exec $(APP_SERVICE) composer $(cmd)

npm:
	docker compose exec $(APP_SERVICE) npm $(cmd)

logs:
	docker compose logs -f $(APP_SERVICE)

reverb:
	docker compose up -d $(REVERB_SERVICE)

logs-reverb:
	docker compose logs -f $(REVERB_SERVICE)

vite:
	docker compose up -d $(VITE_SERVICE)

worker:
	docker compose up -d $(WORKER_SERVICE)

logs-vite:
	docker compose logs -f $(VITE_SERVICE)

logs-worker:
	docker compose logs -f $(WORKER_SERVICE)

clean:
	docker compose down -v --rmi all --remove-orphans
