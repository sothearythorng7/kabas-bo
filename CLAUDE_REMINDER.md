# 🤖 Aide-Mémoire pour Claude

## ⚠️ RÈGLES CRITIQUES À SUIVRE

### 🔴 RÈGLE #1 : NE JAMAIS TOUCHER À LA PRODUCTION DIRECTEMENT

**TOUJOURS travailler sur TESTING en premier !**

- ❌ NE PAS modifier `/var/www/kabas/`
- ❌ NE PAS modifier `/var/www/kabas-site/`
- ❌ NE PAS toucher à la base `kabas_prod`
- ✅ MODIFIER `/var/www/kabas-testing/`
- ✅ MODIFIER `/var/www/kabas-site-testing/`
- ✅ UTILISER la base `kabas_testing`

### 🔴 RÈGLE #2 : Ne JAMAIS faire de DELETE ou UPDATE direct sur la BDD

- ❌ PAS de `DELETE FROM ...`
- ❌ PAS de `UPDATE ... SET ...`
- ❌ PAS de `DROP TABLE ...`
- ✅ Utiliser les migrations Laravel
- ✅ Utiliser les modèles Eloquent
- ✅ Toujours demander confirmation avant toute opération destructive

---

## 📂 Structure des répertoires

### Production (NE PAS TOUCHER)
```
/var/www/kabas/          → BO + POS Production
/var/www/kabas-site/     → Site Public Production
Base de données : kabas_prod
```

### Testing (TRAVAILLER ICI)
```
/var/www/kabas-testing/      → BO + POS Testing
/var/www/kabas-site-testing/ → Site Public Testing
Base de données : kabas_testing
```

---

## 🔄 Workflow standard

```
1. DÉVELOPPEMENT
   └─> Coder dans /var/www/kabas-testing/
   └─> ou /var/www/kabas-site-testing/

2. TEST
   └─> Tester sur https://testing-bo.kabasconceptstore.com
   └─> ou https://testing-pos.kabasconceptstore.com
   └─> ou https://testing.kabasconceptstore.com

3. VALIDATION UTILISATEUR
   └─> L'utilisateur teste
   └─> L'utilisateur valide

4. DÉPLOIEMENT (seulement après validation)
   └─> Copier les fichiers modifiés vers PROD
   └─> Vider les caches
   └─> Vérifier
```

---

## 🛠️ Commandes utiles

### Identifier les fichiers modifiés sur TESTING
```bash
cd /var/www/kabas-testing
git status
# ou
git diff --name-only
```

### Déployer UN fichier vers PROD (après validation)
```bash
# Exemple: InventoryController.php
sudo cp /var/www/kabas-testing/app/Http/Controllers/InventoryController.php \
        /var/www/kabas/app/Http/Controllers/InventoryController.php

sudo chown siwei:www-data /var/www/kabas/app/Http/Controllers/InventoryController.php
```

### Vider les caches après déploiement
```bash
cd /var/www/kabas
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

### Synchroniser les données PROD → TESTING
```bash
mysqldump -u[user_prod] -p[pass_prod] kabas_prod | \
  mysql -u[user_test] -p[pass_test] kabas_testing
```

---

## 📝 Checklist avant chaque modification

Avant de commencer à coder, vérifier :

- [ ] Je travaille bien sur `/var/www/kabas-testing/` ou `/var/www/kabas-site-testing/`
- [ ] La base de données est bien `kabas_testing`
- [ ] L'environnement est bien `APP_ENV=testing`
- [ ] Le bandeau rouge "TESTING" est visible

Avant de déployer en production :

- [ ] L'utilisateur a validé les modifications
- [ ] J'ai identifié TOUS les fichiers modifiés
- [ ] J'ai créé un backup des fichiers qui seront remplacés
- [ ] J'ai testé une dernière fois sur TESTING
- [ ] Je copie UNIQUEMENT les fichiers modifiés (pas tout le répertoire)
- [ ] Je vérifie les permissions après copie
- [ ] Je vide les caches
- [ ] Je teste sur PROD que tout fonctionne

---

## 🚨 En cas d'erreur sur PRODUCTION

Si quelque chose ne fonctionne pas après déploiement :

1. **NE PAS PANIQUER**
2. **Restaurer le backup** :
   ```bash
   cp /var/www/backups/[backup_dir]/[fichier] /var/www/kabas/[chemin]/[fichier]
   ```
3. Vider les caches
4. Analyser le problème sur TESTING
5. Corriger sur TESTING
6. Retester
7. Re-déployer

---

## 📍 URLs importantes

### Production
- BO : https://bo.kabasconceptstore.com
- POS : https://pos.kabasconceptstore.com
- Site : https://kabasconceptstore.com

### Testing
- BO : https://testing-bo.kabasconceptstore.com
- POS : https://testing-pos.kabasconceptstore.com
- Site : https://testing.kabasconceptstore.com

---

## 🎯 Exemples de tâches courantes

### Ajouter une nouvelle fonctionnalité

1. Coder dans `/var/www/kabas-testing/app/Http/Controllers/NewFeatureController.php`
2. Créer la vue dans `/var/www/kabas-testing/resources/views/new-feature/`
3. Ajouter les routes dans `/var/www/kabas-testing/routes/web.php`
4. Tester sur testing-bo.kabasconceptstore.com
5. Demander validation à l'utilisateur
6. Après validation, copier les fichiers vers PROD

### Modifier une vue existante

1. Éditer `/var/www/kabas-testing/resources/views/inventory/index.blade.php`
2. Tester sur testing-bo.kabasconceptstore.com/inventory
3. Demander validation
4. Copier vers `/var/www/kabas/resources/views/inventory/index.blade.php`
5. Vider les caches

### Ajouter une migration

1. Créer la migration sur TESTING :
   ```bash
   cd /var/www/kabas-testing
   php artisan make:migration add_field_to_table
   ```
2. Éditer le fichier de migration
3. Exécuter sur TESTING :
   ```bash
   php artisan migrate
   ```
4. Tester que tout fonctionne
5. **Faire un backup de la BDD PROD !**
6. Copier le fichier de migration vers PROD
7. Exécuter sur PROD :
   ```bash
   cd /var/www/kabas
   php artisan migrate
   ```

---

## 💡 Bonnes pratiques

- ✅ Toujours commiter les changements sur Git (sur TESTING)
- ✅ Documenter les modifications importantes
- ✅ Créer des backups avant tout déploiement
- ✅ Tester après chaque déploiement
- ✅ Informer l'utilisateur des changements déployés
- ❌ Ne jamais déployer sans validation
- ❌ Ne jamais copier tout un répertoire (seulement les fichiers modifiés)
- ❌ Ne jamais oublier de vider les caches
- ❌ Ne jamais modifier directement la production

---

## 📖 Documentation complète

Consulter : `/var/www/kabas/TESTING_ENVIRONMENT_GUIDE.md`

---

**Dernière mise à jour** : 2025-11-22
