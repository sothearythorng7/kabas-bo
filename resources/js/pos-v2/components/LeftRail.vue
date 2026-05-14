<script setup>
import { useRouter } from 'vue-router';
import { useI18nStore } from '../stores/i18n.js';
import { computed } from 'vue';

const router = useRouter();
const i18n = useI18nStore();
const t = computed(() => i18n.t);

const emit = defineEmits(['cashIn', 'cashOut']);

function goEndShift() {
    router.push({ name: 'shift-end' });
}
</script>

<template>
    <aside class="w-16 bg-white border-r border-stone-200/80 flex flex-col items-center py-4 gap-1">
        <button class="w-11 h-11 rounded-xl bg-stone-900 text-white flex items-center justify-center" :title="'Sale'">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-8 2a2 2 0 1 1-4 0 2 2 0 0 1 4 0z"/></svg>
        </button>

        <button class="w-11 h-11 rounded-xl text-stone-300 cursor-not-allowed flex items-center justify-center" :title="'Journal — Phase 5'" disabled>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        </button>

        <button class="w-11 h-11 rounded-xl text-stone-300 cursor-not-allowed flex items-center justify-center" :title="'Planning — Phase 7'" disabled>
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </button>

        <button
            type="button"
            @click="emit('cashIn')"
            class="w-11 h-11 rounded-xl text-stone-500 hover:bg-stone-100 flex items-center justify-center"
            :title="t('addCashIn')"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M12 6V3M12 21v-3"/></svg>
        </button>

        <button
            type="button"
            @click="emit('cashOut')"
            class="w-11 h-11 rounded-xl text-stone-500 hover:bg-stone-100 flex items-center justify-center"
            :title="t('addCashOut')"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><line x1="6" y1="12" x2="2" y2="12"/><line x1="22" y1="12" x2="18" y2="12"/></svg>
        </button>

        <div class="mt-auto">
            <button
                type="button"
                @click="goEndShift"
                class="w-11 h-11 rounded-xl text-rose-500 hover:bg-rose-50 flex items-center justify-center"
                :title="t('endShift')"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            </button>
        </div>
    </aside>
</template>
