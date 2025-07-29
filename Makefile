cs-fix:
	php vendor/bin/php-cs-fixer fix

psalm:
	php vendor/bin/psalm

psalm-fix:
	php vendor/bin/psalm --alter --issues=MissingOverrideAttribute

tests-unit:
	php vendor/bin/phpunit --testsuite=unit

tests-integration:
	php vendor/bin/phpunit --testsuite=integration

tests-all: tests-unit tests-integration
