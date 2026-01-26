.PHONY: build up down restart logs shell db-shell redis-shell install migrate fresh seed test npm-dev npm-build

# Get current user's UID and GID
USER_ID := $(shell id -u)
GROUP_ID := $(shell id -g)

# Export for docker-compose
export USER_ID
export GROUP_ID

build:
	docker compose build

up:
	docker compose up -d

down:
	docker compose down

restart:
	docker compose restart

logs:
	docker compose logs -f

shell:
	docker compose exec app bash

db-shell:
	docker compose exec db mysql -u poker -psecret poker_study

redis-shell:
	docker compose exec redis redis-cli

install: build up
	docker compose exec app composer install
	docker compose exec app cp -n .env.example .env || true
	docker compose exec app php artisan key:generate
	docker compose exec app php artisan migrate --seed
	@echo "Installation complete! App available at http://localhost:8080"

migrate:
	docker compose exec app php artisan migrate

fresh:
	docker compose exec app php artisan migrate:fresh --seed

seed:
	docker compose exec app php artisan db:seed

test:
	docker compose exec app php artisan test

npm-dev:
	docker compose exec node npm run dev

npm-build:
	docker compose exec node npm run build

composer:
	docker compose exec app composer $(filter-out $@,$(MAKECMDGOALS))

artisan:
	docker compose exec app php artisan $(filter-out $@,$(MAKECMDGOALS))

%:
	@:
