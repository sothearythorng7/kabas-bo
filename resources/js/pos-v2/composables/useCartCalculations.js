/**
 * Pure helpers for cart totals.
 * Mirrors V1 calculation semantics exactly:
 *
 *   line_subtotal = price * qty
 *   line_total    = line_subtotal − sum(line discounts)
 *   sale_subtotal = sum(line_total) + delivery + custom services
 *   sale_total    = sale_subtotal − sum(global discounts)
 *
 * Discount type:
 *   { type: 'amount', value: 5 }   → fixed $5 off
 *   { type: 'percent', value: 10 } → 10% off
 */

export function applyDiscounts(base, discounts) {
    if (!discounts || !discounts.length) return base;
    let result = base;
    for (const d of discounts) {
        if (!d || d.value == null) continue;
        const val = Number(d.value) || 0;
        if (d.type === 'percent') {
            result -= result * (val / 100);
        } else {
            result -= val;
        }
    }
    return Math.max(0, result);
}

export function lineSubtotal(item) {
    return Number(item.price || 0) * Number(item.quantity || 0);
}

export function lineTotal(item) {
    return round2(applyDiscounts(lineSubtotal(item), item.discounts));
}

export function saleSubtotal(items) {
    let sum = 0;
    for (const it of items) sum += lineTotal(it);
    return round2(sum);
}

export function saleTotal(sale) {
    const subtotal = saleSubtotal(sale.items || []);
    return round2(applyDiscounts(subtotal, sale.discounts));
}

export function saleDiscountAmount(sale) {
    const subtotal = saleSubtotal(sale.items || []);
    const total = saleTotal(sale);
    return round2(subtotal - total);
}

export function round2(v) {
    return Math.round((Number(v) || 0) * 100) / 100;
}
