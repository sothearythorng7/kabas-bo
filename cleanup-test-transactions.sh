#!/bin/bash

# =============================================================================
# Script de nettoyage des transactions de test
# Supprime toutes les données transactionnelles jusqu'au 2 décembre 2025 inclus
# CONSERVE : produits, fournisseurs, revendeurs, stocks actuels, configuration
# =============================================================================

set -e

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
DATE_LIMITE="2025-12-02 23:59:59"
BACKUP_DIR="/var/www/kabas/backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

echo -e "${BLUE}================================================================${NC}"
echo -e "${BLUE}  NETTOYAGE DES TRANSACTIONS DE TEST${NC}"
echo -e "${BLUE}  Date limite: ${DATE_LIMITE}${NC}"
echo -e "${BLUE}================================================================${NC}"
echo ""

# Demander les credentials MySQL
read -p "Base de données [kabas_testing]: " DB_NAME
DB_NAME=${DB_NAME:-kabas_testing}

read -p "Utilisateur MySQL: " DB_USER
read -sp "Mot de passe MySQL: " DB_PASS
echo ""

# Fonction pour exécuter une requête SQL
run_sql() {
    mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "$1"
}

# Fonction pour compter les enregistrements à supprimer
count_records() {
    local table=$1
    local date_column=$2
    local count=$(mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -N -e "SELECT COUNT(*) FROM $table WHERE $date_column <= '$DATE_LIMITE';" 2>/dev/null)
    echo $count
}

# Vérification de la connexion
echo -e "${YELLOW}Vérification de la connexion à la base de données...${NC}"
if ! mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "SELECT 1;" > /dev/null 2>&1; then
    echo -e "${RED}Erreur: Impossible de se connecter à la base de données${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Connexion OK${NC}"
echo ""

# =============================================================================
# PHASE 1: ANALYSE - Compter les enregistrements à supprimer
# =============================================================================
echo -e "${YELLOW}[PHASE 1] Analyse des données à supprimer...${NC}"
echo ""

declare -A COUNTS

# Ventes POS
COUNTS[shifts]=$(count_records "shifts" "started_at")
COUNTS[sales]=$(count_records "sales" "created_at")

# Transactions financières
COUNTS[financial_transactions]=$(count_records "financial_transactions" "created_at")

# Comptabilité
COUNTS[journals]=$(count_records "journals" "created_at")
COUNTS[ledger_entries]=$(count_records "ledger_entries" "created_at")
COUNTS[cash_transactions]=$(count_records "cash_transactions" "created_at")
COUNTS[financial_journals]=$(count_records "financial_journals" "created_at")

# Dépenses & Factures
COUNTS[expenses]=$(count_records "expenses" "incurred_at")
COUNTS[general_invoices]=$(count_records "general_invoices" "created_at")
COUNTS[warehouse_invoices]=$(count_records "warehouse_invoices" "created_at")

# Commandes fournisseurs
COUNTS[supplier_orders]=$(count_records "supplier_orders" "created_at")
COUNTS[supplier_payments]=$(count_records "supplier_payments" "created_at")

# Mouvements de stock (PAS les lots)
COUNTS[stock_movements]=$(count_records "stock_movements" "created_at")
COUNTS[stock_transactions]=$(count_records "stock_transactions" "created_at")
COUNTS[purchase_price_histories]=$(count_records "purchase_price_histories" "changed_at")

# Matières premières - mouvements
COUNTS[raw_material_stock_movements]=$(count_records "raw_material_stock_movements" "created_at")
COUNTS[productions]=$(count_records "productions" "created_at")

# Revendeurs
COUNTS[reseller_stock_deliveries]=$(count_records "reseller_stock_deliveries" "created_at")
COUNTS[reseller_sales_reports]=$(count_records "reseller_sales_reports" "created_at")
COUNTS[resellers_invoices]=$(count_records "resellers_invoices" "created_at")
COUNTS[reseller_stock_returns]=$(count_records "reseller_stock_returns" "created_at")

# Refills
COUNTS[refills]=$(count_records "refills" "created_at")

# Rapports de ventes
COUNTS[sale_reports]=$(count_records "sale_reports" "created_at")

