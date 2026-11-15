.DEFAULT_GOAL := help
SHELL := /bin/bash

DC      := docker compose
DC_PROD := docker compose -f docker-compose.prod.yml --env-file backend/.env.production
PROD_ENV := backend/.env.production

# Extra arguments for pass-through commands, e.g. make artisan ARGS="route:list"
ARGS ?=

##@ Help

help: ## Show this list of commands
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage: make \033[36m<command>\033[0m [ARGS=\"...\"]\n"} \
		/^[a-zA-Z0-9_-]+:.*?##/ { printf "  \033[36m%-16s\033[0m %s\n", $$1, $$2 } \
		/^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) }' $(MAKEFILE_LIST)
	@echo

##@ Development

setup: ## One-time dev setup: env files, build, start, migrate
	@[ -f .env ] || cp .env.example .env
	@[ -f backend/.env ] || cp backend/.env.example backend/.env
	$(DC) up -d --build --wait
	$(DC) exec laravel php artisan migrate --force
	@echo -e "\n  App:     http://localhost:$${APP_PORT:-8000}\n  RAG API: http://localhost:$${RAG_PORT:-8001}/docs\n  Mail:    http://localhost:$${MAILPIT_PORT:-8025}\n"

up: ## Start the dev stack
	$(DC) up -d --wait

down: ## Stop the dev stack
	$(DC) down

restart: ## Restart the dev stack
	$(DC) restart

build: ## Rebuild dev images
	$(DC) build

ps: ## Show container status
	$(DC) ps

mail: ## Open the Mailpit inbox (all dev email lands here)
	@open http://localhost:$${MAILPIT_PORT:-8025} 2>/dev/null || echo "Mailpit: http://localhost:$${MAILPIT_PORT:-8025}"

logs: ## Follow logs (all, or one: make logs ARGS=queue)
	$(DC) logs -f $(ARGS)

clean: ## Stop and DELETE all dev data (database, redis, node_modules)
	@read -p "This deletes the dev database and volumes. Continue? [y/N] " ans && [ "$$ans" = y ]
	$(DC) down -v --remove-orphans

##@ Laravel

artisan: ## Run artisan (make artisan ARGS="route:list")
	$(DC) exec laravel php artisan $(ARGS)

composer: ## Run composer (make composer ARGS="require foo/bar")
	$(DC) exec laravel composer $(ARGS)

npm: ## Run npm (make npm ARGS="install foo")
	$(DC) exec vite npm $(ARGS)

migrate: ## Run database migrations
	$(DC) exec laravel php artisan migrate

fresh: ## Drop all tables, re-migrate and seed
	$(DC) exec laravel php artisan migrate:fresh --seed

tinker: ## Open a Tinker REPL
	$(DC) exec laravel php artisan tinker

test: ## Run Laravel tests (filter: make test ARGS="--filter=Foo")
	$(DC) exec laravel php artisan test --compact $(ARGS)

lint: ## Fix Laravel code style with Pint
	$(DC) exec laravel composer lint

types: ## Run Larastan static analysis
	$(DC) exec laravel composer types:check

shell: ## Open a shell in the Laravel container
	$(DC) exec laravel bash

##@ RAG service (Python)

rag-test: ## Run Python tests
	$(DC) exec rag uv run pytest $(ARGS)

rag-lint: ## Fix Python lint issues and format
	$(DC) exec rag uv run ruff check --fix .
	$(DC) exec rag uv run ruff format .

rag-add: ## Add a Python package (make rag-add ARGS="pgvector")
	$(DC) exec rag uv add $(ARGS)

rag-shell: ## Open a shell in the RAG container
	$(DC) exec rag bash

##@ Database

db-shell: ## Open psql on the dev database
	$(DC) exec postgres sh -c 'psql -U "$$POSTGRES_USER" -d "$$POSTGRES_DB"'

##@ Checks

check: test types rag-test ## Run all tests and static analysis

##@ Production

prod: ## Build and start production, migrate, cache config
	@if [ ! -f $(PROD_ENV) ]; then \
		cp backend/.env.production.example $(PROD_ENV); \
		echo -e "\nCreated $(PROD_ENV). Set DB_PASSWORD and APP_URL in it, then run 'make prod' again.\n"; \
		exit 1; \
	fi
	@if grep -q '^DB_PASSWORD=change-me' $(PROD_ENV); then \
		echo "Set a real DB_PASSWORD in $(PROD_ENV) first."; exit 1; \
	fi
	$(DC_PROD) build
	@if ! grep -q '^APP_KEY=base64:' $(PROD_ENV); then \
		key=$$($(DC_PROD) run --rm --no-deps -T laravel php artisan key:generate --show) && \
		sed -i.bak "s|^APP_KEY=.*|APP_KEY=$$key|" $(PROD_ENV) && rm $(PROD_ENV).bak && \
		echo "Generated APP_KEY in $(PROD_ENV)"; \
	fi
	$(DC_PROD) up -d --wait
	$(DC_PROD) exec laravel php artisan migrate --force
	$(DC_PROD) exec laravel php artisan optimize
	$(DC_PROD) exec queue php artisan optimize
	$(DC_PROD) exec scheduler php artisan optimize

prod-down: ## Stop production
	$(DC_PROD) down

prod-ps: ## Show production container status
	$(DC_PROD) ps

prod-logs: ## Follow production logs (make prod-logs ARGS=queue)
	$(DC_PROD) logs -f $(ARGS)

prod-artisan: ## Run artisan in production (make prod-artisan ARGS="about")
	$(DC_PROD) exec laravel php artisan $(ARGS)

.PHONY: help setup up down restart build ps logs clean mail artisan composer npm migrate fresh tinker \
	test lint types shell rag-test rag-lint rag-add rag-shell db-shell check \
	prod prod-down prod-ps prod-logs prod-artisan
