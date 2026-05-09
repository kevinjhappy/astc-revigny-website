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

## Déploiement (Alwaysdata)

Le site est hébergé sur [Alwaysdata](https://www.alwaysdata.com) (plan gratuit, Apache, PHP 8.3, MySQL 8).

### Prérequis — Panel Alwaysdata (une seule fois)

1. Créer une base de données MySQL dans **Bases de données → MySQL**
2. Vérifier que PHP 8.3 est actif dans **Sites → configuration PHP**
3. Définir le **Document Root** du site vers `/home/LOGIN/www/astc-revigny/public`
4. Ajouter votre clé SSH publique dans **Accès SSH**

### Premier déploiement (une seule fois)

```bash
# Se connecter en SSH
ssh LOGIN@ssh-LOGIN.alwaysdata.net

# Cloner le dépôt
git clone git@github.com:kevinjhappy/astc-revigny-website.git ~/www/astc-revigny
cd ~/www/astc-revigny

# Créer et remplir le fichier d'environnement prod
cp .env.prod.example .env.local
nano .env.local  # remplacer APP_SECRET, DATABASE_URL et DEFAULT_URI avec les vraies valeurs

# Installer les dépendances PHP
composer install --no-dev --optimize-autoloader --no-interaction

# Appliquer les migrations
php bin/console doctrine:migrations:migrate --no-interaction --env=prod

# Créer le compte administrateur
php bin/console app:create-admin admin@astc-revigny.fr motdepasse_solide
```

### Redéploiement (à chaque mise à jour)

```bash
# 1. En local — si des fichiers JS/CSS ont changé : rebuilder et commiter les assets
docker compose exec php npm run build
git add public/build/
git commit -m "build: assets"
git push origin main

# 2. En local — éditer SSH_USER dans deploy.sh si c'est la première fois
# 3. Déployer sur le serveur (git fetch/reset + composer + migrations + cache)
./deploy.sh
```

> **Note :** Si seul le code PHP a changé (pas d'assets), on peut passer directement à `./deploy.sh` sans l'étape npm.
