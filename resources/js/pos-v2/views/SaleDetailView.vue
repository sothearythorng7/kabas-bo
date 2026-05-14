<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18nStore } from '../stores/i18n.js';
import { useJournalStore } from '../stores/journal.js';
import { useExchangeStore } from '../stores/exchange.js';
import { useReceiptPrinter } from '../composables/useReceiptPrinter.js';
import ExchangeWizard from '../components/exchange/ExchangeWizard.vue';

const route = useRoute();
const router = useRouter();
const i18n = useI18nStore();
const journal = useJournalStore();
const exchange = useExchangeStore();
const printer = useReceiptPrinter();

const t = computed(() => i18n.t);
const locale = computed(() => i18n.locale);

const printing = ref(false);

const sale = computed(() => journal.getSale(route.params.id));

function fmt(v) {
    return `$${(Math.round((v || 0) * 100) / 100).toFixed(2)}`;
}

function fmtDateTime(v) {
    if (!v) return '—';
    try { return new Date(v).toLocaleString(); } catch { return v; }
}

function itemName(item) {
    if (!item) return '';
    if (item.is_delivery) return 'Delivery';
    if (item.is_custom_service) return item.custom_service_description || 'Custom service';
    const n = item.name || item.product_name;
    if (!n) return '';
    if (typeof n === 'string') return n;
    return n[locale.value] || n.en || n.fr || '';
}

function itemSubtotal(item) {
    return Number(item.price || 0) * Number(item.quantity || 0);
}

function itemLineDiscount(item) {
    const ds = Array.isArray(item.discounts) ? item.discounts : [];
    return ds.reduce((s, d) => {
        if (!d) return s;
        if (d.type === 'percent') return s + (itemSubtotal(item) * Number(d.value) / 100);
        return s + Number(d.value || 0);
    }, 0);
}

function lineFinal(item) {
    return Math.max(0, itemSubtotal(item) - itemLineDiscount(item));
}

const splitPayments = computed(() => {
    if (!sale.value) return [];
    return Array.isArray(sale.value.split_payments) ? sale.value.split_payments : [];
});

const totalsBeforeDiscount = computed(() => {
    if (!sale.value) return 0;
    return (sale.value.items || []).reduce((s, it) => s + itemSubtotal(it), 0);
});

const totalDiscount = computed(() => {
    if (!sale.value) return 0;
    return Number(sale.value.discount_total || sale.value.total_discount || (totalsBeforeDiscount.value - Number(sale.value.total || 0)));
});

const hasExchanges = computed(() => Array.isArray(sale.value?.exchanges) && sale.value.exchanges.length > 0);

function reprint() {
    if (!sale.value) return;
    printing.value = true;
    setTimeout(() => { printing.value = false; }, 1200);
    printer.print(sale.value);
}

function startExchange() {
    if (!sale.value) return;
    exchange.startWithSale(sale.value);
}

function back() {
    router.push({ name: 'journal' });
}

onMounted(() => {
    // If user reloads SaleDetail directly, send them back to the journal:
    // the sale state lives only in memory after a date search.
    if (!sale.value) {
        router.replace({ name: 'journal' });
    }
});
</script>

