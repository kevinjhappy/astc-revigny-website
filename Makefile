.PHONY: up down sh console test install

up:      ; docker compose up -d
down:    ; docker compose down
sh:      ; docker compose exec php sh
console: ; docker compose exec php php bin/console $(CMD)
test:    ; docker compose exec php php bin/phpunit --testdox
install: ; docker compose exec php composer install
