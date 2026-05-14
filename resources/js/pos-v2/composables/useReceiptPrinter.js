import { PRINTER_CONFIG, getStoreConfig, generateTicketNumber, formatTicketDateTime } from '../config/printer.js';

/**
 * Receipt printer driver — ports V1 logic 1-for-1.
 *
 * Real path:  POST {PRINTER_CONFIG.url}/print  → local daemon → thermal printer.
 * Fallback:   if the local daemon is unreachable, open a web-print preview so
 *             reprints still work during dev / UAT without a physical printer.
 *
 * Also exposes openCashDrawer (auto-triggered after a CASH payment by the
 * dashboard) and checkPrinter (TopBar status indicator).
 */
export function useReceiptPrinter() {

    async function rawPost(path, body) {
        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), PRINTER_CONFIG.timeoutMs);
        try {
            const res = await fetch(`${PRINTER_CONFIG.url}${path}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: body !== undefined ? JSON.stringify(body) : undefined,
                signal: controller.signal,
                mode: 'cors',
            });
            clearTimeout(timer);
            try {
                return await res.json();
            } catch {
                return { status: res.ok ? 'ok' : 'error' };
            }
        } catch (err) {
            clearTimeout(timer);
            return { status: 'error', message: err.message, networkError: true };
        }
    }

    async function checkPrinter() {
        try {
            const controller = new AbortController();
            const timer = setTimeout(() => controller.abort(), 2_000);
            const res = await fetch(`${PRINTER_CONFIG.url}/status`, {
                method: 'GET',
                signal: controller.signal,
                mode: 'cors',
            });
            clearTimeout(timer);
            if (!res.ok) return false;
            const data = await res.json().catch(() => null);
            return data?.status === 'ok';
        } catch {
            return false;
        }
    }

    async function openCashDrawer() {
        return rawPost('/open-drawer', {});
    }

    /**
     * Builds the V1 ticket payload from a finalized sale.
     */
    function formatTicketData(sale, { storeId } = {}) {
        const cfg = getStoreConfig(storeId ?? sale.store_id);
        let subtotal = 0;
        const giftCardCodes = [];

        const items = (sale.items || []).map((item) => {
            const name = (typeof item.name === 'object' && item.name)
                ? (item.name.en || item.name.fr || item.product_name || '')
                : (item.name || item.product_name || '');
            const qty = Number(item.quantity || 0);
            const unitPrice = Number(item.price || item.unit_price || 0);
            const lineTotal = unitPrice * qty;

            let itemDiscountTotal = 0;
            if (Array.isArray(item.discounts)) {
                for (const d of item.discounts) {
                    const value = Number(d.value) || 0;
                    if (d.scope === 'unit') {
                        if (d.type === 'amount') itemDiscountTotal += value * qty;
                        else if (d.type === 'percent') itemDiscountTotal += unitPrice * (value / 100) * qty;
                    } else {
                        // default = 'line' scope (V1 behaviour when omitted)
                        if (d.type === 'amount') itemDiscountTotal += value;
                        else if (d.type === 'percent') itemDiscountTotal += lineTotal * (value / 100);
                    }
                }
            }
            if (itemDiscountTotal > lineTotal) itemDiscountTotal = lineTotal;
            const amount = lineTotal - itemDiscountTotal;
            subtotal += amount;

            if (item.type === 'gift_card' && item.generated_code) {
                giftCardCodes.push({
                    name,
                    code: item.generated_code,
                    amount: unitPrice.toFixed(2),
                });
            }

            return {
                label: name,
                qty,
                unit_price: unitPrice.toFixed(2),
                discount: itemDiscountTotal.toFixed(2),
                amount: amount.toFixed(2),
            };
        });

        // Global discounts
        let globalDiscountTotal = 0;
        let globalDiscountPercent = 0;
        for (const d of (sale.discounts || [])) {
            if (!d) continue;
            if (d.type === 'amount') {
                globalDiscountTotal += Number(d.value) || 0;
            } else if (d.type === 'percent') {
                globalDiscountPercent += Number(d.value) || 0;
                globalDiscountTotal += subtotal * ((Number(d.value) || 0) / 100);
            }
        }
        const total = Math.max(0, subtotal - globalDiscountTotal);

        let receiptDiscount = null;
        if (globalDiscountTotal > 0) {
            receiptDiscount = {
                percent: globalDiscountPercent > 0 ? String(globalDiscountPercent) : '0',
                amount: globalDiscountTotal.toFixed(2),
            };
        }

        // Payment method label (single → method, multiple → joined breakdown).
        let paymentMethod = sale.payment_type || '';
        const splits = Array.isArray(sale.split_payments) ? sale.split_payments : [];
        if (splits.length === 1) {
            paymentMethod = splits[0].payment_type || paymentMethod;
        } else if (splits.length > 1) {
            paymentMethod = splits.map((p) => `${p.payment_type}: $${Number(p.amount).toFixed(2)}`).join(' / ');
        }

        const ticket = {
            header: {
                title: cfg.address,
                subtitle: `Phone number: ${cfg.phone}`,
            },
            items,
            subtotal: subtotal.toFixed(2),
            total: total.toFixed(2),
            tax: '0.00',
            payment_method: paymentMethod,
            ticket_number: generateTicketNumber(sale, storeId ?? sale.store_id),
            date: formatTicketDateTime(sale.date || sale.created_at || sale.finalized_at),
            footer: PRINTER_CONFIG.footer,
        };
        if (receiptDiscount) ticket.receipt_discount = receiptDiscount;
        if (giftCardCodes.length) ticket.gift_cards = giftCardCodes;
        return ticket;
    }

    /**
     * Builds a voucher ticket payload (printed after an exchange that
     * generates a store-credit voucher). V1 parity.
     */
    function formatVoucherTicketData(voucher, exchange = null, { storeId } = {}) {
        const cfg = getStoreConfig(storeId);
        // V1 cosmetic: insert a space every 3 characters in the code for readability.
        const formattedCode = String(voucher.code || '').replace(/(.{3})/g, '$1 ').trim();

        let expiresFormatted = voucher.expires_at;
        if (voucher.expires_at) {
            const d = new Date(voucher.expires_at);
            if (!isNaN(d.getTime())) {
                expiresFormatted = `${String(d.getDate()).padStart(2, '0')}/${String(d.getMonth() + 1).padStart(2, '0')}/${d.getFullYear()}`;
            }
        }

        const items = [];
        if (exchange) {
            items.push({
                label: `Exchange #${exchange.id}`,
                qty: 1, unit_price: '', discount: '0.00', amount: '',
            });
            items.push({
                label: 'Return Credit',
                qty: 1, unit_price: '', discount: '0.00',
                amount: Number(exchange.return_total || 0).toFixed(2),
            });
            if (Number(exchange.new_items_total) > 0) {
                items.push({
                    label: 'New Items',
                    qty: 1, unit_price: '', discount: '0.00',
                    amount: '-' + Number(exchange.new_items_total).toFixed(2),
                });
            }
        }

        return {
            header: {
                title: cfg.address,
                subtitle: `Phone number: ${cfg.phone}`,
            },
            items,
            voucher: {
                title: 'STORE CREDIT VOUCHER',
                code: formattedCode,
                amount: Number(voucher.amount || 0).toFixed(2),
                expires: expiresFormatted || '',
            },
            subtotal: Number(voucher.amount || 0).toFixed(2),
            total: Number(voucher.amount || 0).toFixed(2),
            tax: '0.00',
            payment_method: 'VOUCHER',
            ticket_number: voucher.code,
            date: formatTicketDateTime(),
            footer: `Present this voucher for your next purchase.\nValid until ${expiresFormatted || '—'}`,
        };
    }

    /**
     * Send a sale to the printer. Returns true on success, falls back to a
     * web-print preview if the local daemon is unreachable.
     */
    async function printSale(sale, options = {}) {
        const ticket = formatTicketData(sale, options);
        return printTicket(ticket);
    }

    async function printVoucher(voucher, exchange = null, options = {}) {
        const ticket = formatVoucherTicketData(voucher, exchange, options);
        return printTicket(ticket);
    }

    async function printTicket(ticket) {
        // Notify any host shell that wants to intercept (Capacitor/etc.)
        window.dispatchEvent(new CustomEvent('pos-v2:print-receipt', { detail: { ticket } }));

        const res = await rawPost('/print', ticket);
        if (res.status === 'ok') {
            return { ok: true };
        }
        if (res.networkError) {
            // Daemon offline → web-print fallback. Keeps reprint usable in UAT.
            webPrintFallback(ticket);
            return { ok: false, fallback: 'web', reason: 'network' };
        }
        return { ok: false, reason: res.message || 'print-error' };
    }

    function webPrintFallback(ticket) {
        const txt = renderPlainText(ticket);
        const w = window.open('', 'pos-v2-receipt', 'width=420,height=720');
        if (!w) return;
        w.document.write(`<!doctype html><html><head><title>Receipt</title>
            <style>
                body { font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 12px; white-space: pre; padding: 16px; }
                @media print { body { padding: 0; } }
            </style></head><body>${escapeHtml(txt)}
            <script>window.onload = () => { setTimeout(() => window.print(), 300); }<\/script>
            </body></html>`);
        w.document.close();
    }

    function renderPlainText(ticket) {
        const lines = [];
        lines.push(ticket.header?.title || '');
        if (ticket.header?.subtitle) lines.push(ticket.header.subtitle);
        lines.push('-'.repeat(32));
        lines.push(`Ticket  ${ticket.ticket_number}`);
        lines.push(`Date    ${ticket.date}`);
        lines.push('');
        for (const it of ticket.items || []) {
            lines.push(`${it.qty}× ${it.label}`);
            if (it.unit_price) lines.push(`   ${it.unit_price}  →  ${it.amount}`);
        }
        if (ticket.receipt_discount) {
            lines.push('-'.repeat(32));
            lines.push(`Discount  −${ticket.receipt_discount.amount}`);
        }
        lines.push('-'.repeat(32));
        if (ticket.subtotal) lines.push(`SUBTOTAL  ${ticket.subtotal}`);
        lines.push(`TOTAL     ${ticket.total}`);
        if (ticket.payment_method) lines.push(`PAID      ${ticket.payment_method}`);
        if (ticket.voucher) {
            lines.push('');
            lines.push(ticket.voucher.title || 'VOUCHER');
            lines.push(`Code: ${ticket.voucher.code}`);
            lines.push(`Amount: ${ticket.voucher.amount}`);
            lines.push(`Expires: ${ticket.voucher.expires}`);
        }
        if (ticket.gift_cards) {
            lines.push('');
            lines.push('GIFT CARDS');
            for (const gc of ticket.gift_cards) {
                lines.push(`${gc.name} ${gc.amount}`);
                lines.push(`  ${gc.code}`);
            }
        }
        if (ticket.footer) {
            lines.push('');
            lines.push(ticket.footer);
        }
        return lines.join('\n');
    }

    function escapeHtml(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    // Back-compat shim for the Phase 5 placeholder (SaleDetailView calls .print).
    function print(sale) {
        return printSale(sale);
    }

    return {
        printSale,
        printVoucher,
        printTicket,
        openCashDrawer,
        checkPrinter,
        formatTicketData,
        formatVoucherTicketData,
        print, // legacy alias used by SaleDetailView
    };
}
