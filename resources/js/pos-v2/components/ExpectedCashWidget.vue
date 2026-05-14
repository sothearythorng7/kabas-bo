<script setup>
import { computed } from 'vue';
import { useSessionStore } from '../stores/session.js';
import { useCashStore } from '../stores/cash.js';
import { useI18nStore } from '../stores/i18n.js';

const session = useSessionStore();
const cash = useCashStore();
const i18n = useI18nStore();

const t = computed(() => i18n.t);

const openingCash = computed(() => Number(session.currentShift?.opening_cash ?? session.currentShift?.start_amount ?? 0));
const expected = computed(() => {
    const total = openingCash.value + Number(cash.cashSalesFromSales || 0) + Number(cash.cashIn || 0) - Number(cash.cashOut || 0);
    return Math.round(total * 100) / 100;
});

function fmt(v) {
    return `$${(Math.round(v * 100) / 100).toFixed(2)}`;
}
</script>

<template>
    <div v-if="session.hasOpenShift" class="text-[11px] text-stone-500 flex items-center gap-1.5">
        <span>{{ t('expectedCash') }}</span>
        <span class="font-semibold text-stone-900 tabular-nums">{{ fmt(expected) }}</span>
    </div>
</template>
