/**
 * Receipt printer configuration — ported verbatim from the V1
 * `PRINTER_CONFIG` block in `resources/views/pos/screens/dashboard.blade.php`.
 *
 * Architecture:
 *   The POS does NOT talk ESC/POS directly. Each tablet/PC runs a small
 *   local daemon listening on http://localhost:8888 that bridges HTTP →
 *   thermal printer over USB/Bluetooth. The POS only needs to POST JSON.
 *
 * Endpoints:
 *   GET  /status        → { status: 'ok' | 'error', message? }
 *   POST /print         → { status: 'ok' | 'error', message? }   body = ticketData
 *   POST /open-drawer   → { status: 'ok' | 'error' }
 *
 * Per-store fields keep the exact V1 values so the printed header is
 * indistinguishable from V1 output.
 */
export const PRINTER_CONFIG = {
    url: 'http://localhost:8888',
    timeoutMs: 8_000,
    stores: {
        1: {
            prefix: 'PP',
            name: 'Phnom Penh',
            address: '#65 STREET 178, Phnom Penh 12302',
            phone: '015 656 122',
        },
        2: {
            prefix: 'SR',
            name: 'Siem Reap',
            address: '200 Pokambor Ave, Krong Siem Reap',
            phone: '016 606 133',
        },
    },
    footer: 'Thank you for your visit!',
};

export function getStoreConfig(storeId) {
    return PRINTER_CONFIG.stores[storeId] || PRINTER_CONFIG.stores[1];
}

/**
 * Ticket number format: `PREFIX-YYYYMMDD-XXX` where XXX = last 3 digits of sale id.
 */
export function generateTicketNumber(sale, storeId) {
    const cfg = getStoreConfig(storeId);
    const date = new Date(sale.date || sale.created_at || sale.finalized_at || Date.now());
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    const saleId = sale.db_id || sale.id;
    const idStr = String(saleId ?? '').slice(-3).padStart(3, '0');
    return `${cfg.prefix}-${y}${m}${d}-${idStr}`;
}

/** Date+time string in the V1 "YYYY-MM-DD HH:MM" format. */
export function formatTicketDateTime(date = null) {
    const dt = date ? new Date(date) : new Date();
    const y = dt.getFullYear();
    const m = String(dt.getMonth() + 1).padStart(2, '0');
    const d = String(dt.getDate()).padStart(2, '0');
    const hh = String(dt.getHours()).padStart(2, '0');
    const mm = String(dt.getMinutes()).padStart(2, '0');
    return `${y}-${m}-${d} ${hh}:${mm}`;
}
