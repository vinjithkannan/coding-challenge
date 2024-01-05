#-include .env .env.local
export

DOCKER 			   = docker
DOCKER_COMPOSE 	   = docker compose
EXEC 			   = $(DOCKER_COMPOSE) exec
APP				   = $(EXEC) -it php
NODE			   = $(DOCKER_COMPOSE) run -it node
COMPOSER		   = $(APP) composer
CONSOLE			   = $(APP) bin/console
PHP				   = $(APP) php
PNPM			   = $(DOCKER_COMPOSE) run --rm node pnpm
PLAYBOOK 		   = ansible-playbook
QA                 = $(DOCKER_COMPOSE) run --rm qa
SSL_DIR			   = devops/caddy/certs
PHPSTAN_QA_VERSION = ^1.11@dev
ANSI_COLOR		   = --ansi

# Colors
GREEN  := $(shell tput -Txterm setaf 2)
RED    := $(shell tput -Txterm setaf 1)
YELLOW := $(shell tput -Txterm setaf 3)
BLUE   := $(shell tput -Txterm setaf 4)
RESET  := $(shell tput -Txterm sgr0)

.DEFAULT_GOAL := help

## —— 🔥 Project ——
.env.local: .env
	@if [ -f .env.local ]; then \
		echo '${YELLOW}The ".env" has changed. You may want to update your copy .env.local accordingly (this message will only appear once).'; \
		touch .env.local; \
		exit 1; \
	else \
		cp .env .env.local; \
		echo "${YELLOW}Modify it according to your needs and rerun the command."; \
		exit 1; \
	fi

.PHONY: install
install: ## Project Installation
install: .env.local ssl build start vendor assets-build db-reset open
	@echo "${GREEN}The application is available at: https://$(SERVER_NAME)"

.PHONY: cache-clear
cache-clear: ## Clear cache
	$(CONSOLE) cache:clear

.PHONY: ssl
ssl: ## Build SSL using mkcert
	rm -rf $(SSL_DIR) && mkdir $(SSL_DIR) && cd $(SSL_DIR) && mkcert $(SERVER_NAME)
	@echo "${GREEN}New SSL certificate has been created.${RESET}"

##
## —— 🐳 Docker ——
docker-compose.override.yml: docker-compose.override.yml.dist
	@if [ -f docker-compose.override.yml ]; then \
		echo '${YELLOW}/!!!\ "docker-compose.override.yml.dist" has changed. You may want to update your copy accordingly (this message will only appear once).'; \
		touch docker-compose.override.yml; \
		exit 1; \
	else \
		cp docker-compose.override.yml.dist docker-compose.override.yml; \
		echo "cp docker-compose.override.yml.dist docker-compose.override.yml"; \
		echo "${YELLOW}Modify it according to your needs and rerun the command."; \
		exit 1; \
	fi

.PHONY: build
build: ## Build the container
build: docker-compose.override.yml
	$(DOCKER_COMPOSE) build
	$(DOCKER_COMPOSE) build node
	$(DOCKER_COMPOSE) build qa

.PHONY: start
start: ## Start the containers
start:
	$(DOCKER_COMPOSE) up -d --remove-orphans

.PHONY: stop
stop: ## Stop the containers
	$(DOCKER_COMPOSE) stop

.PHONY: restart
restart: ## restart the containers
restart: stop start

.PHONY: kill
kill: ## Forces running containers to stop by sending a SIGKILL signal
	$(DOCKER_COMPOSE) kill

.PHONY: down
down: ## Stops containers
	$(DOCKER_COMPOSE) down --volumes --remove-orphans

.PHONY: reset
reset: ## Stop and start a fresh install of the project
reset: down install

.PHONY: open
open: ## Open the project in the browser
open:
	@echo "${GREEN}Opening https://$(SERVER_NAME)"
	@open https://$(SERVER_NAME)

.PHONY: php-bash
php-bash: ## Open a bash in the php container
php-bash:
	$(APP) bash

.PHONY: node-shell
node-shell: ## Open a shell in the node container
node-shell:
	$(NODE) sh

.PHONY: ps
ps: ## List containers
ps:
	$(DOCKER_COMPOSE) ps

##
## —— 🎻 Composer ——
vendor: ## Install dependencies
vendor: .env.local composer.lock
	$(APP) composer install --ignore-platform-req=php $(ANSI_COLOR)

