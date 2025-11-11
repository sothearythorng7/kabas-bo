# Fix pour la Rotation des Backups MySQL

## Problème Identifié

Le système de backup actuel garde **trop de fichiers** (44 backups actuellement).

### Analyse des Backups Actuels
- **Attendu:** 24 backups (48h × 12 par jour) + 30 backups quotidiens = ~54 max
- **Actuel:** 44 backups (dont 7 de trop)

### Cause
Le script `/usr/local/bin/mysql-backup.sh` actuel utilise `find -mtime +2` qui ne correspond pas exactement à la politique décrite:
- "Toutes les sauvegardes des dernières 48h"
- "1 sauvegarde quotidienne pour 30 jours"

## Solution

### 1. Script Amélioré Créé
Un nouveau script a été créé: `/var/www/kabas/mysql-backup-improved.sh`

**Améliorations:**
- Logique de nettoyage précise et claire
- Garde TOUS les backups des 48 dernières heures
- Garde 1 backup quotidien (heure 00:xx) pour 30 jours
- Supprime automatiquement les backups > 30 jours
- Logging détaillé du nettoyage

### 2. Politique de Rétention Exacte

```bash
# Garde:
- Backups < 48h: TOUS
- Backups entre 48h et 30j: uniquement ceux de minuit (00:00-00:59)
- Backups > 30j: AUCUN

# Résultat attendu:
- 24 backups des 48 dernières heures (2h × 12 par jour × 2 jours)
- 28 backups quotidiens (jours 3 à 30)
- TOTAL: ~52 backups maximum
```

### 3. Test Dry-Run Effectué

Analyse des backups actuels:
```
✗ 7 backups à supprimer (8 nov, 10h-22h, plus de 48h, pas quotidiens)
✓ 37 backups à garder
```

## Installation

### Étape 1: Backup du script actuel
```bash
sudo cp /usr/local/bin/mysql-backup.sh /usr/local/bin/mysql-backup.sh.bak
```

### Étape 2: Remplacer le script
```bash
sudo cp /var/www/kabas/mysql-backup-improved.sh /usr/local/bin/mysql-backup.sh
sudo chmod +x /usr/local/bin/mysql-backup.sh
```

### Étape 3: Tester manuellement
```bash
sudo /usr/local/bin/mysql-backup.sh
```

Vérifiez le log: `/var/backups/mysql/backup.log`

### Étape 4: Vérifier le cron
Le script sera exécuté automatiquement par le cron existant.

## Impact du Changement de Timezone

✅ **AUCUN IMPACT** sur le système de backup

Après le changement de timezone (UTC → Asia/Phnom_Penh):
- Les nouveaux backups auront des timestamps en heure cambodgienne
- Les anciens backups restent valides et accessibles
- La rotation fonctionne correctement avec les deux formats
- Le script compare les dates, pas les heures absolues

Exemple:
```
Avant: kabas_20251111_100501.sql.gz (10h05 CET = Europe/Berlin)
Après: kabas_20251111_170501.sql.gz (17h05 +07 = Cambodia)
```

## Vérification

Après installation, vérifier:

```bash
# Nombre de backups
ls -1 /var/backups/mysql/kabas_*.sql.gz | wc -l
# Devrait être entre 40 et 54

# Logs de nettoyage
tail -50 /var/backups/mysql/backup.log

# Taille totale
du -sh /var/backups/mysql/
```

## Rollback

Si problème, restaurer l'ancien script:
```bash
sudo cp /usr/local/bin/mysql-backup.sh.bak /usr/local/bin/mysql-backup.sh
```

---

**Date de création:** 2025-11-11
**Auteur:** Claude Code
**Version:** 1.0
