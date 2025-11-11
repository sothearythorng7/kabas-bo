#!/bin/bash

#############################################
# Script de sauvegarde MySQL pour Kabas
# Sauvegarde SANS interruption de service
# Utilise --single-transaction pour InnoDB
#############################################

# Configuration
DB_NAME="kabas"
DB_USER="kabas"
DB_PASS="fPz978B8DaLs"
BACKUP_DIR="/var/backups/mysql"
DATE=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="${BACKUP_DIR}/kabas_${DATE}.sql"
LOG_FILE="${BACKUP_DIR}/backup.log"

# Créer le répertoire de sauvegarde s'il n'existe pas
mkdir -p "$BACKUP_DIR"

# Fonction de logging
log_message() {
    echo "[$(date +"%Y-%m-%d %H:%M:%S")] $1" | tee -a "$LOG_FILE"
}

log_message "=== Début de la sauvegarde MySQL ==="

# Effectuer la sauvegarde avec --single-transaction (PAS de lock des tables)
# Cette option permet de faire une sauvegarde cohérente SANS bloquer la base
mysqldump \
    --user="$DB_USER" \
    --password="$DB_PASS" \
    --single-transaction \
    --quick \
    --lock-tables=false \
    --routines \
    --triggers \
    --events \
    "$DB_NAME" > "$BACKUP_FILE" 2>&1

# Vérifier si la sauvegarde a réussi
if [ $? -eq 0 ]; then
    # Compresser la sauvegarde pour économiser de l'espace
    gzip "$BACKUP_FILE"
    BACKUP_FILE="${BACKUP_FILE}.gz"

    # Calculer la taille du fichier
    SIZE=$(du -h "$BACKUP_FILE" | cut -f1)

    log_message "✓ Sauvegarde réussie: $(basename $BACKUP_FILE) (Taille: $SIZE)"

    # Nettoyer les anciennes sauvegardes
    # POLITIQUE DE RÉTENTION:
    # - Toutes les sauvegardes des dernières 48h (sauvegardes toutes les 2h)
    # - 1 sauvegarde quotidienne (première de la journée) pour les 30 derniers jours

    log_message "Nettoyage des anciennes sauvegardes..."

    # Date limite pour les backups de 48h (2 jours)
    CUTOFF_48H=$(date -d '2 days ago' +%Y%m%d)

    # Date limite pour les backups quotidiens (30 jours)
    CUTOFF_30D=$(date -d '30 days ago' +%Y%m%d)

    # Compter avant nettoyage
    BEFORE_COUNT=$(find "$BACKUP_DIR" -name "kabas_*.sql.gz" -type f | wc -l)

    # Supprimer les backups qui:
    # - ont plus de 48h ET ne sont PAS des backups de minuit (00:00-00:59)
    # - OU ont plus de 30 jours (même ceux de minuit)

    find "$BACKUP_DIR" -name "kabas_*.sql.gz" -type f | while read backup; do
        filename=$(basename "$backup")

        # Extraire la date (YYYYMMDD) et l'heure (HHMMSS)
        if [[ $filename =~ kabas_([0-9]{8})_([0-9]{6})\.sql\.gz ]]; then
            backup_date="${BASH_REMATCH[1]}"
            backup_time="${BASH_REMATCH[2]}"
            backup_hour="${backup_time:0:2}"

            # Supprimer si plus de 30 jours
            if [ "$backup_date" -lt "$CUTOFF_30D" ]; then
                rm -f "$backup"
                log_message "Supprimé (>30j): $filename"
            # Supprimer si plus de 48h ET pas entre 00:00 et 00:59
            elif [ "$backup_date" -lt "$CUTOFF_48H" ] && [ "$backup_hour" != "00" ]; then
                rm -f "$backup"
                log_message "Supprimé (>48h, non-quotidien): $filename"
            fi
        fi
    done

    # Compter après nettoyage
    AFTER_COUNT=$(find "$BACKUP_DIR" -name "kabas_*.sql.gz" -type f | wc -l)
    DELETED=$((BEFORE_COUNT - AFTER_COUNT))

    log_message "✓ Nettoyage terminé: $DELETED backup(s) supprimé(s), $AFTER_COUNT restant(s)"
else
    log_message "✗ ERREUR: La sauvegarde a échoué!"
    exit 1
fi

log_message "=== Fin de la sauvegarde MySQL ==="
echo "" >> "$LOG_FILE"

exit 0