.PHONY: composer-update
composer-update: ## Update dependencies
	$(APP) composer update --ignore-platform-req=php $(ANSI_COLOR)

.PHONY: composer-validate
composer-validate: ## Validate composer.json
	$(APP) composer validate --strict $(ANSI_COLOR)

.PHONY: composer-outdated
composer-outdated: ## Show outdated composer dependencies
	$(APP) composer outdated --direct --strict $(ANSI_COLOR)

##
## —— 📊 Database ——
.PHONY: db-wait
db-wait: ## Wait for database to be ready
db-wait:
	@echo "${YELLOW}Waiting for database to be ready...${RESET}"
	@$(APP) php -r "set_time_limit(60);for(;;){if(@fsockopen('database',5432)){break;}echo \"${RED}Database is not ready. Retrying...${RESET}\n\";sleep(1);}"
	@echo "${GREEN}Database is ready.${RESET}"

.PHONY: db-reset
env ?= dev
db-reset: ## Reset Database, you can use it like : make db-reset env=test or just make db-reset
db-reset: vendor db-wait
	@echo "${YELLOW}Resetting ${BLUE}${env}${YELLOW} database...${RESET}"
	$(CONSOLE) doctrine:database:drop --if-exists --force --env=$(env) $(ANSI_COLOR)
	$(CONSOLE) doctrine:database:create --env=$(env) $(ANSI_COLOR)
	$(CONSOLE) doctrine:schema:create --env=$(env) $(ANSI_COLOR)
	@echo "${GREEN}Database ${BLUE}${env}${GREEN} has been reset.${RESET}"

.PHONY: db-migrate
env ?= dev
db-migrate: ## Migrate Database, you can use it like : make db-migrate env=test or just make db-migrate
db-migrate:
	@echo "${YELLOW}Migrating ${BLUE}${env}${YELLOW} database...${RESET}"
	$(CONSOLE) doctrine:migrations:migrate --no-interaction --env=$(env) $(ANSI_COLOR)
	@echo "${GREEN}Database ${BLUE}${env}${GREEN} has been migrated.${RESET}"

.PHONY: load-fixtures
env ?= dev
load-fixtures: ## Load fixtures, you can use it like : make load-fixtures env=test or just make load-fixtures
load-fixtures: db-reset db-migrate
	@echo "${YELLOW}Loading fixtures for ${BLUE}${env}${YELLOW}...${RESET}"
	$(CONSOLE) hautelook:fixtures:load --no-interaction --env=$(env) $(ANSI_COLOR)
	@echo "${GREEN}Fixtures loaded.${RESET}"

##
## —— Assets ——
node_modules: ## Install assets
node_modules:
	$(PNPM) install --force --color

public/bundles: ## Create public assets from vendors
public/bundles: vendor
	$(CONSOLE) assets:install --symlink $(ANSI_COLOR)

public/build: ## Build assets
public/build: assets assets/**/*
	@if [[ "prod" = "$(APP_ENV)" ]]; then \
		$(PNPM) build --color; \
	else \
		$(PNPM) dev --color; \
	fi

.PHONY: assets-build
assets-build: ## Install and build assets
assets-build: node_modules public/bundles public/build


.PHONY: assets-watch
assets-watch: ## Build assets with watch mode on
assets-watch:
	$(PNPM) watch

##
## —— ✅ Test ——
.PHONY: tests
tests: env=test ## Run all tests
tests: db-reset unit-tests

.PHONY: unit-tests
unit-tests: ## Run unit tests
unit-tests: vendor/bin/.phpunit
	$(PHP) ./vendor/bin/simple-phpunit tests --exclude-group panther --testdox

.PHONY: panther-tests
panther-tests: env=test ## Run tests using Panther
panther-tests: vendor/bin/.phpunit db-reset load-fixtures
	$(PHP) ./vendor/bin/simple-phpunit tests --group panther --testdox

vendor/bin/.phpunit: vendor
	$(APP) vendor/bin/simple-phpunit install

##
## —— ✨ Code Quality ——
.PHONY: qa
qa: ## Run all code quality checks
qa: lint-yaml lint-twig twigcs lint-container phpdd phpcs phpmnd php-cs-fixer phpstan phpinsights security-check

.PHONY: qa-fix
qa-fix: ## Run all code quality fixers
qa-fix: phpinsights-fix php-cs-fixer-apply

