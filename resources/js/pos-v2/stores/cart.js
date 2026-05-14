import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { db } from '../db/index.js';
import { generateGiftCardCode } from '../composables/useGiftCardCode.js';
import { lineTotal, saleSubtotal, saleTotal, saleDiscountAmount } from '../composables/useCartCalculations.js';

/**
 * Cart store — represents the active sale being edited.
 *
 * Compatibility with V1 sale shape:
 * {
 *   id:            'local UUID',
 *   shift_id:      number,
 *   items:         [{ ... 5 type variants }],
 *   discounts:     [{ type, value, label? }],
 *   payment_type:  string,
 *   split_payments:[{ payment_type, amount, voucher_code? }],
 *   total:         number,
 *   discount_total:number,
 *   date:          ISO,
 *   seller:        user.name,
 * }
 *
 * Item type variants:
 *   - { type: 'product',   product_id, original_id, name, price, quantity, ean, photos[] }
 *   - { type: 'gift_box',  product_id: null, original_id: id, name, price, quantity }
 *   - { type: 'gift_card', product_id: null, original_id: id, name, price, quantity: 1, generated_code }
 *   - { is_custom_service: true,  name, price, quantity: 1, custom_service_description }
 *   - { is_delivery: true,        name: 'Delivery', price, quantity: 1, delivery_address }
 *
 * Persistence:
 *   Every mutation persists the draft sale to Dexie `sales_queue` with
 *   status='draft'. Phase 4 finalises drafts into 'pending' on Pay.
 */
