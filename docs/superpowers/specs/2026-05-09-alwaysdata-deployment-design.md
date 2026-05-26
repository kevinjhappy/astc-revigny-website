# Déploiement Alwaysdata — Design Spec

Date : 2026-05-09  
Contexte : Site ASTC Révigny (Symfony 7.4 / PHP 8.3 / MySQL 8)  
Approche retenue : **Git pull via SSH** (approche A)

---

## Contexte et contraintes

- Alwaysdata plan gratuit = hébergement PHP traditionnel (Apache), **pas de Docker**
- Trafic très faible, déploiements rares (quelques fois par an)
- Assets Webpack compilés localement et commités dans git (approche A)
- Pas d'automatisation CI/CD : un script shell local suffit

---

## Fichiers à créer / modifier

### 1. `.gitignore` — retirer `public/build/`

`public/build/` est actuellement ignoré. Il faut le retirer du `.gitignore` pour que les assets compilés soient versionnés et disponibles sur le serveur sans Node.js.

### 2. `public/.htaccess`

Alwaysdata utilise Apache. Symfony requiert une réécriture vers `index.php`. Généré via le package `symfony/apache-pack` (`composer require symfony/apache-pack`) ou créé manuellement.

Contenu :
```
DirectoryIndex index.php

<IfModule mod_negotiation.c>
    Options -MultiViews
</IfModule>

<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI}::$0 ^(/.+)/(.*)::\2$
    RewriteRule .* - [E=BASE:%1]
    RewriteCond %{HTTP:Authorization} .+
    RewriteRule ^ - [E=HTTP_AUTHORIZATION:%0]
    RewriteCond %{ENV:REDIRECT_STATUS} =""
    RewriteRule ^index\.php(?:/(.*)|$) %{ENV:BASE}/$1 [R=301,L]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ %{ENV:BASE}/index.php [L]
</IfModule>

<IfModule !mod_rewrite.c>
    <IfModule mod_alias.c>
        RedirectMatch 307 ^/$ /index.php/
    </IfModule>
</IfModule>
```

### 3. `.env.prod.example`

Template à copier en `.env.local` sur le serveur Alwaysdata :

```
APP_ENV=prod
APP_SECRET=GENERER_UNE_VALEUR_ALEATOIRE_32_CHARS
DATABASE_URL="mysql://USER:PASSWORD@mysql-USER.alwaysdata.net:3306/USER_astc"
MAILER_DSN=null://null
```

> Note : sur Alwaysdata, le host MySQL suit le pattern `mysql-<login>.alwaysdata.net` et le nom de BDD `<login>_<nom>`.

### 4. `deploy.sh`

Script local à exécuter depuis la machine du développeur :

```bash
#!/bin/bash
set -e

SSH_USER="VOTRE_LOGIN_ALWAYSDATA"
SSH_HOST="ssh-${SSH_USER}.alwaysdata.net"
APP_PATH="/home/${SSH_USER}/www/astc-revigny"

echo "→ Déploiement en cours..."

ssh "${SSH_USER}@${SSH_HOST}" bash << EOF
  set -e
  cd ${APP_PATH}
  git pull origin main
  composer install --no-dev --optimize-autoloader --no-interaction
  php bin/console doctrine:migrations:migrate --no-interaction --env=prod
  php bin/console cache:clear --env=prod
  php bin/console cache:warmup --env=prod
EOF

echo "✓ Déploiement terminé."
```

Rendre exécutable : `chmod +x deploy.sh`

### 5. `README.md` — nouveau chapitre Déploiement

Deux sous-sections :

- **Premier déploiement** : prérequis panel Alwaysdata, clone SSH, `.env.local`, webroot → `public/`, migrations, création admin
- **Redéploiement** : workflow complet avec build assets en local avant push

---

## Flux de déploiement

### Premier déploiement (une seule fois)

```
Panel Alwaysdata
  └─ Créer BDD MySQL
  └─ Configurer PHP 8.3
  └─ Configurer webroot → /home/USER/www/astc-revigny/public

SSH
  └─ git clone git@github.com:kevinjhappy/astc-revigny-website.git ~/www/astc-revigny
  └─ cp .env.prod.example .env.local  (puis éditer les vraies valeurs)
  └─ composer install --no-dev --optimize-autoloader
  └─ php bin/console doctrine:migrations:migrate --env=prod
  └─ php bin/console app:create-admin email@example.com motdepasse
```

### Redéploiement (à chaque mise à jour)

```
Local
  └─ npm run build              ← compiler les assets
  └─ git add public/build/
  └─ git commit -m "build assets"
  └─ git push origin main

Local
  └─ ./deploy.sh                ← git pull + composer + migrations + cache
```

---

## Décisions prises

| Question | Décision | Raison |
|---|---|---|
| Assets | Commités dans git (`public/build/`) | Pas de Node.js nécessaire sur serveur |
| Déploiement | Script SSH local (`deploy.sh`) | Trafic faible, déploiements rares |
| CI/CD | Aucun | Complexité non justifiée |
| Webserver | Apache + `.htaccess` | Alwaysdata plan gratuit = Apache |
| Variables d'env | `.env.local` sur serveur | Pattern standard Symfony prod |