.PHONY: lint-yaml
lint-yaml: ## Lints YAML files
lint-yaml:
	$(QA) yaml-lint config --parse-tags $(ANSI_COLOR)

.PHONY: lint-twig
lint-twig: ## Lints Twig files
lint-twig:
	$(CONSOLE) lint:twig templates $(ANSI_COLOR)

.PHONY: lint-containers
lint-container: ## Lints containers
lint-container:
	$(CONSOLE) cache:clear --env=prod $(ANSI_COLOR)
	$(CONSOLE) lint:container $(ANSI_COLOR)

.PHONY: phpcs
phpcs: ## PHP_CodeSniffer (https://github.com/squizlabs/PHP_CodeSniffer)
	$(QA) phpcs -p -n --colors --standard=.phpcs.xml src tests --colors

.PHONY: phpmnd
phpmnd: ## Detect magic numbers in your PHP code
	$(QA) phpmnd src tests $(ANSI_COLOR)

.PHONY: phpdd
phpdd: ## Detect deprecations
	$(QA) phpdd src tests $(ANSI_COLOR)

.PHONY: phpstan
phpstan: ## PHP Static Analysis Tool (https://github.com/phpstan/phpstan)
phpstan: vendor/bin/.phpunit
	$(QA) phpstan --memory-limit=-1 analyse $(ANSI_COLOR)

.PHONY: phpinsights
phpinsights: ## PHP Insights (https://phpinsights.com)
	$(QA) phpinsights analyse --no-interaction $(ANSI_COLOR)

.PHONY: phpinsights-fix
phpinsights-fix: ## PHP Insights (https://phpinsights.com)
	$(QA) phpinsights analyse --no-interaction --fix $(ANSI_COLOR)

.PHONY: php-cs-fixer
php-cs-fixer: ## PhpCsFixer (https://cs.symfony.com/)
	$(QA) php-cs-fixer fix --using-cache=no --verbose --diff --dry-run $(ANSI_COLOR)

.PHONY: php-cs-fixer-apply
php-cs-fixer-apply: ## Applies PhpCsFixer fixes
	$(QA) php-cs-fixer fix --using-cache=no --verbose --diff $(ANSI_COLOR)

.PHONY: twigcs
twigcs: ## Twigcs (https://github.com/friendsoftwig/twigcs)
	$(QA) twigcs templates --severity error --display blocking $(ANSI_COLOR)

.PHONY: security-check
security-check: ## SensioLabs Security Checker
	-$(APP) composer audit $(ANSI_COLOR)

.PHONY: lint-dockerfile
lint-dockerfiles: ## Lints Dockerfile files
	$(DOCKER) run --rm -i -v ./hadolint.yaml:/.config/hadolint.yaml hadolint/hadolint < devops/caddy/Dockerfile
	$(DOCKER) run --rm -i -v ./hadolint.yaml:/.config/hadolint.yaml hadolint/hadolint < devops/php/Dockerfile
	$(DOCKER) run --rm -i -v ./hadolint.yaml:/.config/hadolint.yaml hadolint/hadolint < devops/node/Dockerfile
	$(DOCKER) run --rm -i -v ./hadolint.yaml:/.config/hadolint.yaml hadolint/hadolint < devops/qa/Dockerfile
	$(DOCKER) run --rm -i -v ./hadolint.yaml:/.config/hadolint.yaml hadolint/hadolint < devops/database/Dockerfile

##
## —— 🚀 Deployment ——
.PHONY: deploy-staging
deploy-staging: ## Deploy on staging
deploy-staging:
	$(PLAYBOOK) -i devops/iac/hosts.ini devops/iac/deploy.yml --extra-vars "branch=main" --extra-vars "env=staging" --extra-vars "composer_project_name=$(COMPOSER_PROJECT_NAME)" --extra-vars "server_name=staging.alximy.io" --limit 'staging'

.PHONY: deploy-prod
deploy-prod: ## Deploy on prod
deploy-prod:
	$(PLAYBOOK) -i devops/iac/hosts.ini devops/iac/deploy.yml --extra-vars "branch=main" --extra-vars "env=prod" --extra-vars "composer_project_name=$(COMPOSER_PROJECT_NAME)" --extra-vars "server_name=alximy.io" --limit 'production'

##
## —— 🛠️ Others ——
.PHONY: help
help: ## List of commands
	@grep -E '(^[a-z0-9A-Z_-]+:.*?##.*$$)|(^##)' Makefile | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'
