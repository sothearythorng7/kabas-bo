<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useSessionStore } from '../stores/session.js';
import { useI18nStore } from '../stores/i18n.js';
import { useJournalStore } from '../stores/journal.js';

const router = useRouter();
const session = useSessionStore();
const i18n = useI18nStore();
const journal = useJournalStore();

const t = computed(() => i18n.t);

const date = ref(formatToday());

function formatToday() {
    const d = new Date();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${d.getFullYear()}-${m}-${day}`;
}

const summary = computed(() => {
    const total = journal.sales.reduce((s, sale) => s + Number(sale.total || 0), 0);
    const items = journal.sales.reduce((s, sale) => s + (sale.items || []).reduce((c, it) => c + (Number(it.quantity) || 0), 0), 0);
    const discounts = journal.sales.reduce((s, sale) => s + Number(sale.discount_total || sale.total_discount || 0), 0);
    return {
        total,
        items,
        discounts,
        count: journal.sales.length,
    };
});

async function search() {
    await journal.search({
        date: date.value,
        user_id: null,
        store_id: session.currentUser?.store_id || null,
    });
}

function fmt(v) {
    return `$${(Math.round((v || 0) * 100) / 100).toFixed(2)}`;
}

function fmtDateTime(v) {
    if (!v) return '—';
    try { return new Date(v).toLocaleString(); } catch { return v; }
}

function openSale(sale) {
    router.push({ name: 'sale-detail', params: { id: String(sale.id) } });
}

function itemCount(sale) {
    return (sale.items || []).reduce((s, it) => s + (Number(it.quantity) || 0), 0);
}

function paymentBadge(sale) {
    const splits = Array.isArray(sale.split_payments) ? sale.split_payments : [];
    if (splits.length > 1) return 'SPLIT';
    return String(sale.payment_type || splits[0]?.payment_type || '—').toUpperCase();
}

function back() {
    router.push({ name: 'dashboard' });
}

onMounted(() => {
    if (!journal.searchDate) search();
});
</script>

<template>
    <div class="flex-1 flex flex-col p-6 max-w-6xl mx-auto w-full overflow-y-auto">
        <header class="flex items-center justify-between mb-6">
            <div>
                <div class="font-serif text-3xl">{{ t('journal') }}</div>
                <div class="text-sm text-stone-500 mt-0.5">{{ t('searchSales') }}</div>
            </div>
            <button type="button" @click="back" class="text-sm text-stone-500 hover:text-stone-900">← {{ t('back') }}</button>
        </header>

        <!-- Search bar -->
        <div class="flex items-end gap-3 mb-6">
            <div class="flex-1 max-w-xs">
                <label class="block text-xs font-medium text-stone-700 mb-1">{{ t('date') }}</label>
                <input
                    v-model="date"
                    type="date"
                    class="w-full bg-white border border-stone-200 rounded-xl px-4 h-11 text-[15px] focus:border-stone-400 focus:outline-none focus:ring-4 focus:ring-stone-200/50"
                >
            </div>
            <button
                @click="search"
                :disabled="journal.searching"
                class="h-11 px-5 bg-stone-900 hover:bg-stone-800 text-white rounded-xl font-medium disabled:opacity-50"
            >
                {{ journal.searching ? t('loading') : t('search') }}
            </button>
        </div>

        <!-- Summary cards -->
        <div v-if="journal.sales.length > 0" class="grid grid-cols-4 gap-3 mb-6">
            <div class="bg-white border border-stone-200 rounded-2xl p-4">
                <div class="text-xs text-stone-500">{{ t('salesCount') }}</div>
                <div class="font-serif text-3xl mt-1 tabular-nums">{{ summary.count }}</div>
            </div>
            <div class="bg-white border border-stone-200 rounded-2xl p-4">
                <div class="text-xs text-stone-500">{{ t('itemsSold') }}</div>
                <div class="font-serif text-3xl mt-1 tabular-nums">{{ summary.items }}</div>
            </div>
            <div class="bg-white border border-stone-200 rounded-2xl p-4">
                <div class="text-xs text-stone-500">{{ t('totalPaidLabel') }}</div>
                <div class="font-serif text-3xl mt-1 tabular-nums">{{ fmt(summary.total) }}</div>
            </div>
            <div class="bg-white border border-stone-200 rounded-2xl p-4">
                <div class="text-xs text-stone-500">{{ t('totalDiscount') }}</div>
                <div class="font-serif text-3xl mt-1 tabular-nums text-amber-700">{{ fmt(summary.discounts) }}</div>
            </div>
        </div>

        <!-- Shift info -->
        <div v-if="journal.shift" class="bg-stone-50 border border-stone-200 rounded-xl p-4 mb-6 text-sm">
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <div class="text-xs text-stone-500">Shift</div>
                    <div class="font-medium">#{{ journal.shift.id }}</div>
                </div>
                <div>
                    <div class="text-xs text-stone-500">Started</div>
                    <div class="font-medium">{{ fmtDateTime(journal.shift.started_at) }}</div>
                </div>
                <div v-if="journal.shift.ended_at">
                    <div class="text-xs text-stone-500">Ended</div>
                    <div class="font-medium">{{ fmtDateTime(journal.shift.ended_at) }}</div>
                </div>
            </div>
        </div>

        <!-- Error -->
        <div v-if="journal.error" class="bg-rose-50 border border-rose-200 rounded-xl p-3 mb-4 text-sm text-rose-700">{{ journal.error }}</div>

        <!-- Sales table -->
        <div class="bg-white border border-stone-200 rounded-2xl overflow-hidden">
            <table v-if="journal.sales.length > 0" class="w-full text-sm">
                <thead class="bg-stone-50 text-stone-500 text-xs">
                    <tr>
                        <th class="text-left font-medium px-4 py-3">Time</th>
                        <th class="text-left font-medium px-4 py-3">{{ t('seller') }}</th>
                        <th class="text-right font-medium px-4 py-3">{{ t('items') }}</th>
                        <th class="text-right font-medium px-4 py-3">{{ t('beforeDiscount') }}</th>
                        <th class="text-right font-medium px-4 py-3">Total</th>
                        <th class="text-left font-medium px-4 py-3">{{ t('payment') }}</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="sale in journal.sales"
                        :key="sale.id"
                        @click="openSale(sale)"
                        class="border-t border-stone-100 cursor-pointer hover:bg-stone-50"
                    >
                        <td class="px-4 py-3 tabular-nums text-stone-700">{{ fmtDateTime(sale.created_at || sale.date) }}</td>
                        <td class="px-4 py-3">{{ sale.seller || sale.user?.name || '—' }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ itemCount(sale) }}</td>
                        <td class="px-4 py-3 text-right tabular-nums text-stone-500">{{ fmt(Number(sale.total || 0) + Number(sale.discount_total || sale.total_discount || 0)) }}</td>
                        <td class="px-4 py-3 text-right tabular-nums font-semibold">{{ fmt(sale.total) }}</td>
                        <td class="px-4 py-3">
                            <span class="text-[10px] font-semibold uppercase tracking-wider bg-stone-100 text-stone-700 px-2 py-0.5 rounded-full">{{ paymentBadge(sale) }}</span>
                        </td>
                        <td class="px-4 py-3 text-right text-stone-300">
                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div v-else-if="!journal.searching" class="px-6 py-12 text-center text-stone-400">
                <svg class="w-10 h-10 mx-auto mb-2 text-stone-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <p class="text-sm">{{ t('noResults') }}</p>
            </div>
        </div>
    </div>
</template>
