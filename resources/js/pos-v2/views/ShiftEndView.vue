<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useSessionStore } from '../stores/session.js';
import { useI18nStore } from '../stores/i18n.js';
import { useCashStore } from '../stores/cash.js';
import { endShift } from '../api/endpoints/shifts.js';
import NumPad from '../components/NumPad.vue';

const router = useRouter();
const session = useSessionStore();
const i18n = useI18nStore();
const cash = useCashStore();

const t = computed(() => i18n.t);

const step = ref(1); // 1: visitors | 2: counted cash | 3: verification
const visitors = ref('');
const counted = ref('');
const submitting = ref(false);
const error = ref('');

const openingCash = computed(() => Number(session.currentShift?.opening_cash ?? session.currentShift?.start_amount ?? 0));
const cashSales = computed(() => Number(cash.cashSalesFromSales || 0));
const cashIn = computed(() => Number(cash.cashIn || 0));
const cashOut = computed(() => Number(cash.cashOut || 0));

const expected = computed(() => round2(openingCash.value + cashSales.value + cashIn.value - cashOut.value));
const countedNumber = computed(() => {
    const n = parseFloat(counted.value);
    return isNaN(n) ? 0 : n;
});
const difference = computed(() => round2(countedNumber.value - expected.value));
const hasGap = computed(() => Math.abs(difference.value) > 0.01);

onMounted(async () => {
    if (!session.hasOpenShift) {
        router.replace({ name: 'dashboard' });
        return;
    }
    await cash.loadForShift(session.currentShift.id);
    // Phase 4 will populate cash.setCashSales() from the sales store.
});

function next() {
    if (step.value === 1) {
        if (visitors.value === '' || parseInt(visitors.value, 10) < 0) {
            error.value = 'Visitor count required';
            return;
        }
        error.value = '';
        step.value = 2;
    } else if (step.value === 2) {
        if (counted.value === '' || parseFloat(counted.value) < 0) {
            error.value = 'Counted amount required';
            return;
        }
        error.value = '';
        step.value = 3;
    }
}

function back() {
    if (step.value > 1) step.value -= 1;
    else router.back();
}

async function confirm() {
    if (submitting.value) return;
    submitting.value = true;
    error.value = '';
    try {
        const updated = await endShift({
            user_id: session.currentUser.id,
            end_amount: countedNumber.value,
            visitors_count: parseInt(visitors.value, 10) || 0,
            cash_difference: difference.value,
            cash_in: cashIn.value,
            cash_out: cashOut.value,
        });
        cash.reset();
        session.clearShift();
        // After end shift, log out cashier — matches V1 behaviour.
        session.signOut();
        router.replace({ name: 'login' });
    } catch (err) {
        console.error('[POS V2] endShift failed', err);
        error.value = err.message || 'Failed to end shift';
    } finally {
        submitting.value = false;
    }
}

function round2(v) {
    return Math.round(v * 100) / 100;
}
function fmt(v) {
    return `$${(Math.round(v * 100) / 100).toFixed(2)}`;
}
</script>

