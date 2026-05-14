/**
 * Receipt printer wrapper — stub until Phase 8 wires the real device.
 *
 * V1 has `formatTicketData(sale)` + `printReceipt(data)` that talk to the
 * thermal printer through whatever bridge is used in production
 * (likely Web Bluetooth / Web Serial / a tablet shell). Phase 8 will
 * audit that integration and plug it back in here.
 *
 * For now we emit a structured event the host page can listen to, and
 * fall back to a print-preview window using window.print(). This keeps
 * the Reprint button functional during UAT even before the device layer
 * lands.
 */
export function useReceiptPrinter() {
    function buildText(sale) {
        const lines = [];
        lines.push('KABAS CONCEPT STORE');
        lines.push('-'.repeat(32));
        lines.push(`Sale #${sale.id ?? sale.pos_local_id ?? ''}`);
        lines.push(new Date(sale.created_at || sale.finalized_at || sale.date || Date.now()).toLocaleString());
        if (sale.seller) lines.push(`Seller: ${sale.seller}`);
        lines.push('');
        for (const it of sale.items || []) {
            const name = typeof it.name === 'string'
                ? it.name
                : (it.name?.en || it.name?.fr || it.product_name || '');
            lines.push(`${(it.quantity || 1)}× ${name}`);
            lines.push(`   ${money(it.price)} ea`);
        }
        lines.push('-'.repeat(32));
        lines.push(`TOTAL  ${money(sale.total)}`);
        if (sale.payment_type) lines.push(`PAID   ${sale.payment_type}`);
        return lines.join('\n');
    }

    function money(v) {
        return `$${(Math.round((Number(v) || 0) * 100) / 100).toFixed(2)}`;
    }

    function print(sale) {
        const text = buildText(sale);

        // Notify any host shell (Capacitor / web wrapper) that wants to
        // hand the payload to the real printer driver.
        window.dispatchEvent(new CustomEvent('pos-v2:print-receipt', {
            detail: { sale, text },
        }));

        // Web preview fallback — opens a print dialog with monospace receipt.
        const w = window.open('', 'pos-v2-receipt', 'width=420,height=720');
        if (!w) return { ok: false, reason: 'popup-blocked' };
        w.document.write(`<!doctype html><html><head><title>Receipt</title>
            <style>
                body { font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 12px; white-space: pre; padding: 16px; }
                @media print { body { padding: 0; } }
            </style></head><body>${escapeHtml(text)}
            <script>window.onload = () => { setTimeout(() => window.print(), 300); }<\/script>
            </body></html>`);
        w.document.close();
        return { ok: true };
    }

    function escapeHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    return { print, buildText };
}
