#!/bin/bash

# Script pour ajouter le bandeau TESTING aux applications
# Ce script modifie les layouts pour afficher un bandeau rouge "TESTING"

set -e

echo "=================================================="
echo "  Ajout du bandeau TESTING aux applications"
echo "=================================================="

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# ============================================
# 1. AJOUT DU BANDEAU AU BACK-OFFICE (kabas-testing)
# ============================================
echo -e "${YELLOW}[1/3] Ajout du bandeau au Back-Office...${NC}"

# Créer le fichier de bandeau pour le BO
cat > /var/www/kabas-testing/resources/views/layouts/testing-banner.blade.php << 'BANNER1'
{{-- Bandeau TESTING --}}
@if(config('app.env') === 'testing')
<div style="position: fixed; top: 0; left: 0; right: 0; z-index: 9999; background: linear-gradient(135deg, #ff0000 0%, #cc0000 100%); color: white; padding: 8px 20px; text-align: center; font-weight: bold; font-size: 14px; box-shadow: 0 2px 10px rgba(0,0,0,0.3); border-bottom: 3px solid #990000;">
    <div style="display: flex; align-items: center; justify-content: center; gap: 10px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16" style="flex-shrink: 0;">
            <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
        </svg>
        <span style="letter-spacing: 3px;">⚠️ ENVIRONNEMENT DE TESTING ⚠️</span>
        <span style="background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 4px; font-size: 12px; letter-spacing: 1px;">TEST ONLY</span>
    </div>
</div>
<div style="height: 44px;"></div>
@endif
BANNER1

# Modifier le layout principal du BO
if grep -q "testing-banner" /var/www/kabas-testing/resources/views/layouts/app.blade.php 2>/dev/null; then
    echo "Le bandeau est déjà présent dans app.blade.php"
else
    # Ajouter l'include du bandeau juste après <body>
    sed -i '/<body/a\    @include('"'"'layouts.testing-banner'"'"')' /var/www/kabas-testing/resources/views/layouts/app.blade.php
    echo "Bandeau ajouté à app.blade.php"
fi

echo -e "${GREEN}✓ Bandeau BO ajouté${NC}"

# ============================================
# 2. AJOUT DU BANDEAU AU POS (kabas-testing)
# ============================================
echo -e "${YELLOW}[2/3] Ajout du bandeau au POS...${NC}"

# Créer le fichier de bandeau pour le POS
cat > /var/www/kabas-testing/resources/views/pos/testing-banner.blade.php << 'BANNER2'
{{-- Bandeau TESTING pour POS --}}
@if(config('app.env') === 'testing')
<div id="testing-banner" style="position: fixed; top: 0; left: 0; right: 0; z-index: 10000; background: linear-gradient(135deg, #ff0000 0%, #cc0000 100%); color: white; padding: 6px 15px; text-align: center; font-weight: bold; font-size: 13px; box-shadow: 0 2px 8px rgba(0,0,0,0.4); border-bottom: 2px solid #990000;">
    <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
        <i class="bi bi-exclamation-triangle-fill" style="font-size: 16px;"></i>
        <span style="letter-spacing: 2px;">MODE TEST</span>
        <span style="background: rgba(255,255,255,0.2); padding: 1px 6px; border-radius: 3px; font-size: 11px;">TESTING</span>
    </div>
</div>
<style>
    /* Ajuster le padding du contenu pour ne pas qu'il soit caché par le bandeau */
    @media (min-width: 1px) {
        body { padding-top: 38px !important; }
        #pos-container { margin-top: 0 !important; }
    }
</style>
@endif
BANNER2

# Modifier le layout POS
if grep -q "testing-banner" /var/www/kabas-testing/resources/views/pos/index.blade.php 2>/dev/null; then
    echo "Le bandeau est déjà présent dans POS index.blade.php"
else
    # Ajouter l'include du bandeau juste après <body>
    sed -i '/<body/a\    @include('"'"'pos.testing-banner'"'"')' /var/www/kabas-testing/resources/views/pos/index.blade.php
    echo "Bandeau ajouté à POS index.blade.php"
fi

echo -e "${GREEN}✓ Bandeau POS ajouté${NC}"

# ============================================
# 3. AJOUT DU BANDEAU AU SITE PUBLIC (kabas-site-testing)
# ============================================
echo -e "${YELLOW}[3/3] Ajout du bandeau au Site Public...${NC}"

# Créer le fichier de bandeau pour le site
cat > /var/www/kabas-site-testing/resources/views/partials/testing-banner.blade.php << 'BANNER3'
{{-- Bandeau TESTING pour Site Public --}}
@if(config('app.env') === 'testing')
<div style="position: fixed; top: 0; left: 0; right: 0; z-index: 9999; background: linear-gradient(135deg, #ff0000 0%, #cc0000 100%); color: white; padding: 8px 20px; text-align: center; font-weight: bold; font-size: 13px; box-shadow: 0 2px 10px rgba(0,0,0,0.3); border-bottom: 3px solid #990000;">
    <div style="display: flex; align-items: center; justify-content: center; gap: 10px; flex-wrap: wrap;">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" style="flex-shrink: 0;">
            <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
        </svg>
        <span style="letter-spacing: 2px;">SITE DE TEST</span>
        <span style="background: rgba(255,255,255,0.25); padding: 2px 8px; border-radius: 4px; font-size: 11px;">TESTING ENVIRONMENT</span>
    </div>
</div>
<style>
    /* Ajuster le padding du body pour ne pas cacher le contenu */
    body { padding-top: 44px !important; }
</style>
@endif
BANNER3

# Modifier le layout du site
if [ -f "/var/www/kabas-site-testing/resources/views/layout.blade.php" ]; then
    if grep -q "testing-banner" /var/www/kabas-site-testing/resources/views/layout.blade.php; then
        echo "Le bandeau est déjà présent dans layout.blade.php"
    else
        # Ajouter l'include du bandeau juste après <body>
        sed -i '/<body/a\    @include('"'"'partials.testing-banner'"'"')' /var/www/kabas-site-testing/resources/views/layout.blade.php
        echo "Bandeau ajouté à layout.blade.php"
    fi
fi

echo -e "${GREEN}✓ Bandeau Site Public ajouté${NC}"

# ============================================
# RÉSUMÉ
# ============================================
echo ""
echo -e "${GREEN}=================================================="
echo "  ✓ BANDEAU TESTING AJOUTÉ AUX 3 APPLICATIONS !"
echo "==================================================${NC}"
echo ""
echo "Les bandeaux rouges 'TESTING' s'afficheront en haut de toutes les pages"
echo "pour les environnements où APP_ENV=testing"
echo ""
echo "Fichiers créés:"
echo "  - /var/www/kabas-testing/resources/views/layouts/testing-banner.blade.php"
echo "  - /var/www/kabas-testing/resources/views/pos/testing-banner.blade.php"
echo "  - /var/www/kabas-site-testing/resources/views/partials/testing-banner.blade.php"
echo ""
