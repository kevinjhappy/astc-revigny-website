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
