# Executables (local)
DOCKER_COMP = docker compose

# Docker containers
PHP_CONT = $(DOCKER_COMP) exec php

# Executables
PHP      = $(PHP_CONT) php
COMPOSER = $(PHP_CONT) composer
SYMFONY  = $(PHP) bin/console

# Misc
.DEFAULT_GOAL = help
.PHONY        : help build up start down logs sh composer vendor sf cc test coverage coverage-text coverage-clover coverage-all cs-fix cs-check phpstan rector qa qa-fix validate check-imports order-migrate order-migrate-status order-migrate-generate order-migrate-diff invoice-migrate invoice-migrate-status invoice-migrate-generate invoice-migrate-diff

## —— 🎵 🐳 The Symfony Docker Makefile 🐳 🎵 ——————————————————————————————————
help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9\./_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

## —— Docker 🐳 ————————————————————————————————————————————————————————————————
build: ## Builds the Docker images
	@$(DOCKER_COMP) build --pull --no-cache

up: ## Start the docker hub in detached mode (no logs)
	@$(DOCKER_COMP) up --detach

start: build up ## Build and start the containers

down: ## Stop the docker hub
	@$(DOCKER_COMP) down --remove-orphans

logs: ## Show live logs
	@$(DOCKER_COMP) logs --tail=0 --follow

sh: ## Connect to the FrankenPHP container
	@$(PHP_CONT) sh

bash: ## Connect to the FrankenPHP container via bash so up and down arrows go to previous commands
	@$(PHP_CONT) bash

test: ## Start tests with phpunit, pass the parameter "c=" to add options to phpunit, example: make test c="--group e2e --stop-on-failure"
	@$(eval c ?=)
	@$(DOCKER_COMP) exec -e APP_ENV=test php bin/phpunit --no-coverage $(c)

coverage: ## Run tests with code coverage (HTML report)
	@$(DOCKER_COMP) exec -e APP_ENV=test -e XDEBUG_MODE=coverage php bin/phpunit --coverage-html coverage/html

coverage-text: ## Run tests with code coverage (text output)
	@$(DOCKER_COMP) exec -e APP_ENV=test -e XDEBUG_MODE=coverage php bin/phpunit --coverage-text

coverage-clover: ## Run tests with code coverage (Clover XML report)
	@$(DOCKER_COMP) exec -e APP_ENV=test -e XDEBUG_MODE=coverage php bin/phpunit --coverage-clover coverage/clover.xml

coverage-all: ## Run tests with all coverage reports
	@$(DOCKER_COMP) exec -e APP_ENV=test -e XDEBUG_MODE=coverage php bin/phpunit --coverage-html coverage/html --coverage-text --coverage-clover coverage/clover.xml


## —— Composer 🧙 ——————————————————————————————————————————————————————————————
composer: ## Run composer, pass the parameter "c=" to run a given command, example: make composer c='req symfony/orm-pack'
	@$(eval c ?=)
	@$(COMPOSER) $(c)

vendor: ## Install vendors according to the current composer.lock file
vendor: c=install --prefer-dist --no-dev --no-progress --no-scripts --no-interaction
vendor: composer

## —— Symfony 🎵 ———————————————————————————————————————————————————————————————
sf: ## List all Symfony commands or pass the parameter "c=" to run a given command, example: make sf c=about
	@$(eval c ?=)
	@$(SYMFONY) $(c)

cc: c=c:c ## Clear the cache
cc: sf

## —— Migrations 🔄 ————————————————————————————————————————————————————————————
order-migrate: ## Run Order microservice migrations
	@$(SYMFONY) doctrine:migrations:migrate --configuration=config/migrations_order.yaml --no-interaction

order-migrate-status: ## Check Order microservice migration status
	@$(SYMFONY) doctrine:migrations:status --configuration=config/migrations_order.yaml

order-migrate-generate: ## Generate new Order microservice migration
	@$(SYMFONY) doctrine:migrations:generate --configuration=config/migrations_order.yaml

order-migrate-diff: ## Generate Order migration based on entity changes
	@$(SYMFONY) doctrine:migrations:diff --configuration=config/migrations_order.yaml

invoice-migrate: ## Run Invoice microservice migrations
	@$(SYMFONY) doctrine:migrations:migrate --configuration=config/migrations_invoice.yaml --no-interaction

invoice-migrate-status: ## Check Invoice microservice migration status
	@$(SYMFONY) doctrine:migrations:status --configuration=config/migrations_invoice.yaml

invoice-migrate-generate: ## Generate new Invoice microservice migration
	@$(SYMFONY) doctrine:migrations:generate --configuration=config/migrations_invoice.yaml

invoice-migrate-diff: ## Generate Invoice migration based on entity changes
	@$(SYMFONY) doctrine:migrations:diff --configuration=config/migrations_invoice.yaml

## —— Code Quality 🔍 ——————————————————————————————————————————————————————————
cs-fix: ## Fix code style with PHP-CS-Fixer
	@$(PHP) vendor/bin/php-cs-fixer fix --verbose --allow-risky yes

cs-check: ## Check code style with PHP-CS-Fixer (dry-run)
	@$(PHP) vendor/bin/php-cs-fixer fix --dry-run --diff --allow-risky yes

phpstan: ## Run PHPStan static analysis
	@$(PHP) vendor/bin/phpstan analyse --memory-limit=1G

rector: ## Run Rector for automated refactoring (dry-run)
	@$(PHP) vendor/bin/rector process --dry-run

rector-fix: ## Apply Rector automated refactoring
	@$(PHP) vendor/bin/rector process

qa: ## Run full quality assurance suite
	@echo "🔍 Running code quality checks..."
	@make cs-check
	@echo "📊 Running static analysis..."
	@make phpstan
	@echo "🧪 Running tests..."
	@make test
	@echo "✅ Quality assurance complete!"

qa-fix: ## Fix all code quality issues
	@echo "🔧 Fixing code style..."
	@make cs-fix
	@echo "🔄 Applying automated refactoring..."
	@make rector-fix
	@echo "✅ All fixes applied!"

validate: ## Quick validation for missing imports and basic issues
	@echo "🔍 Validating imports and class existence..."
	@$(PHP) vendor/bin/phpstan analyse --error-format=table --no-progress --quiet
	@echo "✅ Validation complete!"

check-imports: ## Specifically check for missing imports and class issues
	@echo "📦 Checking for missing imports and class issues..."
	@$(PHP) vendor/bin/phpstan analyse --error-format=table --no-progress | grep -E "(class|interface|trait).*(not found|does not exist)" || echo "✅ No missing import issues found"
