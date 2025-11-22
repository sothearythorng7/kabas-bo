# 🎯 DÉMARRER ICI - Environnement de Testing Kabas

## 📌 TOUT EST PRÊT !

Tous les scripts et la documentation sont dans `/var/www/kabas/`

---

## 🚀 INSTALLATION EN 3 ÉTAPES

### 1️⃣ Installer l'environnement de testing (15 min)

```bash
cd /var/www/kabas
sudo bash setup-testing-environment.sh
```

### 2️⃣ Ajouter les bandeaux TESTING (1 min)

```bash
sudo bash add-testing-banner.sh
```

### 3️⃣ Configurer le DNS

Ajouter ces entrées DNS :
```
testing-bo.kabasconceptstore.com
testing-pos.kabasconceptstore.com
testing.kabasconceptstore.com
```

---

## 📚 DOCUMENTATION

### Pour l'installation
👉 **[INSTALLATION_INSTRUCTIONS.md](INSTALLATION_INSTRUCTIONS.md)** ⭐ LIRE EN PREMIER

### Pour l'utilisation quotidienne
👉 **[TESTING_ENVIRONMENT_GUIDE.md](TESTING_ENVIRONMENT_GUIDE.md)** - Guide complet
👉 **[CLAUDE_REMINDER.md](CLAUDE_REMINDER.md)** - Aide-mémoire pour Claude
👉 **[README_TESTING_SETUP.md](README_TESTING_SETUP.md)** - Démarrage rapide

---

## 💡 CONCEPT SIMPLE

```
┌─────────────────────────────────────────────────────┐
│                    AVANT                             │
├─────────────────────────────────────────────────────┤
│  ❌ Modifications directement sur PRODUCTION        │
│  ❌ Risques pour la base de données                 │
│  ❌ Impossibilité de revenir en arrière             │
│  ❌ Perturbation du travail du staff                │
└─────────────────────────────────────────────────────┘

                      ⬇️

┌─────────────────────────────────────────────────────┐
│                  MAINTENANT                          │
├─────────────────────────────────────────────────────┤
│  ✅ TESTING → Développement et tests                │
│  ✅ VALIDATION → L'utilisateur vérifie              │
│  ✅ PRODUCTION → Déploiement sécurisé               │
│  ✅ Aucun risque pour la production                 │
└─────────────────────────────────────────────────────┘
```

---

## 🎨 IDENTIFICATION VISUELLE

### Production (PAS de bandeau)
```
┌─────────────────────────────────┐
│  [Logo] Kabas Back-Office       │ ← PAS de bandeau
├─────────────────────────────────┤
│  Dashboard                       │
│  ...                            │
```

### Testing (Bandeau ROUGE)
```
┌─────────────────────────────────┐
│ ⚠️ ENVIRONNEMENT DE TESTING ⚠️  │ ← BANDEAU ROUGE
├─────────────────────────────────┤
│  [Logo] Kabas Back-Office       │
│  Dashboard                       │
│  ...                            │
```

---

## 🔄 WORKFLOW QUOTIDIEN

```
1️⃣ DÉVELOPPER sur TESTING
   cd /var/www/kabas-testing/
   [Modifier les fichiers]

2️⃣ TESTER
   https://testing-bo.kabasconceptstore.com
   [Vérifier que tout fonctionne]

3️⃣ FAIRE VALIDER
   L'utilisateur teste et valide

4️⃣ DÉPLOYER vers PRODUCTION
   sudo bash deploy-specific-files.sh [fichiers...]
```

---

## 📂 STRUCTURE DES RÉPERTOIRES

```
/var/www/
├── kabas/                    → ❌ PRODUCTION (ne pas toucher)
├── kabas-site/               → ❌ PRODUCTION (ne pas toucher)
├── kabas-testing/            → ✅ TESTING (travailler ici)
└── kabas-site-testing/       → ✅ TESTING (travailler ici)
```

---

## 🛠️ SCRIPTS DISPONIBLES

| Script | Usage | Quand |
|--------|-------|-------|
| `setup-testing-environment.sh` | Installation initiale | Une seule fois |
| `add-testing-banner.sh` | Ajouter les bandeaux | Une seule fois |
| `deploy-specific-files.sh` | Déployer en production | Après validation |

---

## ⚡ EXEMPLE RAPIDE

### Modifier le système d'inventaire

```bash
# 1. CODER
cd /var/www/kabas-testing/
vim app/Http/Controllers/InventoryController.php

# 2. TESTER
# Ouvrir: https://testing-bo.kabasconceptstore.com/inventory

# 3. VALIDER
# Demander à l'utilisateur de tester

# 4. DÉPLOYER (après OK)
cd /var/www/kabas
sudo bash deploy-specific-files.sh \
    app/Http/Controllers/InventoryController.php
```

---

## ⚠️ RÈGLES D'OR

### ✅ TOUJOURS
- Travailler sur TESTING en premier
- Faire valider par l'utilisateur
- Créer un backup avant déploiement
- Vider les caches après déploiement

### ❌ JAMAIS
- Modifier directement PRODUCTION
- Déployer sans validation
- DELETE/UPDATE direct sur la BDD prod
- Copier tout un répertoire (seulement les fichiers modifiés)

---

## 🎯 PROCHAINES ÉTAPES

1. **[ ]** Lire [INSTALLATION_INSTRUCTIONS.md](INSTALLATION_INSTRUCTIONS.md)
2. **[ ]** Exécuter `sudo bash setup-testing-environment.sh`
3. **[ ]** Exécuter `sudo bash add-testing-banner.sh`
4. **[ ]** Configurer le DNS
5. **[ ]** Tester l'accès aux URLs de testing
6. **[ ]** Vérifier que les bandeaux apparaissent
7. **[ ]** Lire [TESTING_ENVIRONMENT_GUIDE.md](TESTING_ENVIRONMENT_GUIDE.md)

---

## 📞 BESOIN D'AIDE ?

Consulter dans l'ordre :
1. [INSTALLATION_INSTRUCTIONS.md](INSTALLATION_INSTRUCTIONS.md)
2. [TESTING_ENVIRONMENT_GUIDE.md](TESTING_ENVIRONMENT_GUIDE.md)
3. Logs Apache : `/var/log/apache2/`
4. Logs Laravel : `/var/www/kabas-testing/storage/logs/`

---

**🎉 BONNE CHANCE !**

L'environnement de testing va vous permettre de travailler en toute sécurité sans perturber la production ni le travail du staff.

---

**Date de création** : 2025-11-22
**Créé par** : Claude Code
**Version** : 1.0.0
