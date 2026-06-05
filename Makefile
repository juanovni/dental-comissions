.PHONY: help up down build restart shell migrate seed fresh test pint composer npm install clean logs

APP_SERVICE = dental.app
DB_SERVICE = dental.pgsql

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

clean:
	docker compose down -v --rmi all --remove-orphans