# Gift card codes
COUNTS[gift_card_codes]=$(count_records "gift_card_codes" "created_at")

# Messages contact
COUNTS[contact_messages]=$(count_records "contact_messages" "created_at")

# Afficher le résumé
echo -e "${BLUE}┌─────────────────────────────────────────┬────────────┐${NC}"
echo -e "${BLUE}│ Table                                   │ À supprimer│${NC}"
echo -e "${BLUE}├─────────────────────────────────────────┼────────────┤${NC}"

TOTAL=0
for table in "${!COUNTS[@]}"; do
    count=${COUNTS[$table]}
    TOTAL=$((TOTAL + count))
    printf "${BLUE}│${NC} %-39s ${BLUE}│${NC} %10s ${BLUE}│${NC}\n" "$table" "$count"
done

echo -e "${BLUE}├─────────────────────────────────────────┼────────────┤${NC}"
printf "${BLUE}│${NC} ${YELLOW}%-39s${NC} ${BLUE}│${NC} ${YELLOW}%10s${NC} ${BLUE}│${NC}\n" "TOTAL" "$TOTAL"
echo -e "${BLUE}└─────────────────────────────────────────┴────────────┘${NC}"
echo ""

if [ "$TOTAL" -eq 0 ]; then
    echo -e "${GREEN}Aucune donnée à supprimer.${NC}"
    exit 0
fi

# Confirmation
echo -e "${RED}⚠️  ATTENTION: Cette opération est IRRÉVERSIBLE !${NC}"
echo -e "${YELLOW}Les stocks actuels (stock_batches, product_store) seront CONSERVÉS.${NC}"
echo ""
read -p "Voulez-vous créer un backup avant de continuer? (o/N): " DO_BACKUP

if [[ "$DO_BACKUP" =~ ^[Oo]$ ]]; then
    echo -e "${YELLOW}Création du backup...${NC}"
    mkdir -p "$BACKUP_DIR"
    BACKUP_FILE="$BACKUP_DIR/backup_before_cleanup_$TIMESTAMP.sql"
    mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_FILE"
    echo -e "${GREEN}✓ Backup créé: $BACKUP_FILE${NC}"
fi

echo ""
read -p "Confirmer la suppression des données jusqu'au $DATE_LIMITE ? (oui/NON): " CONFIRM

if [ "$CONFIRM" != "oui" ]; then
    echo -e "${YELLOW}Opération annulée.${NC}"
    exit 0
fi

# =============================================================================
# PHASE 2: SUPPRESSION
# =============================================================================
echo ""
echo -e "${YELLOW}[PHASE 2] Suppression des données...${NC}"
echo ""

# Désactiver les contraintes de clés étrangères
run_sql "SET FOREIGN_KEY_CHECKS = 0;"

# --- VENTES POS ---
echo -e "${BLUE}[1/12] Ventes POS...${NC}"
run_sql "DELETE FROM sale_items WHERE sale_id IN (SELECT id FROM sales WHERE created_at <= '$DATE_LIMITE');"
run_sql "DELETE FROM sales WHERE created_at <= '$DATE_LIMITE';"
run_sql "DELETE FROM shifts WHERE started_at <= '$DATE_LIMITE';"
echo -e "${GREEN}✓ Ventes POS supprimées${NC}"

# --- RAPPORTS DE VENTES ---
echo -e "${BLUE}[2/12] Rapports de ventes...${NC}"
run_sql "DELETE FROM sale_reports WHERE created_at <= '$DATE_LIMITE';"
echo -e "${GREEN}✓ Rapports de ventes supprimés${NC}"

# --- TRANSACTIONS FINANCIÈRES ---
echo -e "${BLUE}[3/12] Transactions financières...${NC}"
run_sql "DELETE FROM financial_transaction_logs WHERE transaction_id IN (SELECT id FROM financial_transactions WHERE created_at <= '$DATE_LIMITE');"
run_sql "DELETE FROM financial_transaction_attachments WHERE transaction_id IN (SELECT id FROM financial_transactions WHERE created_at <= '$DATE_LIMITE');"
run_sql "DELETE FROM financial_transactions WHERE created_at <= '$DATE_LIMITE';"
echo -e "${GREEN}✓ Transactions financières supprimées${NC}"

