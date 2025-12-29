#!/bin/bash

# Script de création de l'environnement de testing pour Kabas
# Ce script doit être exécuté avec sudo

set -e  # Arrêter en cas d'erreur

echo "=================================================="
echo "  Configuration de l'environnement de TESTING"
echo "=================================================="

# Couleurs pour les messages
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# ============================================
# 1. CRÉATION DES RÉPERTOIRES TESTING
# ============================================
echo -e "${YELLOW}[1/8] Création des répertoires testing...${NC}"

mkdir -p /var/www/kabas-testing
mkdir -p /var/www/kabas-site-testing

echo -e "${GREEN}✓ Répertoires créés${NC}"

# ============================================
# 2. COPIE DES APPLICATIONS
# ============================================
echo -e "${YELLOW}[2/8] Copie de l'application kabas (BO + POS)...${NC}"

rsync -av \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='.git' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='bootstrap/cache/*' \
    /var/www/kabas/ \
    /var/www/kabas-testing/

echo -e "${GREEN}✓ kabas copié${NC}"

echo -e "${YELLOW}[2/8] Copie de l'application kabas-site...${NC}"

rsync -av \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='.git' \
    --exclude='storage/logs/*' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='bootstrap/cache/*' \
    /var/www/kabas-site/ \
    /var/www/kabas-site-testing/

echo -e "${GREEN}✓ kabas-site copié${NC}"

# ============================================
# 3. AJUSTEMENT DES PERMISSIONS
# ============================================
echo -e "${YELLOW}[3/8] Ajustement des permissions...${NC}"

chown -R siwei:www-data /var/www/kabas-testing
chown -R siwei:www-data /var/www/kabas-site-testing

chmod -R 775 /var/www/kabas-testing/storage
chmod -R 775 /var/www/kabas-testing/bootstrap/cache
chmod -R 775 /var/www/kabas-site-testing/storage
chmod -R 775 /var/www/kabas-site-testing/bootstrap/cache

echo -e "${GREEN}✓ Permissions ajustées${NC}"

# ============================================
# 4. INSTALLATION DES DÉPENDANCES
# ============================================
echo -e "${YELLOW}[4/8] Installation des dépendances Composer et NPM...${NC}"

cd /var/www/kabas-testing
sudo -u siwei composer install --no-interaction --optimize-autoloader
sudo -u siwei npm install

cd /var/www/kabas-site-testing
sudo -u siwei composer install --no-interaction --optimize-autoloader
sudo -u siwei npm install

echo -e "${GREEN}✓ Dépendances installées${NC}"

# ============================================
# 5. CONFIGURATION .ENV POUR TESTING
# ============================================
echo -e "${YELLOW}[5/8] Configuration des fichiers .env pour testing...${NC}"

# Copier .env pour kabas-testing
cp /var/www/kabas-testing/.env /var/www/kabas-testing/.env.production.bak
cat > /var/www/kabas-testing/.env << 'ENVEOF'
APP_NAME="Kabas TESTING"
APP_ENV=testing
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=https://testing-bo.kabasconceptstore.com

APP_LOCALE=fr
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
APP_MAINTENANCE_STORE=database

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kabas_testing
DB_USERNAME=
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
CACHE_PREFIX=

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=

VITE_APP_NAME="${APP_NAME}"
ENVEOF

# Copier .env pour kabas-site-testing
cp /var/www/kabas-site-testing/.env /var/www/kabas-site-testing/.env.production.bak
cat > /var/www/kabas-site-testing/.env << 'ENVEOF2'
APP_NAME="Kabas Site TESTING"
APP_ENV=testing
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=https://testing.kabasconceptstore.com

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=sqlite

BACKOFFICE_DB_CONNECTION=mysql
BACKOFFICE_DB_HOST=127.0.0.1
BACKOFFICE_DB_PORT=3306
BACKOFFICE_DB_DATABASE=kabas_testing
BACKOFFICE_DB_USERNAME=
BACKOFFICE_DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
CACHE_PREFIX=

MAIL_MAILER=log

VITE_APP_NAME="${APP_NAME}"
ENVEOF2

echo -e "${GREEN}✓ Fichiers .env configurés${NC}"
echo -e "${YELLOW}⚠️  IMPORTANT: Vous devez compléter les informations DB_USERNAME et DB_PASSWORD dans les fichiers .env${NC}"

# ============================================
# 6. CRÉATION DES VHOSTS APACHE
# ============================================
echo -e "${YELLOW}[6/8] Création des vhosts Apache pour testing...${NC}"

# Vhost pour BO Testing
cat > /etc/apache2/sites-available/testing-bo-kabasconceptstore.conf << 'VHOSTEOF1'
<VirtualHost *:80>
    ServerName testing-bo.kabasconceptstore.com
    ServerAdmin webmaster@localhost

    DocumentRoot /var/www/kabas-testing/public

    <Directory /var/www/kabas-testing/public>
        AllowOverride All
        Options Indexes FollowSymLinks
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/testing_bo_kabasconceptstore_error.log
    CustomLog ${APACHE_LOG_DIR}/testing_bo_kabasconceptstore_access.log combined
</VirtualHost>
VHOSTEOF1

# Vhost pour POS Testing
cat > /etc/apache2/sites-available/testing-pos-kabasconceptstore.conf << 'VHOSTEOF2'
<VirtualHost *:80>
    ServerName testing-pos.kabasconceptstore.com
    ServerAdmin webmaster@localhost

    DocumentRoot /var/www/kabas-testing/public

    <Directory /var/www/kabas-testing/public>
        AllowOverride All
        Options Indexes FollowSymLinks
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/testing_pos_kabasconceptstore_error.log
    CustomLog ${APACHE_LOG_DIR}/testing_pos_kabasconceptstore_access.log combined
