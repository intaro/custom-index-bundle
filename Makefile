PHP=docker-compose run --rm --no-deps php

vendor: composer.json
	@$(PHP) composer install -o -n --no-ansi
	@touch vendor || true

php-cs: vendor
	@$(PHP) vendor/bin/php-cs-fixer fix --using-cache=no -vv

phpstan: vendor
	@$(PHP) vendor/bin/phpstan analyse

check: php-cs phpstan