# --- COMPTABILITÉ ---
echo -e "${BLUE}[4/12] Comptabilité...${NC}"
run_sql "DELETE FROM ledger_entries WHERE created_at <= '$DATE_LIMITE';"
run_sql "DELETE FROM journals WHERE created_at <= '$DATE_LIMITE';"
run_sql "DELETE FROM cash_transaction_items WHERE cash_transaction_id IN (SELECT id FROM cash_transactions WHERE created_at <= '$DATE_LIMITE');"
run_sql "DELETE FROM cash_transactions WHERE created_at <= '$DATE_LIMITE';"
run_sql "DELETE FROM financial_journals WHERE created_at <= '$DATE_LIMITE';"
echo -e "${GREEN}✓ Comptabilité supprimée${NC}"

# --- DÉPENSES ---
echo -e "${BLUE}[5/12] Dépenses...${NC}"
run_sql "DELETE FROM documents WHERE documentable_type = 'App\\\\Models\\\\Expense' AND created_at <= '$DATE_LIMITE';"
run_sql "DELETE FROM expenses WHERE incurred_at <= '$DATE_LIMITE';"
echo -e "${GREEN}✓ Dépenses supprimées${NC}"

# --- FACTURES ---
echo -e "${BLUE}[6/12] Factures...${NC}"
run_sql "DELETE FROM general_invoices WHERE created_at <= '$DATE_LIMITE';"
run_sql "DELETE FROM warehouse_invoice_status_histories WHERE warehouse_invoice_id IN (SELECT id FROM warehouse_invoices WHERE created_at <= '$DATE_LIMITE');"
run_sql "DELETE FROM warehouse_invoice_histories WHERE warehouse_invoice_id IN (SELECT id FROM warehouse_invoices WHERE created_at <= '$DATE_LIMITE');"
run_sql "DELETE FROM warehouse_invoice_files WHERE warehouse_invoice_id IN (SELECT id FROM warehouse_invoices WHERE created_at <= '$DATE_LIMITE');"
run_sql "DELETE FROM warehouse_invoices WHERE created_at <= '$DATE_LIMITE';"
echo -e "${GREEN}✓ Factures supprimées${NC}"

# --- COMMANDES FOURNISSEURS ---
echo -e "${BLUE}[7/12] Commandes fournisseurs...${NC}"
run_sql "DELETE FROM supplier_order_invoice_lines WHERE supplier_order_id IN (SELECT id FROM supplier_orders WHERE created_at <= '$DATE_LIMITE');"
run_sql "DELETE FROM supplier_order_product WHERE supplier_order_id IN (SELECT id FROM supplier_orders WHERE created_at <= '$DATE_LIMITE');"
# Vérifier si la table existe avant de supprimer
run_sql "DELETE FROM supplier_order_raw_material WHERE supplier_order_id IN (SELECT id FROM supplier_orders WHERE created_at <= '$DATE_LIMITE');" 2>/dev/null || true
run_sql "DELETE FROM supplier_orders WHERE created_at <= '$DATE_LIMITE';"
run_sql "DELETE FROM supplier_payments WHERE created_at <= '$DATE_LIMITE';"
echo -e "${GREEN}✓ Commandes fournisseurs supprimées${NC}"

# --- MOUVEMENTS DE STOCK (pas les lots!) ---
echo -e "${BLUE}[8/12] Mouvements de stock...${NC}"
run_sql "DELETE FROM stock_movement_items WHERE stock_movement_id IN (SELECT id FROM stock_movements WHERE created_at <= '$DATE_LIMITE');"
run_sql "DELETE FROM stock_movements WHERE created_at <= '$DATE_LIMITE';"
run_sql "DELETE FROM stock_transactions WHERE created_at <= '$DATE_LIMITE';"
run_sql "DELETE FROM purchase_price_histories WHERE changed_at <= '$DATE_LIMITE';"
echo -e "${GREEN}✓ Mouvements de stock supprimés (lots conservés)${NC}"

