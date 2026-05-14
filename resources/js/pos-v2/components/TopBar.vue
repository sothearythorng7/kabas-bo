<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useSessionStore } from '../stores/session.js';
import { useI18nStore } from '../stores/i18n.js';
import { useSalesStore } from '../stores/sales.js';

const session = useSessionStore();
const i18n = useI18nStore();
const sales = useSalesStore();

const t = computed(() => i18n.t);
const now = ref(Date.now());
let timer = null;

onMounted(() => {
    timer = setInterval(() => { now.value = Date.now(); }, 30_000);
});
onBeforeUnmount(() => {
    if (timer) clearInterval(timer);
});

const shiftDuration = computed(() => {
    const startedAt = session.currentShift?.started_at;
    if (!startedAt) return '—';
    const elapsed = (now.value - new Date(startedAt).getTime()) / 1000;
    const h = Math.floor(elapsed / 3600);
    const m = Math.floor((elapsed % 3600) / 60);
    return `${h}h ${String(m).padStart(2, '0')}m`;
});

function toggleLocale() {
    i18n.setLocale(i18n.locale === 'fr' ? 'en' : 'fr');
}

const emit = defineEmits(['switchCashier', 'forceSync']);
</script>

<template>
    <header class="bg-white border-b border-stone-200/80 sticky top-0 z-30">
        <div class="flex items-center justify-between h-14 px-4">
            <!-- Left: brand + store -->
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-stone-900 rounded-xl flex items-center justify-center text-white font-serif text-lg">K</div>
                <div class="leading-tight">
                    <div class="text-[15px] font-semibold tracking-tight">Kabas POS</div>
                    <div class="text-[11px] text-stone-500">v2 · Store {{ session.currentUser?.store_id }}</div>
                </div>
            </div>

            <!-- Center: status -->
            <div class="flex items-center gap-6 text-[13px]">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-stone-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span class="text-stone-600">Shift</span>
                    <span class="font-semibold tabular-nums">{{ shiftDuration }}</span>
                </div>
                <div class="h-4 w-px bg-stone-200"></div>
                <div class="flex items-center gap-2">
                    <span class="inline-block w-2 h-2 rounded-full pulse-dot" :class="session.isOnline ? 'bg-emerald-500' : 'bg-stone-400'"></span>
                    <span class="text-stone-600">{{ session.isOnline ? 'Online' : 'Offline' }}</span>
                </div>
                <div class="h-4 w-px bg-stone-200"></div>
                <button type="button" @click="emit('forceSync')" :disabled="sales.syncing" class="flex items-center gap-1.5 text-stone-600 hover:text-stone-900 disabled:opacity-60">
                    <svg class="w-4 h-4" :class="{ 'animate-spin': sales.syncing }" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><polyline points="21 3 21 8 16 8"/></svg>
                    <span>{{ sales.syncing ? t('loading') : t('forceSync') }}</span>
                    <span v-if="sales.pendingCount > 0" class="bg-amber-100 text-amber-800 text-[10px] font-semibold px-1.5 py-0.5 rounded-full tabular-nums">
                        {{ sales.pendingCount }}
                    </span>
                </button>
            </div>

            <!-- Right: cashier + locale -->
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    @click="emit('switchCashier')"
                    class="flex items-center gap-2 px-3 py-1.5 bg-stone-100 hover:bg-stone-200 rounded-full transition-colors"
                >
                    <div class="w-6 h-6 bg-gradient-to-br from-orange-300 to-rose-400 rounded-full flex items-center justify-center text-white text-[11px] font-semibold">
                        {{ (session.currentUser?.name || '?').charAt(0).toUpperCase() }}
                    </div>
                    <span class="text-[13px] font-medium">{{ session.currentUser?.name }}</span>
                    <svg class="w-3.5 h-3.5 text-stone-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><polyline points="17 11 19 13 23 9"/></svg>
                </button>
                <button
                    type="button"
                    @click="toggleLocale"
                    class="flex items-center text-[12px] font-medium bg-stone-100 rounded-full p-0.5"
                >
                    <span class="px-2.5 py-1 rounded-full" :class="i18n.locale === 'en' ? 'bg-white shadow-sm' : 'text-stone-500'">EN</span>
                    <span class="px-2.5 py-1 rounded-full" :class="i18n.locale === 'fr' ? 'bg-white shadow-sm' : 'text-stone-500'">FR</span>
                </button>
            </div>
        </div>
    </header>
</template>