export const useCartStore = defineStore('cart', () => {
    const activeSale = ref(createBlankSale());
    const initialized = ref(false);

    const itemCount = computed(() => activeSale.value.items.length);
    const subtotal = computed(() => saleSubtotal(activeSale.value.items));
    const discountAmount = computed(() => saleDiscountAmount(activeSale.value));
    const total = computed(() => saleTotal(activeSale.value));

    function createBlankSale(shiftId = null, sellerName = null) {
        return {
            id: cryptoUUID(),
            shift_id: shiftId,
            items: [],
            discounts: [],
            payment_type: null,
            split_payments: [],
            total: 0,
            discount_total: 0,
            date: new Date().toISOString(),
            seller: sellerName,
            status: 'draft',
            customer_label: null,
        };
    }

    function cryptoUUID() {
        if (crypto.randomUUID) return crypto.randomUUID();
        // Fallback for very old browsers
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
            const r = Math.random() * 16 | 0;
            return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
        });
    }

    async function initForShift(shiftId, sellerName) {
        if (!shiftId) return;
        const existing = await db.table('sales_queue')
            .where(['status', 'shift_id'])
            .equals(['draft', shiftId])
            .first()
            .catch(async () => {
                // Compound index might not be available — fall back to filter
                const rows = await db.table('sales_queue').where('status').equals('draft').toArray();
                return rows.find((r) => r.shift_id === shiftId) || null;
            });
        if (existing) {
            activeSale.value = existing;
        } else {
            activeSale.value = createBlankSale(shiftId, sellerName);
        }
        initialized.value = true;
        await persist();
    }

    async function persist() {
        const sale = activeSale.value;
        sale.total = total.value;
        sale.discount_total = discountAmount.value;
        sale.updated_at = new Date().toISOString();
        await db.table('sales_queue').put({
            ...sale,
            pos_local_id: sale.id,
            status: sale.status || 'draft',
            shift_id: sale.shift_id,
            created_at: sale.created_at || new Date().toISOString(),
        });
    }

    /**
     * Add a catalog item (product, gift_box or gift_card) to the cart.
     * - product / gift_box: increment qty if same id already in cart.
     * - gift_card: always a new line with a freshly generated unique code.
     */
    async function addCatalogItem(catalogItem, quantity = 1) {
        if (!catalogItem) return;
        const type = catalogItem.type || 'product';
        if (type === 'gift_card') {
            activeSale.value.items.push({
                line_id: cryptoUUID(),
                type: 'gift_card',
                product_id: null,
                original_id: catalogItem.original_id,
                name: catalogItem.name,
                price: Number(catalogItem.price ?? catalogItem.amount ?? 0),
                quantity: 1,
                generated_code: generateGiftCardCode(),
                discounts: [],
            });
        } else if (type === 'gift_box') {
            const existing = activeSale.value.items.find(
                (it) => it.type === 'gift_box' && it.original_id === catalogItem.original_id
            );
            if (existing) {
                existing.quantity += quantity;
            } else {
                activeSale.value.items.push({
                    line_id: cryptoUUID(),
                    type: 'gift_box',
                    product_id: null,
                    original_id: catalogItem.original_id,
                    name: catalogItem.name,
                    ean: catalogItem.ean,
                    price: Number(catalogItem.price || 0),
                    quantity,
                    photos: catalogItem.photos || [],
                    discounts: [],
                });
            }
        } else {
            const existing = activeSale.value.items.find(
                (it) => it.type === 'product' && it.product_id === catalogItem.original_id
            );
            if (existing) {
                existing.quantity += quantity;
            } else {
                activeSale.value.items.push({
                    line_id: cryptoUUID(),
                    type: 'product',
                    product_id: catalogItem.original_id,
                    original_id: catalogItem.original_id,
                    name: catalogItem.name,
                    ean: catalogItem.ean,
                    price: Number(catalogItem.price || 0),
                    quantity,
                    photos: catalogItem.photos || [],
                    discounts: [],
                });
            }
        }
        await persist();
    }

    async function addCustomService(description, amount) {
        const price = Number(amount) || 0;
        activeSale.value.items.push({
            line_id: cryptoUUID(),
            type: 'service',
            is_custom_service: true,
            product_id: null,
            name: { en: 'Custom service', fr: 'Service personnalisé' },
            price,
            quantity: 1,
            custom_service_description: description || '',
            discounts: [],
        });
        await persist();
    }

    async function addDelivery(address, fee) {
        const price = Number(fee) || 0;
        activeSale.value.items.push({
            line_id: cryptoUUID(),
            type: 'delivery',
            is_delivery: true,
            product_id: null,
            name: { en: 'Delivery', fr: 'Livraison' },
            price,
            quantity: 1,
            delivery_address: address || '',
            discounts: [],
        });
        await persist();
    }

    async function updateQuantity(lineId, qty) {
        const item = findLine(lineId);
        if (!item) return;
        const q = Math.max(0, Math.floor(Number(qty) || 0));
        // gift cards stay at qty 1
        if (item.type === 'gift_card') return;
        if (q === 0) {
            return removeLine(lineId);
        }
        item.quantity = q;
        await persist();
    }

    async function incrementQuantity(lineId) {
        const item = findLine(lineId);
        if (!item || item.type === 'gift_card') return;
        item.quantity = (item.quantity || 0) + 1;
        await persist();
    }

    async function decrementQuantity(lineId) {
        const item = findLine(lineId);
        if (!item || item.type === 'gift_card') return;
        const next = (item.quantity || 0) - 1;
        if (next <= 0) return removeLine(lineId);
        item.quantity = next;
        await persist();
    }

    async function removeLine(lineId) {
        activeSale.value.items = activeSale.value.items.filter((it) => it.line_id !== lineId);
        await persist();
    }

    async function setLineDiscount(lineId, discount) {
        const item = findLine(lineId);
        if (!item) return;
        item.discounts = discount ? [discount] : [];
        await persist();
    }

    async function setGlobalDiscount(discount) {
        activeSale.value.discounts = discount ? [discount] : [];
        await persist();
    }

    async function clearCart() {
        const shiftId = activeSale.value.shift_id;
        const seller = activeSale.value.seller;
        // Remove the persisted draft and start fresh.
        if (activeSale.value.id) {
            await db.table('sales_queue').delete(activeSale.value.id).catch(() => {});
        }
        activeSale.value = createBlankSale(shiftId, seller);
        await persist();
    }

    function findLine(lineId) {
        return activeSale.value.items.find((it) => it.line_id === lineId);
    }

    function lineTotalOf(item) {
        return lineTotal(item);
    }

    return {
        activeSale,
        initialized,
        itemCount,
        subtotal,
        discountAmount,
        total,
        initForShift,
        addCatalogItem,
        addCustomService,
        addDelivery,
        updateQuantity,
        incrementQuantity,
        decrementQuantity,
        removeLine,
        setLineDiscount,
        setGlobalDiscount,
        clearCart,
        lineTotalOf,
    };
});
