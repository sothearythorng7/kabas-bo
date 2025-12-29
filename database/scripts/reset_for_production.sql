-- ============================================
-- SCRIPT DE RESET POUR PRODUCTION
-- Base de données: kabas
-- ============================================
-- ATTENTION: Ce script supprime toutes les données transactionnelles
-- Les données maîtres (produits, fournisseurs, stocks, etc.) sont préservées
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- POS / VENTES
-- ============================================
TRUNCATE TABLE sale_items;
TRUNCATE TABLE sales;
TRUNCATE TABLE shifts;

-- ============================================
-- TRANSACTIONS FINANCIÈRES
-- ============================================
TRUNCATE TABLE financial_transaction_attachments;
TRUNCATE TABLE financial_transaction_logs;
TRUNCATE TABLE financial_transactions;
TRUNCATE TABLE cash_transaction_items;
TRUNCATE TABLE cash_transactions;
TRUNCATE TABLE ledger_entries;
TRUNCATE TABLE journals;
TRUNCATE TABLE expenses;

-- ============================================
-- MOUVEMENTS DE STOCK
-- ============================================
TRUNCATE TABLE stock_movement_items;
TRUNCATE TABLE stock_movements;
TRUNCATE TABLE stock_transactions;

-- ============================================
-- RAPPORTS DE VENTES
-- ============================================
TRUNCATE TABLE sale_report_items;
TRUNCATE TABLE sale_reports;
TRUNCATE TABLE reseller_sales_report_anomalies;
TRUNCATE TABLE reseller_sales_report_items;
TRUNCATE TABLE reseller_sales_reports;

-- ============================================
-- FACTURES ET PAIEMENTS
-- ============================================
TRUNCATE TABLE general_invoices;
TRUNCATE TABLE reseller_invoice_payments;
TRUNCATE TABLE reseller_invoices;
TRUNCATE TABLE supplier_payments;

-- ============================================
-- LIVRAISONS RESELLERS
-- ============================================
TRUNCATE TABLE reseller_stock_delivery_product;
TRUNCATE TABLE reseller_stock_deliveries;
TRUNCATE TABLE reseller_stock_batches;

-- ============================================
-- COMMANDES FOURNISSEURS
-- ============================================
TRUNCATE TABLE supplier_order_invoice_lines;
TRUNCATE TABLE supplier_order_product;
TRUNCATE TABLE supplier_orders;
TRUNCATE TABLE warehouse_invoice_histories;
TRUNCATE TABLE warehouse_invoice_files;
TRUNCATE TABLE warehouse_invoices;
TRUNCATE TABLE refills;

-- ============================================
-- FACTORY / PRODUCTION
-- ============================================
TRUNCATE TABLE production_consumptions;
TRUNCATE TABLE productions;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- RÉSUMÉ
-- ============================================
SELECT 'Reset terminé!' AS status;
SELECT '32 tables vidées' AS info;
SELECT 'Données préservées: produits, catégories, fournisseurs, resellers, stocks, recettes, utilisateurs, configuration' AS preserved;