<template>
    <div class="flex-1 flex flex-col p-6 max-w-3xl mx-auto w-full">
        <header class="flex items-center justify-between mb-6">
            <div>
                <div class="font-serif text-3xl">{{ t('endShift') }}</div>
                <div class="text-sm text-stone-500 mt-0.5">{{ session.currentUser?.name }}</div>
            </div>
            <div class="flex items-center gap-2">
                <span
                    v-for="n in 3"
                    :key="n"
                    class="w-7 h-7 rounded-full text-xs font-semibold flex items-center justify-center"
                    :class="n === step
                        ? 'bg-stone-900 text-white'
                        : (n < step ? 'bg-emerald-100 text-emerald-700' : 'bg-stone-100 text-stone-400')"
                >{{ n }}</span>
            </div>
        </header>

        <!-- Step 1: visitors -->
        <div v-if="step === 1" class="bg-white rounded-2xl border border-stone-200 p-6 flex-1 flex flex-col">
            <label class="block text-sm font-medium text-stone-700 mb-2">{{ t('visitorsCount') }}</label>
            <div class="font-serif text-5xl tabular-nums mb-6">
                {{ visitors === '' ? '0' : visitors }}
            </div>
            <NumPad v-model:value="visitors" :allow-decimal="false" />
            <div v-if="error" class="mt-3 text-sm text-rose-600">{{ error }}</div>
            <div class="mt-auto flex gap-3 pt-6">
                <button type="button" @click="back" class="flex-1 h-12 bg-white border border-stone-200 rounded-xl font-medium text-stone-700 hover:bg-stone-50">← {{ t('back') }}</button>
                <button type="button" @click="next" class="flex-1 h-12 bg-stone-900 hover:bg-stone-800 text-white rounded-xl font-semibold">{{ t('next') }} →</button>
            </div>
        </div>

        <!-- Step 2: counted cash -->
        <div v-else-if="step === 2" class="bg-white rounded-2xl border border-stone-200 p-6 flex-1 flex flex-col">
            <label class="block text-sm font-medium text-stone-700 mb-2">{{ t('countedCash') }}</label>
            <div class="flex items-baseline gap-2 mb-6">
                <span class="text-stone-400 text-2xl">$</span>
                <div class="font-serif text-5xl tabular-nums">{{ counted === '' ? '0.00' : counted }}</div>
            </div>
            <NumPad v-model:value="counted" />
            <div v-if="error" class="mt-3 text-sm text-rose-600">{{ error }}</div>
            <div class="mt-auto flex gap-3 pt-6">
                <button type="button" @click="back" class="flex-1 h-12 bg-white border border-stone-200 rounded-xl font-medium text-stone-700 hover:bg-stone-50">← {{ t('back') }}</button>
                <button type="button" @click="next" class="flex-1 h-12 bg-stone-900 hover:bg-stone-800 text-white rounded-xl font-semibold">{{ t('next') }} →</button>
            </div>
        </div>

        <!-- Step 3: verification -->
        <div v-else class="bg-white rounded-2xl border border-stone-200 p-6 flex-1 flex flex-col">
            <h3 class="font-medium text-stone-800 mb-4">{{ t('verification') }}</h3>

            <dl class="divide-y divide-stone-100 mb-6">
                <div class="flex justify-between py-2.5 text-sm">
                    <dt class="text-stone-600">{{ t('openingCash') }}</dt>
                    <dd class="font-medium tabular-nums">{{ fmt(openingCash) }}</dd>
                </div>
                <div class="flex justify-between py-2.5 text-sm">
                    <dt class="text-stone-600">+ {{ t('cashSales') }}</dt>
                    <dd class="font-medium tabular-nums">{{ fmt(cashSales) }}</dd>
                </div>
                <div class="flex justify-between py-2.5 text-sm">
                    <dt class="text-stone-600">+ {{ t('cashIn') }}</dt>
                    <dd class="font-medium tabular-nums">{{ fmt(cashIn) }}</dd>
                </div>
                <div class="flex justify-between py-2.5 text-sm">
                    <dt class="text-stone-600">− {{ t('cashOut') }}</dt>
                    <dd class="font-medium tabular-nums">{{ fmt(cashOut) }}</dd>
                </div>
                <div class="flex justify-between py-3 text-base bg-stone-50 -mx-6 px-6">
                    <dt class="font-semibold">{{ t('expectedCash') }}</dt>
                    <dd class="font-serif text-xl tabular-nums">{{ fmt(expected) }}</dd>
                </div>
                <div class="flex justify-between py-2.5 text-sm">
                    <dt class="text-stone-600">{{ t('countedAmount') }}</dt>
                    <dd class="font-medium tabular-nums">{{ fmt(countedNumber) }}</dd>
                </div>
                <div
                    class="flex justify-between py-3 text-base"
                    :class="hasGap ? (difference > 0 ? 'text-amber-700 bg-amber-50 -mx-6 px-6' : 'text-rose-700 bg-rose-50 -mx-6 px-6') : 'text-emerald-700'"
                >
                    <dt class="font-semibold">{{ t('difference') }}</dt>
                    <dd class="font-serif text-xl tabular-nums">
                        {{ difference >= 0 ? '+' : '' }}{{ fmt(difference) }}
                    </dd>
                </div>
            </dl>

            <div v-if="hasGap" class="mb-4 p-3 rounded-xl bg-amber-50 text-amber-900 text-sm border border-amber-200">
                <strong>⚠</strong> Cash gap detected ({{ fmt(Math.abs(difference)) }}). Confirm to record anyway, or back to adjust the counted amount.
            </div>

            <div v-if="error" class="mb-3 text-sm text-rose-600">{{ error }}</div>

            <div class="mt-auto flex gap-3">
                <button type="button" @click="back" :disabled="submitting" class="flex-1 h-12 bg-white border border-stone-200 rounded-xl font-medium text-stone-700 hover:bg-stone-50 disabled:opacity-50">← {{ t('back') }}</button>
                <button type="button" @click="confirm" :disabled="submitting" class="flex-1 h-12 bg-rose-600 hover:bg-rose-700 text-white rounded-xl font-semibold disabled:opacity-50">
                    {{ submitting ? t('loading') : t('confirm') }}
                </button>
            </div>
        </div>

    </div>
</template>
