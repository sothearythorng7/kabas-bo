import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { lookupSale, processExchange } from '../api/endpoints/exchange.js';
import { round2 } from '../composables/useCartCalculations.js';

/**
 * Exchange wizard state machine.
 *
 * Lifecycle:
 *   1. `start(saleId)`        → step='lookup', fetches the original sale
 *      `startWithSale(sale)`  → skip lookup (called from SaleDetailView)
 *   2. `toggleReturn(itemId)` → step='return'
 *   3. `addNewItem(catalog)`  → step='new'
 *   4. `setPayments([...])`   → step='pay'  (only when balance < 0)
 *   5. `submit()`             → POST /exchange/process → reset
 *
 * Balance semantics (V1):
 *   returnTotal     = Σ returned items unit_price × qty
 *   newItemsTotal   = Σ new items price × qty
 *   balance         = returnTotal − newItemsTotal
 *     > 0 → credit due to customer (auto voucher unless cash refund chosen)
 *     < 0 → customer owes the difference (split payment supported)
 *     = 0 → straight swap, no payment
 */
export const useExchangeStore = defineStore('exchange', () => {
    const open = ref(false);
    const step = ref('lookup'); // 'lookup' | 'return' | 'new' | 'pay' | 'done'
    const loading = ref(false);
    const error = ref('');

    const originalSale = ref(null);
    const returnedItems = ref([]); // [{ sale_item_id, quantity, unit_price, name }]
    const newItems = ref([]);      // [{ product_id, original_id, type, name, price, quantity, photos }]
    const payments = ref([]);       // [{ method, amount, voucher_code? }] when balance < 0
    const notes = ref('');

    const result = ref(null); // server response on success

    const returnTotal = computed(() => round2(
        returnedItems.value.reduce((s, it) => s + (Number(it.unit_price) || 0) * (Number(it.quantity) || 0), 0)
    ));
    const newItemsTotal = computed(() => round2(
        newItems.value.reduce((s, it) => s + (Number(it.price) || 0) * (Number(it.quantity) || 0), 0)
    ));
    const balance = computed(() => round2(returnTotal.value - newItemsTotal.value));

    /** Credit due → positive (customer gets voucher / cash back). */
    const creditDue = computed(() => balance.value > 0 ? balance.value : 0);

    /** Amount owed by customer → positive number when newItems > return. */
    const amountToPay = computed(() => balance.value < 0 ? Math.abs(balance.value) : 0);

    /** Sum of split payments entered by cashier. */
    const paymentsTotal = computed(() => round2(
        payments.value.reduce((s, p) => s + (Number(p.amount) || 0), 0)
    ));

    const canSubmit = computed(() => {
        if (returnedItems.value.length === 0) return false;
        // Cashier owes nothing → can submit immediately.
        if (amountToPay.value === 0) return true;
        // Cashier owes balance → split payments must sum exactly.
        return Math.abs(paymentsTotal.value - amountToPay.value) <= 0.01;
    });

    function reset() {
        open.value = false;
        step.value = 'lookup';
        loading.value = false;
        error.value = '';
        originalSale.value = null;
        returnedItems.value = [];
        newItems.value = [];
        payments.value = [];
        notes.value = '';
        result.value = null;
    }

    function close() {
        open.value = false;
    }

    async function start(saleId) {
        reset();
        open.value = true;
        step.value = 'lookup';
        if (saleId) {
            await runLookup(saleId);
        }
    }

    function startWithSale(sale) {
        reset();
        open.value = true;
        originalSale.value = enrichSaleForWizard(sale);
        step.value = 'return';
    }

    async function runLookup(saleId) {
        loading.value = true;
        error.value = '';
        try {
            const res = await lookupSale(saleId);
            if (!res?.success || !res.sale) {
                error.value = res?.error || 'not_found';
                originalSale.value = null;
                return false;
            }
            originalSale.value = enrichSaleForWizard(res.sale);
            step.value = 'return';
            return true;
        } catch (err) {
            console.error('[POS V2] exchange lookup failed', err);
            // 404 → not_found, 400 → too_old; api/client throws ApiError with body
            const errBody = err.body || {};
            error.value = errBody.error || (err.status === 404 ? 'not_found' : err.status === 400 ? 'too_old' : err.message || 'Lookup failed');
            originalSale.value = null;
            return false;
        } finally {
            loading.value = false;
        }
    }

    /**
     * Make sure every item has the shape the wizard needs.
     * Tolerates both the lookup-sale response and the journal sale shape.
     */
    function enrichSaleForWizard(sale) {
        const items = (sale.items || []).map((it) => ({
            ...it,
            sale_item_id: it.sale_item_id ?? it.id,
            product_id: it.product_id ?? it.original_id ?? null,
            quantity: Number(it.quantity || 0),
            unit_price: Number(it.unit_price ?? it.price ?? 0),
            product_name: it.product_name || (typeof it.name === 'object' ? (it.name.en || it.name.fr) : it.name) || '—',
            is_exchangeable: it.is_exchangeable !== false && !it.exchanged_at,
        }));
        return { ...sale, items };
    }

    function toggleReturn(saleItemId) {
        const item = originalSale.value?.items?.find((i) => i.sale_item_id === saleItemId);
        if (!item || !item.is_exchangeable) return;
        const idx = returnedItems.value.findIndex((r) => r.sale_item_id === saleItemId);
        if (idx >= 0) {
            returnedItems.value.splice(idx, 1);
        } else {
            returnedItems.value.push({
                sale_item_id: saleItemId,
                product_id: item.product_id,
                product_name: item.product_name,
                quantity: item.quantity,
                unit_price: item.unit_price,
            });
        }
    }

    function setReturnedQuantity(saleItemId, qty) {
        const r = returnedItems.value.find((i) => i.sale_item_id === saleItemId);
        if (!r) return;
        const max = originalSale.value?.items?.find((it) => it.sale_item_id === saleItemId)?.quantity || 1;
        r.quantity = Math.max(1, Math.min(Number(qty) || 1, max));
    }

    function isReturned(saleItemId) {
        return returnedItems.value.some((r) => r.sale_item_id === saleItemId);
    }

    function addNewItem(catalogItem, qty = 1) {
        if (!catalogItem) return;
        const id = catalogItem.original_id;
        const existing = newItems.value.find((i) => i.product_id === id && i.type === catalogItem.type);
        if (existing) {
            existing.quantity += qty;
            return;
        }
        newItems.value.push({
            product_id: id,
            original_id: id,
            type: catalogItem.type,
            name: catalogItem.name,
            price: Number(catalogItem.price || 0),
            quantity: qty,
            photos: catalogItem.photos || [],
            ean: catalogItem.ean || null,
        });
    }

    function removeNewItem(productId, type) {
        newItems.value = newItems.value.filter((i) => !(i.product_id === productId && i.type === type));
    }

    function setNewItemQuantity(productId, type, qty) {
        const it = newItems.value.find((i) => i.product_id === productId && i.type === type);
        if (!it) return;
        const next = Math.max(0, Math.floor(Number(qty) || 0));
        if (next === 0) return removeNewItem(productId, type);
        it.quantity = next;
    }

    function setPayments(list) {
        payments.value = Array.isArray(list) ? list : [];
    }

    async function submit({ shiftId }) {
        if (!canSubmit.value || loading.value) return null;
        loading.value = true;
        error.value = '';
        try {
            const payload = {
                original_sale_id: originalSale.value.id,
                shift_id: shiftId,
                returned_items: returnedItems.value.map((r) => ({
                    sale_item_id: r.sale_item_id,
                    quantity: r.quantity,
                })),
                new_items: newItems.value.map((n) => ({
                    product_id: n.product_id,
                    quantity: n.quantity,
                })),
                payments: payments.value.map((p) => ({
                    method: p.method,
                    amount: round2(Number(p.amount) || 0),
                    voucher_code: p.voucher_code || undefined,
                })),
                notes: notes.value || null,
            };
            const res = await processExchange(payload);
            if (!res?.success) {
                error.value = res?.error || 'Process failed';
                return null;
            }
            result.value = res;
            step.value = 'done';
            return res;
        } catch (err) {
            console.error('[POS V2] exchange process failed', err);
            error.value = err.message || 'Process failed';
            return null;
        } finally {
            loading.value = false;
        }
    }

    return {
        // state
        open,
        step,
        loading,
        error,
        originalSale,
        returnedItems,
        newItems,
        payments,
        notes,
        result,
        // computed
        returnTotal,
        newItemsTotal,
        balance,
        creditDue,
        amountToPay,
        paymentsTotal,
        canSubmit,
        // actions
        start,
        startWithSale,
        runLookup,
        toggleReturn,
        setReturnedQuantity,
        isReturned,
        addNewItem,
        removeNewItem,
        setNewItemQuantity,
        setPayments,
        submit,
        reset,
        close,
    };
});
