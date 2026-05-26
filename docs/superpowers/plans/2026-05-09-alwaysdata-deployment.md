# Déploiement Alwaysdata — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Configurer le projet pour un déploiement sur Alwaysdata (plan gratuit) via git pull SSH, avec assets compilés versionnés dans git.

**Architecture:** Hébergement PHP traditionnel Apache sur Alwaysdata, pas de Docker. Les assets Webpack sont compilés localement et commités dans git (`public/build/`). Un script `deploy.sh` enchaîne les commandes SSH pour redéployer en une commande.

**Tech Stack:** PHP 8.3, Symfony 7.4, Apache (.htaccess), Bash (deploy script), MySQL 8 (Alwaysdata)

---

## Fichiers concernés

| Fichier | Action |
|---|---|
| `.gitignore` | Modifier — retirer `/public/build/` |
| `public/.htaccess` | Créer — réécriture Apache pour Symfony |
| `.env.prod.example` | Créer — template variables d'env prod |
| `deploy.sh` | Créer — script SSH de déploiement |
| `README.md` | Modifier — ajouter chapitre Déploiement |

---

### Task 1 : Créer la branche de travail

**Files:**
- Aucun fichier modifié

- [ ] **Step 1 : Créer et basculer sur la branche**

```bash
git checkout -b feature/alwaysdata-deployment
```

Expected : `Switched to a new branch 'feature/alwaysdata-deployment'`

---

### Task 2 : Versionner les assets compilés

**Files:**
- Modify: `.gitignore`
- Track: `public/build/` (répertoire existant, actuellement ignoré)

- [ ] **Step 1 : Retirer `public/build/` du `.gitignore`**

Dans `.gitignore`, supprimer la ligne :
```
/public/build/
```

La section `symfony/webpack-encore-bundle` doit passer de :
```
###> symfony/webpack-encore-bundle ###
/node_modules/
/public/build/
npm-debug.log
yarn-error.log
###< symfony/webpack-encore-bundle ###
```
à :
```
###> symfony/webpack-encore-bundle ###
/node_modules/
npm-debug.log
yarn-error.log
###< symfony/webpack-encore-bundle ###
```

- [ ] **Step 2 : Compiler les assets**

```bash
docker compose exec php npm run build
```

Expected : webpack build réussi, fichiers dans `public/build/`.

- [ ] **Step 3 : Vérifier que git voit les assets**

```bash
git status public/build/
```

Expected : les fichiers apparaissent en `Untracked files`.

- [ ] **Step 4 : Commiter**

```bash
git add .gitignore public/build/
git commit -m "build: versionner assets compilés pour déploiement Alwaysdata"
```

---

### Task 3 : Ajouter le `.htaccess` Apache

**Files:**
- Create: `public/.htaccess`

Alwaysdata plan gratuit utilise Apache. Symfony nécessite une réécriture vers `index.php`.

- [ ] **Step 1 : Installer symfony/apache-pack**

```bash
docker compose exec php composer require symfony/apache-pack --no-interaction
```

Expected : Flex installe la recipe, `public/.htaccess` est créé automatiquement.

- [ ] **Step 2 : Vérifier que le fichier existe**

```bash
cat public/.htaccess
```

Expected : fichier contenant `RewriteEngine On` et `RewriteRule`.

- [ ] **Step 3 : Commiter**

```bash
git add public/.htaccess composer.json composer.lock symfony.lock
git commit -m "feat: ajouter .htaccess Apache pour Alwaysdata"
```

---

### Task 4 : Créer le template de variables d'environnement

**Files:**
- Create: `.env.prod.example`

- [ ] **Step 1 : Créer le fichier**

Créer `.env.prod.example` avec ce contenu exact :

```dotenv
APP_ENV=prod
APP_SECRET=REMPLACER_PAR_32_CARACTERES_ALEATOIRES
DATABASE_URL="mysql://LOGIN_ALWAYSDATA:MOT_DE_PASSE@mysql-LOGIN_ALWAYSDATA.alwaysdata.net:3306/LOGIN_ALWAYSDATA_astc"
MAILER_DSN=null://null
```

> Les valeurs en MAJUSCULES sont des placeholders à remplacer.
> Sur Alwaysdata le host MySQL suit le pattern `mysql-<login>.alwaysdata.net`
> et le nom de BDD `<login>_<nom>`.

