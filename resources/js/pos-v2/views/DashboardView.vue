<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useSessionStore } from '../stores/session.js';
import { useCashStore } from '../stores/cash.js';
import { useI18nStore } from '../stores/i18n.js';
import CashInOutDialog from '../components/CashInOutDialog.vue';
import ChangeUserDialog from '../components/ChangeUserDialog.vue';
import ExpectedCashWidget from '../components/ExpectedCashWidget.vue';

const router = useRouter();
const session = useSessionStore();
const cash = useCashStore();
const i18n = useI18nStore();

const cashDialogOpen = ref(false);
const cashDirection = ref('in');
const switchDialogOpen = ref(false);

const t = computed(() => i18n.t);

onMounted(async () => {
    if (session.currentShift?.id) {
        await cash.loadForShift(session.currentShift.id);
    }
});

function openCashIn() {
    cashDirection.value = 'in';
    cashDialogOpen.value = true;
}
function openCashOut() {
    cashDirection.value = 'out';
    cashDialogOpen.value = true;
}
function goEndShift() {
    router.push({ name: 'shift-end' });
}
function fmt(v) {
    return `$${(Math.round((v || 0) * 100) / 100).toFixed(2)}`;
}
</script>

<template>
    <div class="flex-1 flex flex-col">
        <!-- Top bar (placeholder until Phase 3 dashboard) -->
        <header class="bg-white border-b border-stone-200 px-4 h-14 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-stone-900 rounded-xl flex items-center justify-center text-white font-serif">K</div>
                <div class="leading-tight">
                    <div class="text-sm font-semibold">Kabas POS V2</div>
                    <div class="text-[11px] text-stone-500">Shift #{{ session.currentShift?.id }} · {{ session.currentUser?.name }}</div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <ExpectedCashWidget />
                <button @click="switchDialogOpen = true" class="text-xs px-3 py-1.5 bg-stone-100 hover:bg-stone-200 rounded-full font-medium">{{ t('switchCashier') }}</button>
                <button @click="goEndShift" class="text-xs px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-full font-medium">{{ t('endShift') }}</button>
            </div>
        </header>

        <!-- Body (placeholder — Phase 3 mounts the catalog + cart) -->
        <main class="flex-1 flex items-center justify-center p-6">
            <div class="text-center max-w-md">
                <div class="font-serif text-4xl mb-3">Dashboard</div>
                <p class="text-stone-600 text-sm mb-6">
                    Phase 3 will mount the catalog and cart shown in the
                    <a class="underline" href="/pos-v2-dashboard-mockup.html" target="_blank">approved mockup</a>.
                    For now, you can already test Phase 2 flows below.
                </p>

                <div class="grid grid-cols-2 gap-3">
                    <button @click="openCashIn" class="p-4 bg-white border border-stone-200 rounded-xl hover:bg-stone-50">
                        <div class="text-xs text-stone-500">Cash in</div>
                        <div class="font-semibold text-emerald-700 mt-1 tabular-nums">{{ fmt(cash.cashIn) }}</div>
                    </button>
                    <button @click="openCashOut" class="p-4 bg-white border border-stone-200 rounded-xl hover:bg-stone-50">
                        <div class="text-xs text-stone-500">Cash out</div>
                        <div class="font-semibold text-rose-700 mt-1 tabular-nums">{{ fmt(cash.cashOut) }}</div>
                    </button>
                </div>
            </div>
        </main>

        <CashInOutDialog
            :open="cashDialogOpen"
            :direction="cashDirection"
            @close="cashDialogOpen = false"
        />
        <ChangeUserDialog
            :open="switchDialogOpen"
            @close="switchDialogOpen = false"
        />
    </div>
</template>
