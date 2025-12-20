PROJECT_NAME = wardianlabsapp
DOCKER_COMPOSE_COMMAND = docker compose -p $(PROJECT_NAME)
DOCKER_COMPOSE_FILE = docker-compose.yml
ENV_FILE = .env
DOCKER_COMPOSE_RUN = $(DOCKER_COMPOSE_COMMAND) -f $(DOCKER_COMPOSE_FILE) run --rm
DOCKER_EXEC = docker compose exec
DOCKER_EXEC_TEST = docker compose exec -e APP_ENV=test app
COMPOSER = composer

behat-tests:
	$(DOCKER_EXEC_TEST) vendor/bin/behat -p main --format=progress -v
.PHONY: behat-tests

ci-tests: ci-composer-install yarn-install yarn-dev create-database migrate-test phpunit-tests
.PHONY: ci-tests

ci-composer-install:
	$(DOCKER_COMPOSE_RUN) --entrypoint /usr/bin/composer --rm app install
.PHONY: ci-composer-install

phpunit-tests:
	$(DOCKER_COMPOSE_RUN) -e SYMFONY_DECRYPTION_SECRET=${SYMFONY_DECRYPTION_SECRET} -e APP_ENV=test app vendor/bin/phpunit -c /app/phpunit.xml
.PHONY: phpunit-tests

phpunit-tests-unit:
	$(DOCKER_EXEC_TEST) vendor/bin/phpunit --testsuite Unit
.PHONY: phpunit-tests-unit

phpunit-tests-functional:
	$(DOCKER_EXEC_TEST) vendor/bin/phpunit --testsuite Functional
.PHONY: phpunit-tests-functional

phpunit-tests-integration:
	$(DOCKER_EXEC_TEST) vendor/bin/phpunit --testsuite Integration
.PHONY: phpunit-tests-integration

phpunit-tests-acceptance:
	$(DOCKER_EXEC_TEST) vendor/bin/phpunit --testsuite Acceptance
.PHONY: phpunit-tests-acceptance

phpunit-tests-e2e:
	$(DOCKER_EXEC_TEST) vendor/bin/phpunit --testsuite E2E
.PHONY: phpunit-tests-e2e

test: phpunit-tests
.PHONY: test

setup: build-local-image composer-install
.PHONY: setup

start: ## Start containers
	$(DOCKER_COMPOSE_COMMAND) -f $(DOCKER_COMPOSE_FILE) up --build -d
start: composer-install yarn-install yarn-dev
.PHONY: start

stop: ## Stop all or c=<name> containers
	$(DOCKER_COMPOSE_COMMAND) -f $(DOCKER_COMPOSE_FILE) stop
.PHONY: stop

logs: ## Show logs for all or c=<name> containers
	$(DOCKER_COMPOSE_COMMAND) -f $(DOCKER_COMPOSE_FILE) logs --tail=100 -f
.PHONY: logs

.PHONY: composer-install
composer-install: CMD=install

.PHONY: composer-update
composer-update: CMD=update

.PHONY: composer-require
composer-require: CMD=require

.PHONY: composer-require-module
composer-require-module: CMD=require $(module)

.PHONY: composer
composer composer-install composer-update composer-require composer-require-module:
	$(DOCKER_COMPOSE_RUN) app composer $(CMD) \
			--ignore-platform-reqs \
			--no-ansi

.PHONY: bash
bash:
	$(DOCKER_COMPOSE_RUN) app bash

.PHONY: exec
exec:
	$(DOCKER_EXEC) app bash

yarn-install:
	$(DOCKER_COMPOSE_RUN) -w /app app yarn install
.PHONY: yarn-install

yarn-dev:
	$(DOCKER_COMPOSE_RUN) -w /app app yarn dev
.PHONY: yarn-dev

watch:
	$(DOCKER_COMPOSE_RUN) -w /app app yarn watch
.PHONY: watch

build-local-image:
	$(DOCKER_COMPOSE_COMMAND) -f $(DOCKER_COMPOSE_FILE) build app
.PHONY: build-local-image

.PHONY: static-analysis
static-analysis:
	$(DOCKER_COMPOSE_COMMAND) exec app ./vendor/bin/psalm

.PHONY: cs-fix
cs-fix:
	$(DOCKER_COMPOSE_COMMAND) exec app /app/vendor/bin/php-cs-fixer fix --config /app/.php-cs-fixer.dist.php --allow-risky=yes

.PHONY: migrate
migrate:
	$(DOCKER_COMPOSE_RUN) -w /app app bin/console doctrine:migrations:migrate

.PHONY: create-database
create-database:
	$(DOCKER_COMPOSE_RUN) -w /app app bin/console doc:database:create

.PHONY: create-database-test
create-database-test:
	$(DOCKER_COMPOSE_RUN) -w /app app bin/console --env=test doc:database:create

.PHONY: migrate-test
migrate-test:
	$(DOCKER_COMPOSE_RUN) -w /app app bin/console --env=test doctrine:migrations:migrate

.PHONY: prod-start
prod-start:
	$(DOCKER_COMPOSE_COMMAND) -f docker-compose.prod.yml build app
	$(DOCKER_COMPOSE_COMMAND) -f docker-compose.prod.yml stop
	$(DOCKER_COMPOSE_COMMAND) -f docker-compose.prod.yml rm -f app
	$(DOCKER_COMPOSE_COMMAND) -f docker-compose.prod.yml up -d

.PHONY: restart-workers
restart-workers:
	$(DOCKER_COMPOSE_COMMAND) -f docker-compose.yml restart workers_dev workers_test

.PHONY: clear-cache
clear-cache:
	$(DOCKER_COMPOSE_COMMAND) -f docker-compose.yml exec app bin/console cache:clear
	$(DOCKER_COMPOSE_COMMAND) -f docker-compose.yml exec workers_dev bin/console cache:clear
	$(DOCKER_COMPOSE_COMMAND) -f docker-compose.yml exec workers_test bin/console cache:clear

.PHONY: connect-staging-console
connect-staging-console:
	./connect-to-ecs.sh console staging

.PHONY: connect-production-console
connect-production-console:
	./connect-to-ecs.sh console production

.PHONY: connect-staging-tunnel
connect-staging-tunnel:
	./connect-to-ecs.sh tunnel staging

.PHONY: connect-production-tunnel
connect-production-tunnel:
	./connect-to-ecs.sh tunnel production

.PHONY: sqs-list-queues
sqs-list-queues:
	aws --endpoint-url=http://localhost:4566 --region us-east-1 sqs list-queues

.PHONY: sqs-receive-messages-dev
sqs-receive-messages-dev:
	 aws --endpoint-url=http://localhost:4566 sqs receive-message --region us-east-1 --queue-url http://localhost:4566/000000000000/projects-creation-dev --max-number-of-messages 10

.PHONY: sqs-purge-queue-dev
sqs-purge-queue-dev:
	 aws  --region us-east-1 --endpoint-url=http://localhost:4566 sqs purge-queue --queue-url http://localhost:4566/000000000000/projects-creation-dev

.PHONY: sqs-receive-messages-test
sqs-receive-messages-test:
	 aws --endpoint-url=http://localhost:4566 sqs receive-message --region us-east-1 --queue-url http://localhost:4566/000000000000/projects-creation-test --max-number-of-messages 10

.PHONY: sqs-purge-queue-test
sqs-purge-queue-test:
	 aws  --region us-east-1 --endpoint-url=http://localhost:4566 sqs purge-queue --queue-url http://localhost:4566/000000000000/projects-creation-test
