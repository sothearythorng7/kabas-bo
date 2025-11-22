# 🚀 Configuration Rapide de l'Environnement de Testing

## ⚡ Installation rapide

### Étape 1 : Exécuter le script d'installation

```bash
cd /var/www/kabas
sudo bash setup-testing-environment.sh
```

Ce script va créer automatiquement :
- `/var/www/kabas-testing/` (BO + POS Testing)
- `/var/www/kabas-site-testing/` (Site Public Testing)
- Base de données `kabas_testing`
- Vhosts Apache
- Configuration `.env`

**Durée estimée** : 10-15 minutes

### Étape 2 : Ajouter les bandeaux TESTING

```bash
sudo bash add-testing-banner.sh
```

Ceci ajoutera un bandeau rouge "⚠️ TESTING ⚠️" en haut de toutes les pages.

**Durée** : 1 minute

### Étape 3 : Configurer le DNS

Ajouter ces entrées DNS pointant vers votre serveur :

```
testing-bo.kabasconceptstore.com
testing-pos.kabasconceptstore.com
testing.kabasconceptstore.com
```

### Étape 4 : (Optionnel) Certificats SSL

```bash
sudo certbot --apache -d testing-bo.kabasconceptstore.com
sudo certbot --apache -d testing-pos.kabasconceptstore.com
sudo certbot --apache -d testing.kabasconceptstore.com
```

---

## 📝 Fichiers créés

| Fichier | Description |
|---------|-------------|
| `setup-testing-environment.sh` | Script d'installation complète |
| `add-testing-banner.sh` | Ajoute les bandeaux TESTING |
| `deploy-specific-files.sh` | Déploie des fichiers vers PROD |
| `TESTING_ENVIRONMENT_GUIDE.md` | Documentation complète |
| `CLAUDE_REMINDER.md` | Aide-mémoire pour Claude |
| `README_TESTING_SETUP.md` | Ce fichier |

---

## 🎯 URLs de test

Après installation, vous aurez accès à :

- **Back-Office Testing** : https://testing-bo.kabasconceptstore.com
- **POS Testing** : https://testing-pos.kabasconceptstore.com
- **Site Public Testing** : https://testing.kabasconceptstore.com

---

## 💻 Workflow de développement

```
1. CODER sur TESTING
   /var/www/kabas-testing/ ou /var/www/kabas-site-testing/

2. TESTER
   https://testing-*.kabasconceptstore.com

3. VALIDER
   L'utilisateur valide les modifications

4. DÉPLOYER
   sudo bash deploy-specific-files.sh [fichier1] [fichier2]
```

---

## 🔧 Déploiement vers PRODUCTION

### Méthode 1 : Script automatique (recommandé)

```bash
cd /var/www/kabas

# Déployer des fichiers spécifiques
sudo bash deploy-specific-files.sh \
    app/Http/Controllers/InventoryController.php \
    resources/views/inventory/index.blade.php \
    resources/lang/fr/messages.php
```

### Méthode 2 : Copie manuelle

```bash
# Copier un fichier
sudo cp /var/www/kabas-testing/[chemin]/[fichier] \
        /var/www/kabas/[chemin]/[fichier]

# Ajuster les permissions
sudo chown siwei:www-data /var/www/kabas/[chemin]/[fichier]

# Vider les caches
cd /var/www/kabas
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

---

## 📚 Documentation complète

Pour plus de détails, consultez :
- **Guide complet** : [TESTING_ENVIRONMENT_GUIDE.md](TESTING_ENVIRONMENT_GUIDE.md)
- **Aide-mémoire Claude** : [CLAUDE_REMINDER.md](CLAUDE_REMINDER.md)

---

## ⚠️ RÈGLES IMPORTANTES

1. **TOUJOURS** travailler sur TESTING en premier
2. **JAMAIS** modifier directement la PRODUCTION
3. **TOUJOURS** faire valider par l'utilisateur avant déploiement
4. **TOUJOURS** créer un backup avant déploiement
5. **JAMAIS** faire de DELETE/UPDATE direct sur la BDD de production

---

## 🆘 Aide

En cas de problème :

1. Consulter la documentation complète
2. Vérifier les logs : `/var/log/apache2/` et `/var/www/kabas/storage/logs/`
3. Vérifier que APP_ENV=testing dans les .env de testing

---

**Date de création** : 2025-11-22
**Version** : 1.0.0