Pour générer un APP_SECRET : `openssl rand -hex 16`

- [ ] **Step 2 : Vérifier que `.env.prod.example` n'est pas dans `.gitignore`**

```bash
git check-ignore -v .env.prod.example
```

Expected : aucune sortie (fichier non ignoré). S'il est ignoré, ajouter une exception dans `.gitignore` :
```
!.env.prod.example
```

- [ ] **Step 3 : Commiter**

```bash
git add .env.prod.example
git commit -m "feat: ajouter template de variables d'environnement prod"
```

---

### Task 5 : Créer le script de déploiement

**Files:**
- Create: `deploy.sh`

- [ ] **Step 1 : Créer le script**

Créer `deploy.sh` à la racine du projet :

```bash
#!/bin/bash
set -e

# ── Configuration ──────────────────────────────────────────────────────────
SSH_USER="VOTRE_LOGIN_ALWAYSDATA"
SSH_HOST="ssh-${SSH_USER}.alwaysdata.net"
APP_PATH="/home/${SSH_USER}/www/astc-revigny"
# ───────────────────────────────────────────────────────────────────────────

echo "→ Déploiement sur ${SSH_HOST}..."

ssh "${SSH_USER}@${SSH_HOST}" bash << ENDSSH
  set -e
  cd ${APP_PATH}
  echo "  git pull..."
  git pull origin main
  echo "  composer install..."
  composer install --no-dev --optimize-autoloader --no-interaction
  echo "  migrations..."
  php bin/console doctrine:migrations:migrate --no-interaction --env=prod
  echo "  cache..."
  php bin/console cache:clear --env=prod
  php bin/console cache:warmup --env=prod
ENDSSH

echo "✓ Déploiement terminé."
```

- [ ] **Step 2 : Rendre le script exécutable**

```bash
chmod +x deploy.sh
```

- [ ] **Step 3 : Commiter**

```bash
git add deploy.sh
git commit -m "feat: ajouter script de déploiement SSH Alwaysdata"
```

---

### Task 6 : Mettre à jour le README

**Files:**
- Modify: `README.md`

Ajouter un chapitre `## Déploiement` après le chapitre existant `## Services Docker`.

- [ ] **Step 1 : Ajouter le chapitre dans `README.md`**

Ajouter à la fin du fichier :

````markdown
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
nano .env.local  # remplacer APP_SECRET et DATABASE_URL avec les vraies valeurs

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
npm run build
git add public/build/
git commit -m "build: assets"
git push origin main

# 2. En local — déployer sur le serveur (git pull + composer + migrations + cache)
./deploy.sh
```

> **Note :** Si seul le code PHP a changé (pas d'assets), on peut passer directement à `./deploy.sh` sans l'étape npm.

> **Note :** Avant le premier `./deploy.sh`, éditer la variable `SSH_USER` dans le script avec votre login Alwaysdata.
````

- [ ] **Step 2 : Vérifier le rendu**

```bash
cat README.md | grep -A 5 "## Déploiement"
```

Expected : le titre et les premières lignes du chapitre s'affichent.

- [ ] **Step 3 : Commiter**

```bash
git add README.md
git commit -m "docs: ajouter chapitre déploiement Alwaysdata dans le README"
```

---

### Task 7 : Ouvrir la Pull Request

- [ ] **Step 1 : Pousser la branche**

```bash
git push -u origin feature/alwaysdata-deployment
```

- [ ] **Step 2 : Créer la PR**

```bash
gh pr create \
  --title "feat: configuration déploiement Alwaysdata" \
  --body "$(cat <<'EOF'
## Résumé

- Retire `public/build/` du `.gitignore` pour versionner les assets compilés
- Ajoute `public/.htaccess` (réécriture Apache via symfony/apache-pack)
- Ajoute `.env.prod.example` — template des variables d'env prod
- Ajoute `deploy.sh` — script SSH pour redéployer en une commande
- Met à jour le README avec les étapes de premier déploiement et de redéploiement

## Checklist avant de merger

- [ ] Remplacer `VOTRE_LOGIN_ALWAYSDATA` dans `deploy.sh` avec le vrai login
- [ ] Créer `.env.local` sur le serveur à partir de `.env.prod.example`
- [ ] Configurer le webroot dans le panel Alwaysdata → `/public`
EOF
)"
```
