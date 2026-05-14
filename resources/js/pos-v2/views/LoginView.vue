<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useSessionStore } from '../stores/session.js';
import { useI18nStore } from '../stores/i18n.js';
import { useCashStore } from '../stores/cash.js';
import PinPad from '../components/PinPad.vue';

const router = useRouter();
const session = useSessionStore();
const i18n = useI18nStore();
const cash = useCashStore();

const pin = ref('');
const status = ref('idle'); // idle | signing | error
const errorMessage = ref('');

const t = computed(() => i18n.t);

onMounted(async () => {
    if (!session.usersLoaded) {
        await session.loadUsers();
    }
});

async function submit(value) {
    if (status.value === 'signing') return;
    status.value = 'signing';
    errorMessage.value = '';

    const { ok, user } = await session.signInWithPin(value);
    if (!ok) {
        status.value = 'error';
        errorMessage.value = t.value('invalidPin');
        setTimeout(() => {
            pin.value = '';
            status.value = 'idle';
        }, 700);
        return;
    }

    const shift = await session.checkShift(user.id);
    if (shift) {
        await cash.loadForShift(shift.id);
        router.push({ name: 'dashboard' });
    } else {
        router.push({ name: 'shift-start' });
    }
}

function toggleLocale() {
    i18n.setLocale(i18n.locale === 'fr' ? 'en' : 'fr');
}
</script>

<template>
    <div class="flex-1 flex items-center justify-center p-6 relative">
        <button
            type="button"
            @click="toggleLocale"
            class="absolute top-6 right-6 flex items-center text-xs font-medium bg-white rounded-full p-0.5 border border-stone-200 shadow-sm"
        >
            <span class="px-3 py-1 rounded-full" :class="i18n.locale === 'en' ? 'bg-stone-900 text-white' : 'text-stone-500'">EN</span>
            <span class="px-3 py-1 rounded-full" :class="i18n.locale === 'fr' ? 'bg-stone-900 text-white' : 'text-stone-500'">FR</span>
        </button>

        <div class="w-full max-w-md bg-white rounded-3xl shadow-sm border border-stone-200 p-8">
            <div class="flex items-center gap-3 mb-8">
                <div class="w-12 h-12 bg-stone-900 rounded-2xl flex items-center justify-center text-white text-2xl font-serif">K</div>
                <div>
                    <div class="text-xl font-semibold tracking-tight">Kabas POS</div>
                    <div class="text-xs text-stone-500">v2 · BKK 1 · Aeon Mall</div>
                </div>
            </div>

            <div class="text-center mb-6">
                <div class="text-sm font-medium text-stone-700">{{ t('enterPin') }}</div>
                <div v-if="errorMessage" class="text-xs text-rose-600 mt-1">{{ errorMessage }}</div>
                <div v-else-if="status === 'signing'" class="text-xs text-stone-400 mt-1">{{ t('signingIn') }}</div>
                <div v-else class="text-xs text-transparent mt-1">·</div>
            </div>

            <PinPad
                v-model:value="pin"
                :length="6"
                :disabled="status === 'signing'"
                :error="status === 'error'"
                @submit="submit"
            />

            <div class="mt-6 flex items-center justify-center gap-1.5 text-[11px] text-stone-400">
                <span class="badge-dot pulse-dot" :class="session.isOnline ? 'bg-emerald-500' : 'bg-stone-400'" style="display:inline-block;width:8px;height:8px;border-radius:9999px;"></span>
                <span>{{ session.isOnline ? 'Online' : t('offline') }}</span>
                <span v-if="!session.usersLoaded" class="ml-2">· {{ t('loading') }}</span>
            </div>
        </div>
    </div>
</template>
