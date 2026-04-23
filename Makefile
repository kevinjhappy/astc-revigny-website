.PHONY: up down sh console test install

up:      ; docker compose up -d
down:    ; docker compose down
sh:      ; docker compose exec php sh
console: ; docker compose exec php php bin/console $(c)
test:    ; docker compose exec php vendor/bin/phpunit
install: ; docker compose exec php composer install