<template>
    <div v-if="sale" class="flex-1 flex flex-col p-6 max-w-4xl mx-auto w-full overflow-y-auto">
        <header class="flex items-center justify-between mb-6">
            <div>
                <div class="font-serif text-3xl">{{ t('saleNo') }}{{ sale.id }}</div>
                <div class="text-sm text-stone-500 mt-0.5">{{ fmtDateTime(sale.created_at || sale.date) }} · {{ sale.seller || sale.user?.name || '—' }}</div>
            </div>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    @click="reprint"
                    :disabled="printing"
                    class="h-10 px-4 bg-white border border-stone-200 rounded-xl font-medium text-stone-700 hover:bg-stone-50 disabled:opacity-50 flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    {{ printing ? '…' : t('reprint') }}
                </button>
                <button
                    type="button"
                    @click="startExchange"
                    class="h-10 px-4 bg-white border border-stone-200 rounded-xl font-medium text-stone-700 hover:bg-stone-50 flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                    {{ t('exchange') }}
                </button>
                <button type="button" @click="back" class="h-10 px-4 text-sm text-stone-500 hover:text-stone-900">← {{ t('back') }}</button>
            </div>
        </header>

        <!-- Items -->
        <section class="bg-white border border-stone-200 rounded-2xl overflow-hidden mb-6">
            <h2 class="px-5 py-3 border-b border-stone-100 text-sm font-medium text-stone-700">{{ t('items') }}</h2>
            <table class="w-full text-sm">
                <thead class="bg-stone-50 text-xs text-stone-500">
                    <tr>
                        <th class="text-left font-medium px-4 py-2">Item</th>
                        <th class="text-right font-medium px-4 py-2">Qty</th>
                        <th class="text-right font-medium px-4 py-2">Price</th>
                        <th class="text-right font-medium px-4 py-2">Discount</th>
                        <th class="text-right font-medium px-4 py-2">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="item in sale.items" :key="item.id || item.line_id" class="border-t border-stone-100">
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ itemName(item) }}</div>
                            <div v-if="item.ean" class="text-[11px] text-stone-500">EAN {{ item.ean }}</div>
                            <div v-if="item.generated_code" class="text-[11px] font-mono text-stone-500 tracking-wider">{{ item.generated_code }}</div>
                            <div v-if="item.delivery_address" class="text-[11px] text-sky-700">→ {{ item.delivery_address }}</div>
                            <span v-if="item.is_custom_service" class="inline-block mt-1 text-[9px] uppercase tracking-wider font-semibold text-amber-800 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded">Service</span>
                            <span v-else-if="item.is_delivery" class="inline-block mt-1 text-[9px] uppercase tracking-wider font-semibold text-sky-800 bg-sky-50 border border-sky-200 px-1.5 py-0.5 rounded">Delivery</span>
                            <span v-else-if="item.type === 'gift_card'" class="inline-block mt-1 text-[9px] uppercase tracking-wider font-semibold text-stone-800 bg-stone-100 px-1.5 py-0.5 rounded">Gift card</span>
                            <span v-else-if="item.type === 'gift_box'" class="inline-block mt-1 text-[9px] uppercase tracking-wider font-semibold text-amber-800 bg-amber-50 px-1.5 py-0.5 rounded">Gift box</span>
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ item.quantity }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ fmt(item.price) }}</td>
                        <td class="px-4 py-3 text-right tabular-nums text-amber-700">
                            {{ itemLineDiscount(item) > 0 ? '−' + fmt(itemLineDiscount(item)) : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums font-semibold">{{ fmt(lineFinal(item)) }}</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <!-- Totals + payment -->
        <div class="grid grid-cols-2 gap-6 mb-6">
            <section class="bg-white border border-stone-200 rounded-2xl p-5">
                <h3 class="text-sm font-medium text-stone-700 mb-3">Totals</h3>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-stone-600">{{ t('beforeDiscount') }}</dt>
                        <dd class="tabular-nums">{{ fmt(totalsBeforeDiscount) }}</dd>
                    </div>
                    <div class="flex justify-between text-amber-700">
                        <dt>{{ t('totalDiscount') }}</dt>
                        <dd class="tabular-nums">−{{ fmt(totalDiscount) }}</dd>
                    </div>
                    <div class="flex justify-between pt-2 border-t border-stone-100">
                        <dt class="font-medium">Total</dt>
                        <dd class="font-serif text-xl tabular-nums">{{ fmt(sale.total) }}</dd>
                    </div>
                </dl>
            </section>

            <section class="bg-white border border-stone-200 rounded-2xl p-5">
                <h3 class="text-sm font-medium text-stone-700 mb-3">{{ t('payment') }}</h3>
                <ul class="space-y-2 text-sm">
                    <li v-for="(sp, i) in splitPayments" :key="i" class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-semibold uppercase tracking-wider bg-stone-100 text-stone-700 px-2 py-0.5 rounded-full">
                                {{ String(sp.payment_type || sp.method || '—').toUpperCase() }}
                            </span>
                            <span v-if="sp.voucher_code" class="text-[10px] font-mono text-stone-500">{{ sp.voucher_code }}</span>
                        </div>
                        <span class="tabular-nums font-medium">{{ fmt(sp.amount) }}</span>
                    </li>
                    <li v-if="splitPayments.length === 0" class="text-stone-400 italic">{{ String(sale.payment_type || '—').toUpperCase() }} {{ fmt(sale.total) }}</li>
                </ul>
            </section>
        </div>

        <!-- Exchanges history -->
        <section v-if="hasExchanges" class="bg-amber-50 border border-amber-200 rounded-2xl p-5 mb-6">
            <h3 class="text-sm font-medium text-amber-900 mb-3">{{ t('exchanges') }}</h3>
            <ul class="space-y-3 text-sm">
                <li v-for="(ex, i) in sale.exchanges" :key="ex.id || i" class="bg-white border border-amber-200 rounded-xl p-3">
                    <div class="flex justify-between items-baseline">
                        <div>
                            <div class="text-xs text-stone-500">{{ fmtDateTime(ex.created_at) }}</div>
                            <div class="font-medium text-stone-900">{{ t('balance') }}: <span class="tabular-nums">{{ ex.balance >= 0 ? '+' : '' }}{{ fmt(ex.balance) }}</span></div>
                        </div>
                    </div>
                </li>
            </ul>
        </section>
    </div>

    <div v-else class="flex-1 flex items-center justify-center p-6 text-stone-400 text-sm">
        Loading sale…
    </div>

    <ExchangeWizard />
</template>
