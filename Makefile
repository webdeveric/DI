.PHONY: instructions install test

instructions:
	@echo "\n\tRun these commands to get started:\n"
	@echo "\tmake install"
	@echo "\tmake test\n"

install:
	docker-compose run --rm composer install

test:
	docker-compose run --rm phpunit tests/
