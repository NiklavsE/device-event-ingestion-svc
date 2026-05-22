.PHONY: help up down build install migrate seed test test-prepare lint lint-fix analyse qa shell logs

help:
	@echo "Common targets:"
	@echo "  make up           Start docker-compose stack"
	@echo "  make down         Stop docker-compose stack"
	@echo "  make build        Build app images"
	@echo "  make install      composer install inside the app container"
	@echo "  make migrate      Run migrations"
	@echo "  make seed         Seed sample data"
	@echo "  make test-prepare Ensure the test database exists"
	@echo "  make test         Run phpunit (auto-runs test-prepare first)"
	@echo "  make lint         Run phpcs (style check, no changes)"
	@echo "  make lint-fix     Run phpcbf (auto-fix style)"
	@echo "  make analyse      Run phpstan (static analysis)"
	@echo "  make qa           lint + analyse + test"
	@echo "  make shell        Open a shell in the app container"
	@echo "  make logs         Tail app/worker logs"

up:
	docker compose up -d

down:
	docker compose down

build:
	docker compose build

install:
	docker compose run --rm app composer install --prefer-dist --no-interaction

migrate:
	docker compose exec app php artisan migrate --force

seed:
	docker compose exec app php artisan db:seed --force

test-prepare:
	docker compose exec mysql sh -c 'mysql -uroot -p"$$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS device_event_ingestion_svc_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; GRANT ALL PRIVILEGES ON device_event_ingestion_svc_test.* TO '"'"'app'"'"'@'"'"'%'"'"'; FLUSH PRIVILEGES;"'

test: test-prepare
	docker compose exec app vendor/bin/phpunit

lint:
	docker compose exec app vendor/bin/phpcs

lint-fix:
	docker compose exec app vendor/bin/phpcbf

analyse:
	docker compose exec app vendor/bin/phpstan analyse

qa: lint analyse test

shell:
	docker compose exec app bash

logs:
	docker compose logs -f app worker