</VirtualHost>
VHOSTEOF2

# Vhost pour Site Public Testing
cat > /etc/apache2/sites-available/testing-kabasconceptstore.conf << 'VHOSTEOF3'
<VirtualHost *:80>
    ServerName testing.kabasconceptstore.com
    ServerAdmin webmaster@localhost

    DocumentRoot /var/www/kabas-site-testing/public

    <Directory /var/www/kabas-site-testing/public>
        AllowOverride All
        Options Indexes FollowSymLinks
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/testing_kabasconceptstore_error.log
    CustomLog ${APACHE_LOG_DIR}/testing_kabasconceptstore_access.log combined
</VirtualHost>
VHOSTEOF3

# Activer les vhosts
a2ensite testing-bo-kabasconceptstore.conf
a2ensite testing-pos-kabasconceptstore.conf
a2ensite testing-kabasconceptstore.conf

# Recharger Apache
systemctl reload apache2

echo -e "${GREEN}✓ Vhosts créés et activés${NC}"
echo -e "${YELLOW}⚠️  IMPORTANT: Vous devez ajouter ces entrées DNS:${NC}"
echo "   - testing-bo.kabasconceptstore.com"
echo "   - testing-pos.kabasconceptstore.com"
echo "   - testing.kabasconceptstore.com"

# ============================================
# 7. CRÉATION DE LA BASE DE DONNÉES TESTING
# ============================================
echo -e "${YELLOW}[7/8] Création de la base de données de testing...${NC}"
echo -e "${YELLOW}⚠️  Cette étape nécessite les credentials MySQL root${NC}"

read -p "Entrez le nom d'utilisateur MySQL root [root]: " MYSQL_ROOT_USER
MYSQL_ROOT_USER=${MYSQL_ROOT_USER:-root}

read -sp "Entrez le mot de passe MySQL root: " MYSQL_ROOT_PASS
echo ""

read -p "Entrez le nom d'utilisateur MySQL pour kabas_testing: " MYSQL_KABAS_USER
read -sp "Entrez le mot de passe MySQL pour kabas_testing: " MYSQL_KABAS_PASS
echo ""

# Créer la base de données
mysql -u"$MYSQL_ROOT_USER" -p"$MYSQL_ROOT_PASS" << MYSQLEOF
CREATE DATABASE IF NOT EXISTS kabas_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$MYSQL_KABAS_USER'@'localhost' IDENTIFIED BY '$MYSQL_KABAS_PASS';
GRANT ALL PRIVILEGES ON kabas_testing.* TO '$MYSQL_KABAS_USER'@'localhost';
FLUSH PRIVILEGES;
MYSQLEOF

echo -e "${GREEN}✓ Base de données kabas_testing créée${NC}"

# Copier les données de production vers testing
echo -e "${YELLOW}Copie des données de production vers testing...${NC}"

read -p "Entrez le nom de la base de données de production: " PROD_DB
read -p "Entrez le nom d'utilisateur MySQL de production: " PROD_USER
read -sp "Entrez le mot de passe MySQL de production: " PROD_PASS
echo ""

mysqldump -u"$PROD_USER" -p"$PROD_PASS" "$PROD_DB" | mysql -u"$MYSQL_KABAS_USER" -p"$MYSQL_KABAS_PASS" kabas_testing

echo -e "${GREEN}✓ Données copiées vers testing${NC}"

# Mettre à jour les .env avec les credentials
sed -i "s/DB_USERNAME=/DB_USERNAME=$MYSQL_KABAS_USER/" /var/www/kabas-testing/.env
sed -i "s/DB_PASSWORD=/DB_PASSWORD=$MYSQL_KABAS_PASS/" /var/www/kabas-testing/.env

sed -i "s/BACKOFFICE_DB_USERNAME=/BACKOFFICE_DB_USERNAME=$MYSQL_KABAS_USER/" /var/www/kabas-site-testing/.env
sed -i "s/BACKOFFICE_DB_PASSWORD=/BACKOFFICE_DB_PASSWORD=$MYSQL_KABAS_PASS/" /var/www/kabas-site-testing/.env

# ============================================
# 8. GÉNÉRATION DES CLÉS D'APPLICATION
# ============================================
echo -e "${YELLOW}[8/8] Génération des clés d'application...${NC}"

cd /var/www/kabas-testing
sudo -u siwei php artisan key:generate --force

cd /var/www/kabas-site-testing
sudo -u siwei php artisan key:generate --force

echo -e "${GREEN}✓ Clés générées${NC}"

# ============================================
# RÉSUMÉ FINAL
# ============================================
echo ""
echo -e "${GREEN}=================================================="
echo "  ✓ ENVIRONNEMENT DE TESTING CRÉÉ AVEC SUCCÈS !"
echo "==================================================${NC}"
echo ""
echo "URLs de testing configurées:"
echo "  - Back-Office: http://testing-bo.kabasconceptstore.com"
echo "  - POS:         http://testing-pos.kabasconceptstore.com"
echo "  - Site Public: http://testing.kabasconceptstore.com"
echo ""
echo "Base de données:"
echo "  - Nom: kabas_testing"
echo "  - User: $MYSQL_KABAS_USER"
echo ""
echo -e "${YELLOW}PROCHAINES ÉTAPES:${NC}"
echo "1. Configurer vos DNS pour pointer vers ce serveur"
echo "2. Obtenir des certificats SSL (Let's Encrypt recommandé)"
echo "3. Ajouter le bandeau TESTING (voir script add-testing-banner.sh)"
echo ""
