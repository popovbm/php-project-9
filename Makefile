PORT ?= 8000
start:
	PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public
	createdb railway
	psql railway < database.sql

install:
	composer install

validate:
	composer validate

lint:
	composer exec --verbose phpcs -- --standard=PSR12 public app
	composer exec --verbose phpstan -- --level=8 --xdebug analyse app

lint-fix:
	composer exec --verbose phpcbf -- --standard=PSR12 public app

test:
	composer exec --verbose phpunit tests

test-coverage:
	composer exec --verbose phpunit tests -- --coverage-clover build/logs/clover.xml

console:
	composer exec --verbose psysh
