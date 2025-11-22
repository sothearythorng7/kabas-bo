# 📋 Instructions d'Installation de l'Environnement de Testing

## ✅ Ce qui a été préparé

Tous les scripts et la documentation nécessaires ont été créés dans `/var/www/kabas/` :

### Scripts
- ✅ `setup-testing-environment.sh` - Installation complète automatique
- ✅ `add-testing-banner.sh` - Ajout des bandeaux TESTING
- ✅ `deploy-specific-files.sh` - Déploiement sécurisé vers production

### Documentation
- ✅ `TESTING_ENVIRONMENT_GUIDE.md` - Guide complet (à lire en priorité)
- ✅ `CLAUDE_REMINDER.md` - Aide-mémoire pour Claude
- ✅ `README_TESTING_SETUP.md` - Démarrage rapide

### Commits Git
- ✅ Toutes les modifications en cours ont été commitées
- ✅ Les scripts de testing ont été commitées
- ✅ Prêt à être exécuté

---

## 🚀 CE QUE VOUS DEVEZ FAIRE MAINTENANT

### Étape 1 : Exécuter le script d'installation (15 min)

```bash
cd /var/www/kabas
sudo bash setup-testing-environment.sh
```

**Le script va vous demander** :
- Les credentials MySQL root
- Les credentials MySQL pour kabas_testing (vous pouvez créer un nouvel utilisateur)
- Les credentials MySQL de la base de production
- Le nom de la base de production

**Ce qu'il va faire automatiquement** :
1. Créer `/var/www/kabas-testing/` et `/var/www/kabas-site-testing/`
2. Copier les applications (sans node_modules, vendor, .git)
3. Installer les dépendances (composer + npm)
4. Créer les fichiers `.env` configurés pour testing
5. Créer les vhosts Apache
6. Créer la base de données `kabas_testing`
7. Copier les données de production vers testing
8. Générer les clés d'application

### Étape 2 : Ajouter les bandeaux TESTING (1 min)

```bash
cd /var/www/kabas
sudo bash add-testing-banner.sh
```

Ceci ajoutera un bandeau rouge "⚠️ ENVIRONNEMENT DE TESTING ⚠️" en haut de toutes les pages.

### Étape 3 : Configurer le DNS

Vous devez ajouter ces 3 enregistrements DNS pointant vers votre serveur :

```
testing-bo.kabasconceptstore.com  → [IP de votre serveur]
testing-pos.kabasconceptstore.com → [IP de votre serveur]
testing.kabasconceptstore.com     → [IP de votre serveur]
```

### Étape 4 : (Optionnel) Obtenir les certificats SSL

```bash
sudo certbot --apache -d testing-bo.kabasconceptstore.com
sudo certbot --apache -d testing-pos.kabasconceptstore.com
sudo certbot --apache -d testing.kabasconceptstore.com
```

---

## ✨ APRÈS INSTALLATION

### Accès aux environnements

**Production** (inchangé) :
- BO : https://bo.kabasconceptstore.com
- POS : https://pos.kabasconceptstore.com
- Site : https://kabasconceptstore.com

**Testing** (nouveau) :
- BO : https://testing-bo.kabasconceptstore.com (avec bandeau rouge TESTING)
- POS : https://testing-pos.kabasconceptstore.com (avec bandeau rouge TESTING)
- Site : https://testing.kabasconceptstore.com (avec bandeau rouge TESTING)

### Test de fonctionnement

1. Accédez à https://testing-bo.kabasconceptstore.com
2. Vous devriez voir le bandeau rouge "⚠️ ENVIRONNEMENT DE TESTING ⚠️"
3. Connectez-vous avec vos identifiants habituels
4. Vérifiez que tout fonctionne normalement

---

## 💻 UTILISATION AU QUOTIDIEN

### Pour toute modification/amélioration

```bash
# 1. Travailler sur TESTING
cd /var/www/kabas-testing/
# Modifier les fichiers nécessaires

# 2. Tester sur testing-bo.kabasconceptstore.com

# 3. Demander validation

# 4. Déployer en production (après validation)
cd /var/www/kabas
sudo bash deploy-specific-files.sh app/Http/Controllers/MonController.php
```

### Exemple concret : Modification du système d'inventaire

```bash
# 1. DÉVELOPPEMENT
cd /var/www/kabas-testing/
vim app/Http/Controllers/InventoryController.php
vim resources/views/inventory/index.blade.php

# 2. TEST
# Ouvrir https://testing-bo.kabasconceptstore.com/inventory
# Tester les modifications

# 3. VALIDATION
# Demander à l'utilisateur de valider sur testing

# 4. DÉPLOIEMENT (après OK)
cd /var/www/kabas
sudo bash deploy-specific-files.sh \
    app/Http/Controllers/InventoryController.php \
    resources/views/inventory/index.blade.php \
    resources/lang/fr/messages.php \
    resources/lang/en/messages.php
```

---

## 📚 DOCUMENTATION DÉTAILLÉE

Pour tous les détails, consultez :

1. **`TESTING_ENVIRONMENT_GUIDE.md`** - Guide complet avec :
   - Architecture complète
   - Procédure de développement détaillée
   - Déploiement en production
   - Maintenance et dépannage
   - Checklist avant déploiement

2. **`CLAUDE_REMINDER.md`** - Aide-mémoire rapide avec :
   - Règles critiques à suivre
   - Workflow standard
   - Commandes utiles
   - Exemples concrets

3. **`README_TESTING_SETUP.md`** - Démarrage rapide

---

## ⚠️ RÈGLES IMPORTANTES

### TOUJOURS
- ✅ Travailler d'abord sur TESTING
- ✅ Faire valider par l'utilisateur
- ✅ Créer un backup avant déploiement
- ✅ Tester après déploiement

### JAMAIS
- ❌ Modifier directement la PRODUCTION
- ❌ Déployer sans validation
- ❌ Faire des DELETE/UPDATE directs sur la BDD de production
- ❌ Oublier de vider les caches après déploiement

---

## 🔧 MAINTENANCE

### Synchroniser les données PROD → TESTING (mensuel recommandé)

```bash
# Copier les données de production vers testing
mysqldump -u[user_prod] -p[pass_prod] kabas_prod | \
  mysql -u[user_test] -p[pass_test] kabas_testing
```

### Mettre à jour les dépendances

```bash
# Sur TESTING d'abord
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

## 📞 AIDE

En cas de problème :

1. Vérifier les logs :
   - Apache : `/var/log/apache2/testing_*.log`
   - Laravel : `/var/www/kabas-testing/storage/logs/laravel.log`

2. Vérifier la configuration :
   - `.env` : `/var/www/kabas-testing/.env`
   - Vhosts : `/etc/apache2/sites-available/testing-*.conf`

3. Consulter la documentation complète

---

## ✅ CHECKLIST POST-INSTALLATION

- [ ] Le script `setup-testing-environment.sh` s'est exécuté sans erreur
- [ ] Le script `add-testing-banner.sh` a ajouté les bandeaux
- [ ] Les DNS pointent vers le serveur
- [ ] Les 3 URLs de testing sont accessibles
- [ ] Le bandeau rouge "TESTING" est visible
- [ ] La connexion au BO testing fonctionne
- [ ] Les données sont présentes dans `kabas_testing`
- [ ] Les certificats SSL sont installés (optionnel)

---

**Date de création** : 2025-11-22
**Créé par** : Claude Code
**Version** : 1.0.0

**IMPORTANT** : Conservez ce fichier et la documentation associée pour référence future.
