# Guide de l'Environnement de Testing Kabas

## 📋 Table des matières

1. [Vue d'ensemble](#vue-densemble)
2. [Architecture](#architecture)
3. [Installation initiale](#installation-initiale)
4. [Procédure de développement](#procédure-de-développement)
5. [Déploiement en production](#déploiement-en-production)
6. [Maintenance](#maintenance)
7. [Dépannage](#dépannage)

---

## 🎯 Vue d'ensemble

Ce document décrit la configuration et l'utilisation de l'environnement de testing pour Kabas. L'environnement de testing permet de :

- ✅ Tester les modifications sans affecter la production
- ✅ Valider les changements avant déploiement
- ✅ Protéger la base de données de production
- ✅ Permettre au staff de travailler sans interruption
- ✅ Faciliter les rollbacks en cas de problème

---

## 🏗️ Architecture

### Applications de Production

| Application | URL | Répertoire | Base de données |
|------------|-----|------------|-----------------|
| Back-Office | https://bo.kabasconceptstore.com | `/var/www/kabas` | `kabas_prod` |
| POS | https://pos.kabasconceptstore.com | `/var/www/kabas` | `kabas_prod` |
| Site Public | https://kabasconceptstore.com | `/var/www/kabas-site` | `kabas_prod` (lecture seule) |

### Applications de Testing

| Application | URL | Répertoire | Base de données |
|------------|-----|------------|-----------------|
| Back-Office TEST | https://testing-bo.kabasconceptstore.com | `/var/www/kabas-testing` | `kabas_testing` |
| POS TEST | https://testing-pos.kabasconceptstore.com | `/var/www/kabas-testing` | `kabas_testing` |
| Site Public TEST | https://testing.kabasconceptstore.com | `/var/www/kabas-site-testing` | `kabas_testing` (lecture seule) |

### Identification visuelle

- **Production** : Pas de bandeau
- **Testing** : Bandeau rouge en haut de chaque page avec "⚠️ ENVIRONNEMENT DE TESTING ⚠️"

---

## 🚀 Installation initiale

### Prérequis

- Accès SSH au serveur
- Droits sudo
- Git configuré
- Composer et NPM installés
- MySQL/MariaDB configuré

### Étape 1 : Exécuter le script d'installation

```bash
cd /var/www/kabas
sudo bash setup-testing-environment.sh
```

Le script va :
1. Créer les répertoires `/var/www/kabas-testing` et `/var/www/kabas-site-testing`
2. Copier les applications (sans node_modules, vendor, .git)
3. Ajuster les permissions
4. Installer les dépendances (Composer + NPM)
5. Créer les fichiers `.env` pour testing
6. Créer les vhosts Apache
7. Créer la base de données `kabas_testing`
8. Copier les données de production vers testing
9. Générer les clés d'application

### Étape 2 : Ajouter les bandeaux TESTING

```bash
cd /var/www/kabas
sudo bash add-testing-banner.sh
```

### Étape 3 : Configuration DNS

Ajouter les enregistrements DNS suivants :

```
testing-bo.kabasconceptstore.com  → [IP du serveur]
testing-pos.kabasconceptstore.com → [IP du serveur]
testing.kabasconceptstore.com     → [IP du serveur]
```

### Étape 4 : Certificats SSL (optionnel mais recommandé)

```bash
# Installer Certbot si nécessaire
sudo apt install certbot python3-certbot-apache

# Obtenir les certificats
sudo certbot --apache -d testing-bo.kabasconceptstore.com
sudo certbot --apache -d testing-pos.kabasconceptstore.com
sudo certbot --apache -d testing.kabasconceptstore.com
```

---

## 💻 Procédure de développement

### ⚠️ RÈGLE D'OR

**TOUTES les modifications doivent d'abord être testées sur l'environnement de TESTING avant d'aller en production.**

### Workflow de développement

```
┌─────────────────────────────────────────────────┐
│ 1. DÉVELOPPEMENT SUR TESTING                    │
├─────────────────────────────────────────────────┤
│   • Coder sur /var/www/kabas-testing           │
│   • ou /var/www/kabas-site-testing             │
│   • Tester sur testing-*.kabasconceptstore.com │
└─────────────────────────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────┐
│ 2. VALIDATION PAR L'UTILISATEUR                 │
├─────────────────────────────────────────────────┤
│   • L'utilisateur teste sur TESTING            │
│   • Valide que tout fonctionne correctement    │
└─────────────────────────────────────────────────┘
                       ↓
┌─────────────────────────────────────────────────┐
│ 3. DÉPLOIEMENT EN PRODUCTION                    │
├─────────────────────────────────────────────────┤
│   • Copier les fichiers modifiés vers PROD     │
│   • Exécuter les migrations si nécessaire      │
│   • Vérifier que tout fonctionne               │
└─────────────────────────────────────────────────┘
```

### Exemple concret : Modification du système d'inventaire

#### 1. Développement sur TESTING

```bash
# Se connecter en SSH
cd /var/www/kabas-testing

# Modifier les fichiers nécessaires
vim app/Http/Controllers/InventoryController.php
vim resources/views/inventory/index.blade.php
vim resources/lang/fr/messages.php

# Tester sur https://testing-bo.kabasconceptstore.com/inventory
```

#### 2. Validation

- Demander à l'utilisateur de tester sur testing-bo.kabasconceptstore.com
- Vérifier que les modifications fonctionnent comme prévu
- Corriger les bugs éventuels

#### 3. Déploiement (voir section suivante)

---

## 🚢 Déploiement en production

### Méthode 1 : Copie manuelle des fichiers modifiés (RECOMMANDÉ pour débuter)

```bash
# 1. Identifier les fichiers modifiés
cd /var/www/kabas-testing
git status  # ou comparer manuellement

# 2. Copier UNIQUEMENT les fichiers modifiés
# Exemple pour InventoryController
sudo cp /var/www/kabas-testing/app/Http/Controllers/InventoryController.php \
        /var/www/kabas/app/Http/Controllers/InventoryController.php

# Exemple pour les vues
sudo cp /var/www/kabas-testing/resources/views/inventory/index.blade.php \
        /var/www/kabas/resources/views/inventory/index.blade.php

# Exemple pour les traductions
sudo cp /var/www/kabas-testing/resources/lang/fr/messages.php \
        /var/www/kabas/resources/lang/fr/messages.php

# 3. Ajuster les permissions
sudo chown siwei:www-data /var/www/kabas/app/Http/Controllers/InventoryController.php
sudo chown siwei:www-data /var/www/kabas/resources/views/inventory/index.blade.php
sudo chown siwei:www-data /var/www/kabas/resources/lang/fr/messages.php

# 4. Vider le cache Laravel
cd /var/www/kabas
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Méthode 2 : Script de déploiement automatique

Créer un fichier `deploy-to-production.sh` :

```bash
#!/bin/bash
# Script de déploiement TESTING → PRODUCTION

set -e

echo "⚠️  DÉPLOIEMENT EN PRODUCTION ⚠️"
echo ""
read -p "Êtes-vous sûr de vouloir déployer en production ? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo "Déploiement annulé."
    exit 1
fi

echo ""
echo "Fichiers à déployer:"
echo "1. app/Http/Controllers/InventoryController.php"
echo "2. resources/views/inventory/index.blade.php"
echo "3. resources/views/inventory/confirm.blade.php"
echo "4. resources/lang/fr/messages.php"
echo "5. resources/lang/en/messages.php"
echo ""

read -p "Confirmer le déploiement de ces fichiers ? (yes/no): " confirm2

if [ "$confirm2" != "yes" ]; then
    echo "Déploiement annulé."
    exit 1
fi

# Backup avant déploiement
BACKUP_DIR="/var/www/backups/$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

echo "Création du backup dans $BACKUP_DIR..."
cp /var/www/kabas/app/Http/Controllers/InventoryController.php "$BACKUP_DIR/" 2>/dev/null || true
cp /var/www/kabas/resources/views/inventory/index.blade.php "$BACKUP_DIR/" 2>/dev/null || true
cp /var/www/kabas/resources/views/inventory/confirm.blade.php "$BACKUP_DIR/" 2>/dev/null || true

# Déploiement
echo "Déploiement en cours..."

rsync -av /var/www/kabas-testing/app/Http/Controllers/InventoryController.php \
          /var/www/kabas/app/Http/Controllers/

rsync -av /var/www/kabas-testing/resources/views/inventory/ \
          /var/www/kabas/resources/views/inventory/

rsync -av /var/www/kabas-testing/resources/lang/ \
          /var/www/kabas/resources/lang/

# Permissions
chown -R siwei:www-data /var/www/kabas/app/Http/Controllers/
chown -R siwei:www-data /var/www/kabas/resources/views/inventory/
chown -R siwei:www-data /var/www/kabas/resources/lang/

# Clear cache
cd /var/www/kabas
php artisan config:clear
php artisan cache:clear
php artisan view:clear

echo ""
echo "✓ Déploiement terminé avec succès !"
echo ""
echo "Backup disponible dans: $BACKUP_DIR"
```

### Migrations de base de données

Si des migrations sont nécessaires :

```bash
# 1. Tester la migration sur TESTING
cd /var/www/kabas-testing
php artisan migrate

# 2. Vérifier que tout fonctionne

# 3. Appliquer sur PRODUCTION (⚠️ ATTENTION !)
cd /var/www/kabas
# IMPORTANT: Faire un backup de la BDD avant !
mysqldump -u[user] -p[pass] kabas_prod > /var/www/backups/kabas_prod_$(date +%Y%m%d_%H%M%S).sql

# Appliquer la migration
php artisan migrate
```

---

## 🔧 Maintenance

### Synchroniser les données PROD → TESTING

Il est recommandé de synchroniser périodiquement les données :

```bash
#!/bin/bash
# sync-prod-to-testing.sh

# Backup de la BDD testing actuelle
mysqldump -u[user] -p[pass] kabas_testing > /var/www/backups/kabas_testing_backup_$(date +%Y%m%d).sql

# Copier les données de PROD vers TESTING
mysqldump -u[user_prod] -p[pass_prod] kabas_prod | mysql -u[user_test] -p[pass_test] kabas_testing

echo "✓ Données synchronisées PROD → TESTING"
```

### Mettre à jour les dépendances

```bash
cd /var/www/kabas-testing
composer update
npm update

# Tester que tout fonctionne

# Si OK, appliquer en production
cd /var/www/kabas
composer update
npm update
```

---

## 🔍 Dépannage

### Le bandeau TESTING n'apparaît pas

Vérifier que dans `/var/www/kabas-testing/.env` :
```
APP_ENV=testing
```

Vider le cache :
```bash
cd /var/www/kabas-testing
php artisan config:clear
php artisan view:clear
```

### Erreur "Base de données introuvable"

Vérifier les credentials dans `.env` :
```bash
cat /var/www/kabas-testing/.env | grep DB_
```

### Permissions incorrectes

```bash
sudo chown -R siwei:www-data /var/www/kabas-testing
sudo chmod -R 775 /var/www/kabas-testing/storage
sudo chmod -R 775 /var/www/kabas-testing/bootstrap/cache
```

### Apache ne démarre pas

Vérifier les logs :
```bash
sudo tail -f /var/log/apache2/error.log
sudo apache2ctl configtest
```

---

## 📝 Checklist avant déploiement en production

- [ ] Les modifications ont été testées sur TESTING
- [ ] L'utilisateur a validé les modifications
- [ ] Un backup de la BDD production a été créé
- [ ] Un backup des fichiers à modifier a été créé
- [ ] Les fichiers ont été copiés vers PROD
- [ ] Les permissions sont correctes
- [ ] Le cache Laravel a été vidé
- [ ] Les migrations ont été appliquées (si nécessaire)
- [ ] Les modifications ont été testées sur PROD
- [ ] Le staff a été informé des changements

---

## 📞 Contact et support

En cas de problème, contacter l'administrateur système ou consulter :

- Documentation Laravel : https://laravel.com/docs
- Logs Apache : `/var/log/apache2/`
- Logs Laravel : `/var/www/kabas/storage/logs/`

---

**Dernière mise à jour** : 2025-11-22
**Version** : 1.0.0
