#!/bin/bash

# Script de déploiement de fichiers spécifiques TESTING → PRODUCTION
# Usage: sudo bash deploy-specific-files.sh [fichier1] [fichier2] ...

set -e

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${RED}⚠️  DÉPLOIEMENT EN PRODUCTION ⚠️${NC}"
echo ""

if [ "$#" -eq 0 ]; then
    echo "Usage: sudo bash deploy-specific-files.sh [fichier1] [fichier2] ..."
    echo ""
    echo "Exemples:"
    echo "  sudo bash deploy-specific-files.sh app/Http/Controllers/InventoryController.php"
    echo "  sudo bash deploy-specific-files.sh resources/views/inventory/index.blade.php"
    echo "  sudo bash deploy-specific-files.sh resources/lang/fr/messages.php resources/lang/en/messages.php"
    echo ""
    exit 1
fi

# Chemins
TESTING_BASE="/var/www/kabas-testing"
PROD_BASE="/var/www/kabas"
BACKUP_DIR="/var/www/backups/$(date +%Y%m%d_%H%M%S)"

echo "Fichiers à déployer:"
for file in "$@"; do
    echo "  - $file"
done
echo ""

read -p "Confirmer le déploiement de ces fichiers en PRODUCTION ? (yes/no): " confirm

if [ "$confirm" != "yes" ]; then
    echo -e "${YELLOW}Déploiement annulé.${NC}"
    exit 0
fi

# Créer le répertoire de backup
mkdir -p "$BACKUP_DIR"
echo -e "${GREEN}Backup créé dans: $BACKUP_DIR${NC}"

# Déployer chaque fichier
for file in "$@"; do
    echo ""
    echo -e "${YELLOW}Traitement de: $file${NC}"

    TESTING_FILE="$TESTING_BASE/$file"
    PROD_FILE="$PROD_BASE/$file"

    # Vérifier que le fichier existe sur TESTING
    if [ ! -f "$TESTING_FILE" ]; then
        echo -e "${RED}✗ Fichier introuvable sur TESTING: $TESTING_FILE${NC}"
        continue
    fi

    # Créer le répertoire de destination si nécessaire
    PROD_DIR=$(dirname "$PROD_FILE")
    mkdir -p "$PROD_DIR"

    # Backup du fichier de production (si existe)
    if [ -f "$PROD_FILE" ]; then
        BACKUP_FILE="$BACKUP_DIR/$file"
        mkdir -p "$(dirname "$BACKUP_FILE")"
        cp "$PROD_FILE" "$BACKUP_FILE"
        echo -e "${GREEN}  ✓ Backup créé${NC}"
    fi

    # Copier le fichier
    cp "$TESTING_FILE" "$PROD_FILE"

    # Ajuster les permissions
    chown siwei:www-data "$PROD_FILE"

    echo -e "${GREEN}  ✓ Déployé${NC}"
done

# Vider les caches Laravel
echo ""
echo -e "${YELLOW}Vidage des caches Laravel...${NC}"

cd "$PROD_BASE"
php artisan config:clear 2>&1 | grep -v "Warning" || true
php artisan cache:clear 2>&1 | grep -v "Warning" || true
php artisan view:clear 2>&1 | grep -v "Warning" || true
php artisan route:clear 2>&1 | grep -v "Warning" || true

echo -e "${GREEN}✓ Caches vidés${NC}"

# Résumé
echo ""
echo -e "${GREEN}=================================================="
echo "  ✓ DÉPLOIEMENT TERMINÉ AVEC SUCCÈS !"
echo "==================================================${NC}"
echo ""
echo "Fichiers déployés: $#"
echo "Backup disponible dans: $BACKUP_DIR"
echo ""
echo -e "${YELLOW}N'oubliez pas de:${NC}"
echo "  1. Tester sur PROD que tout fonctionne"
echo "  2. Vérifier les logs en cas d'erreur"
echo "  3. Informer l'utilisateur des changements"
echo ""

# Option de rollback
echo -e "${YELLOW}Pour annuler ce déploiement (rollback):${NC}"
echo "  cd $BACKUP_DIR && for f in *; do sudo cp \"\$f\" \"$PROD_BASE/\$f\"; done"
echo "  cd $PROD_BASE && php artisan config:clear && php artisan cache:clear"
echo ""