# --- MATIÈRES PREMIÈRES ---
echo -e "${BLUE}[9/12] Mouvements matières premières...${NC}"
run_sql "DELETE FROM production_consumptions WHERE production_id IN (SELECT id FROM productions WHERE created_at <= '$DATE_LIMITE');"
run_sql "DELETE FROM productions WHERE created_at <= '$DATE_LIMITE';"
run_sql "DELETE FROM raw_material_stock_movements WHERE created_at <= '$DATE_LIMITE';"
echo -e "${GREEN}✓ Mouvements matières premières supprimés (lots conservés)${NC}"

# --- REVENDEURS ---
echo -e "${BLUE}[10/12] Transactions revendeurs...${NC}"
run_sql "DELETE FROM reseller_sales_report_anomalies WHERE report_id IN (SELECT id FROM reseller_sales_reports WHERE created_at <= '$DATE_LIMITE');"
run_sql "DELETE FROM reseller_sales_report_items WHERE report_id IN (SELECT id FROM reseller_sales_reports WHERE created_at <= '$DATE_LIMITE');" 2>/dev/null || true
run_sql "DELETE FROM resellers_invoice_payments WHERE resellers_invoice_id IN (SELECT id FROM resellers_invoices WHERE created_at <= '$DATE_LIMITE');"
run_sql "DELETE FROM resellers_invoices WHERE created_at <= '$DATE_LIMITE';"
run_sql "DELETE FROM reseller_sales_reports WHERE created_at <= '$DATE_LIMITE';"
run_sql "DELETE FROM reseller_stock_delivery_product WHERE reseller_stock_delivery_id IN (SELECT id FROM reseller_stock_deliveries WHERE created_at <= '$DATE_LIMITE');"
run_sql "DELETE FROM reseller_stock_deliveries WHERE created_at <= '$DATE_LIMITE';"
run_sql "DELETE FROM reseller_stock_returns WHERE created_at <= '$DATE_LIMITE';" 2>/dev/null || true
echo -e "${GREEN}✓ Transactions revendeurs supprimées${NC}"

# --- REFILLS ---
echo -e "${BLUE}[11/12] Refills...${NC}"
run_sql "DELETE FROM refill_product WHERE refill_id IN (SELECT id FROM refills WHERE created_at <= '$DATE_LIMITE');" 2>/dev/null || true
run_sql "DELETE FROM refills WHERE created_at <= '$DATE_LIMITE';"
echo -e "${GREEN}✓ Refills supprimés${NC}"

# --- DIVERS ---
echo -e "${BLUE}[12/12] Données diverses...${NC}"
run_sql "DELETE FROM gift_card_codes WHERE created_at <= '$DATE_LIMITE';"
run_sql "DELETE FROM contact_messages WHERE created_at <= '$DATE_LIMITE';"
echo -e "${GREEN}✓ Données diverses supprimées${NC}"

# Réactiver les contraintes
run_sql "SET FOREIGN_KEY_CHECKS = 1;"

# =============================================================================
# RÉSUMÉ FINAL
# =============================================================================
echo ""
echo -e "${GREEN}================================================================${NC}"
echo -e "${GREEN}  ✓ NETTOYAGE TERMINÉ AVEC SUCCÈS${NC}"
echo -e "${GREEN}================================================================${NC}"
echo ""
echo -e "Données supprimées jusqu'au: ${YELLOW}$DATE_LIMITE${NC}"
echo ""
echo -e "${BLUE}Tables CONSERVÉES:${NC}"
echo "  - products, product_images, product_variations"
echo "  - stock_batches (lots de stock)"
echo "  - product_store (quantités par magasin)"
echo "  - raw_material_stock_batches (lots matières premières)"
echo "  - suppliers, resellers, stores, users"
echo "  - categories, brands, gift_cards, gift_boxes"
echo "  - recipes, raw_materials"
echo "  - Toute la configuration"
echo ""

if [[ "$DO_BACKUP" =~ ^[Oo]$ ]]; then
    echo -e "${YELLOW}Backup disponible: $BACKUP_FILE${NC}"
fi
