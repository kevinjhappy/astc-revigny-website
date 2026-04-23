# ASTC Revigny — Site Web

Site web de l'Association Sportive et de Tennis Club de Révigny.

## Prérequis

- Docker + Docker Compose

## Démarrage rapide

```bash
# 1. Démarrer les conteneurs
make up

# 2. Installer les dépendances PHP et construire les assets
docker compose exec php composer install
docker compose exec php npm install
docker compose exec php npm run build

# 3. Créer la base de données et appliquer les migrations
make console CMD="doctrine:database:create --if-not-exists"
make console CMD="doctrine:migrations:migrate --no-interaction"

# 4. Créer un compte administrateur
make console CMD="app:create-admin admin@astc.local secret"
```

L'application est disponible sur **http://localhost:8080**.  
L'interface d'administration est sur **http://localhost:8080/admin**.

## Commandes utiles

```bash
make up                            # Démarrer les conteneurs
make down                          # Arrêter les conteneurs
make sh                            # Shell dans le conteneur PHP
make console CMD="cache:clear"    # Console Symfony
make test                          # Lancer les tests (31 tests)
```

## Tests

```bash
make test
# ou directement :
docker compose exec php php bin/phpunit --testdox
```

Le premier lancement crée automatiquement la base `astc_test` et applique les migrations.

## Architecture

```
src/
  Shared/          # Value Objects (Uuid, Email, PhoneNumber), types Doctrine
  Member/          # Gestion des membres (admin)
  Tournament/      # Gestion des tournois (admin)
  Registration/    # Inscriptions — API publique + vue admin
  Security/        # Auth admin (form_login), création compte, dashboard
  Public/          # Contrôleur page d'accueil
```

Chaque contexte suit le pattern DDD : `Domain/` → `Application/` (CQRS) → `Infrastructure/` (Doctrine, Http).

## Services Docker

| Service  | Port local |
|----------|-----------|
| nginx    | 8080      |
| mysql    | 3306      |
| mailpit  | 8026 (UI) |
