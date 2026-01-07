.PHONY: install test lint format build serve prepare clear docker-up docker-down docker-logs test-compatibility test-compatibility-docker clean-compatibility test-grpc-docker

install:
	composer install

test:
	composer test

lint:
	composer lint:test

format:
	composer format

build:
	composer build

serve:
	composer serve

prepare:
	composer prepare

clear:
	composer clear

# Test gRPC support in Docker
test-grpc-docker:
	@echo "Testing gRPC support on PHP 8.2..."
	@docker run --rm -v $(PWD):/app -w /app -e PHP_EXTENSION_GRPC=1 thecodingmachine/php:8.2-v4-cli \
		sh -c "composer require open-telemetry/transport-grpc --no-update && \
		composer update --prefer-dist --no-interaction --ignore-platform-reqs && \
		vendor/bin/phpunit --filter ProtocolTest::test_grpc_protocol_initialization"
	@echo "Restoring original dependencies..."
	@composer install --no-interaction

# Test compatibility with different PHP and Laravel versions (Local)
test-compatibility:
	@cp composer.json composer.json.bak
	@cp composer.lock composer.lock.bak
	-@$(MAKE) run-compatibility-tests
	@echo "Restoring original composer.json/lock..."
	@mv composer.json.bak composer.json
	@mv composer.lock.bak composer.lock
	@composer install --no-interaction

run-compatibility-tests:
	@echo "Testing Laravel 10 (PHP 8.1+)..."
	@composer require "illuminate/support:^10.0" "orchestra/testbench:^8.0" --dev --no-update --no-interaction && composer update --prefer-dist --no-interaction --ignore-platform-reqs && vendor/bin/phpunit
	@echo "Testing Laravel 11 (PHP 8.2+)..."
	@composer require "illuminate/support:^11.0" "orchestra/testbench:^9.0" --dev --no-update --no-interaction && composer update --prefer-dist --no-interaction --ignore-platform-reqs && vendor/bin/phpunit
	@echo "Testing Laravel 12 (PHP 8.3+)..."
	@composer require "illuminate/support:^12.0" "orchestra/testbench:^10.0" --dev --no-update --no-interaction && composer update --prefer-dist --no-interaction --ignore-platform-reqs && vendor/bin/phpunit

# Test compatibility with different PHP and Laravel versions (Docker)
test-compatibility-docker:
	@cp composer.json composer.json.bak
	@cp composer.lock composer.lock.bak
	-@$(MAKE) run-docker-compatibility-tests
	@echo "Restoring original composer.json/lock..."
	@mv composer.json.bak composer.json
	@mv composer.lock.bak composer.lock
	@composer install --no-interaction

run-docker-compatibility-tests:
	# laravel 10
	@echo "Testing Laravel 10 on PHP 8.1..."
	@$(MAKE) docker-run-test PHP_VERSION=8.1 LARAVEL_VERSION=10.0 TESTBENCH_VERSION=8.0 IMAGE=thecodingmachine/php:8.1-v4-cli
	@echo "Testing Laravel 10 on PHP 8.2..."
	@$(MAKE) docker-run-test PHP_VERSION=8.2 LARAVEL_VERSION=10.0 TESTBENCH_VERSION=8.0 IMAGE=thecodingmachine/php:8.2-v4-cli
	@echo "Testing Laravel 10 on PHP 8.3..."
	@$(MAKE) docker-run-test PHP_VERSION=8.3 LARAVEL_VERSION=10.0 TESTBENCH_VERSION=8.0 IMAGE=thecodingmachine/php:8.3-v4-cli
	@echo "Testing Laravel 10 on PHP 8.4..."
	@$(MAKE) docker-run-test PHP_VERSION=8.4 LARAVEL_VERSION=10.0 TESTBENCH_VERSION=8.0 IMAGE=thecodingmachine/php:8.4-v4-cli
	@echo "Testing Laravel 10 on PHP 8.5..."
	@$(MAKE) docker-run-test PHP_VERSION=8.5 LARAVEL_VERSION=10.0 TESTBENCH_VERSION=8.0 IMAGE=composer:latest

	# laravel 11
	@echo "Testing Laravel 11 on PHP 8.2..."
	@$(MAKE) docker-run-test PHP_VERSION=8.2 LARAVEL_VERSION=11.0 TESTBENCH_VERSION=9.0 IMAGE=thecodingmachine/php:8.2-v4-cli
	@echo "Testing Laravel 11 on PHP 8.3..."
	@$(MAKE) docker-run-test PHP_VERSION=8.3 LARAVEL_VERSION=11.0 TESTBENCH_VERSION=9.0 IMAGE=thecodingmachine/php:8.3-v4-cli
	@echo "Testing Laravel 11 on PHP 8.4..."
	@$(MAKE) docker-run-test PHP_VERSION=8.4 LARAVEL_VERSION=11.0 TESTBENCH_VERSION=9.0 IMAGE=thecodingmachine/php:8.4-v4-cli
	@echo "Testing Laravel 11 on PHP 8.5..."
	@$(MAKE) docker-run-test PHP_VERSION=8.5 LARAVEL_VERSION=11.0 TESTBENCH_VERSION=9.0 IMAGE=composer:latest

	# laravel 12
	@echo "Testing Laravel 12 on PHP 8.3..."
	@$(MAKE) docker-run-test PHP_VERSION=8.3 LARAVEL_VERSION=12.0 TESTBENCH_VERSION=10.0 IMAGE=thecodingmachine/php:8.3-v4-cli
	@echo "Testing Laravel 12 on PHP 8.4..."
	@$(MAKE) docker-run-test PHP_VERSION=8.4 LARAVEL_VERSION=12.0 TESTBENCH_VERSION=10.0 IMAGE=thecodingmachine/php:8.4-v4-cli
	@echo "Testing Laravel 12 on PHP 8.5..."
	@$(MAKE) docker-run-test PHP_VERSION=8.5 LARAVEL_VERSION=12.0 TESTBENCH_VERSION=10.0 IMAGE=composer:latest

docker-run-test:
	@docker run --rm -v $(PWD):/app -w /app $(IMAGE) \
		sh -c "composer require 'illuminate/support:^$(LARAVEL_VERSION)' 'orchestra/testbench:^$(TESTBENCH_VERSION)' --dev --no-update --no-interaction && \
		composer update --prefer-dist --no-interaction --ignore-platform-reqs && \
		vendor/bin/phpunit"

docker-up:
	docker-compose up -d

docker-down:
	docker-compose down

docker-logs:
	docker-compose logs -f

docker-restart:
	docker-compose restart

# Clean up compatibility test artifacts
clean-compatibility:
	@if [ -f composer.json.bak ]; then \
		echo "Restoring composer.json from backup..."; \
		mv composer.json.bak composer.json; \
	fi
	@if [ -f composer.lock.bak ]; then \
		echo "Restoring composer.lock from backup..."; \
		mv composer.lock.bak composer.lock; \
	fi
	@composer install --no-interaction
