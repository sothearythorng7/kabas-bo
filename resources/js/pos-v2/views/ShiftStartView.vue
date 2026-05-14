<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useSessionStore } from '../stores/session.js';
import { useI18nStore } from '../stores/i18n.js';
import { useCashStore } from '../stores/cash.js';
import { startShift } from '../api/endpoints/shifts.js';
import { fetchActiveEvents } from '../api/endpoints/events.js';
import { fetchTodayAbsences } from '../api/endpoints/planning.js';
import NumPad from '../components/NumPad.vue';

const router = useRouter();
const session = useSessionStore();
const i18n = useI18nStore();
const cash = useCashStore();

const t = computed(() => i18n.t);

const amount = ref('');
const selectedEventId = ref(null);
const events = ref([]);
const absences = ref([]);
const submitting = ref(false);
const error = ref('');

onMounted(async () => {
    if (!session.currentUser) {
        router.replace({ name: 'login' });
        return;
    }
    const storeId = session.currentUser.store_id;
    if (!storeId) return;

    try {
        const [evs, abs] = await Promise.all([
            fetchActiveEvents(storeId).catch(() => []),
            fetchTodayAbsences(storeId).catch(() => []),
        ]);
        events.value = Array.isArray(evs) ? evs : [];
        absences.value = Array.isArray(abs) ? abs : (abs?.absences || []);
    } catch (err) {
        console.warn('[POS V2] shift-start side data failed', err);
    }
});

async function submit() {
    if (submitting.value) return;
    error.value = '';
    const parsed = parseFloat(amount.value);
    if (isNaN(parsed) || parsed < 0) {
        error.value = 'Invalid amount';
        return;
    }
    submitting.value = true;
    try {
        const shift = await startShift({
            user_id: session.currentUser.id,
            start_amount: parsed,
            popup_event_id: selectedEventId.value || null,
        });
        if (!shift || !shift.id) {
            throw new Error('Empty shift response');
        }
        session.setShift(shift);
        await cash.loadForShift(shift.id);
        router.push({ name: 'dashboard' });
    } catch (err) {
        console.error('[POS V2] startShift failed', err);
        error.value = err.message || 'Failed to start shift';
    } finally {
        submitting.value = false;
    }
}

function goBack() {
    session.signOut();
    router.replace({ name: 'login' });
}

function formatEventDate(ev) {
    if (!ev?.start_date) return '';
    try {
        return new Date(ev.start_date).toLocaleDateString();
    } catch {
        return ev.start_date;
    }
}
</script>

<template>
    <div class="flex-1 flex flex-col p-6 max-w-5xl mx-auto w-full">
        <header class="flex items-center justify-between mb-6">
            <div>
                <div class="font-serif text-3xl">{{ t('startShift') }}</div>
                <div class="text-sm text-stone-500 mt-0.5">{{ session.currentUser?.name }}</div>
            </div>
            <button
                type="button"
                @click="goBack"
                class="text-sm text-stone-500 hover:text-stone-900"
            >← {{ t('back') }}</button>
        </header>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 flex-1">

            <!-- Left: cash + event -->
            <div class="bg-white rounded-2xl border border-stone-200 p-6 flex flex-col">
                <label class="block text-sm font-medium text-stone-700 mb-2">{{ t('initialCash') }}</label>
                <div class="flex items-baseline gap-2 mb-4">
                    <span class="text-stone-400 text-2xl">$</span>
                    <div class="font-serif text-4xl tabular-nums flex-1">
                        {{ amount === '' ? '0.00' : amount }}
                    </div>
                </div>

                <NumPad v-model:value="amount" :disabled="submitting" />

                <div class="mt-6">
                    <label class="block text-sm font-medium text-stone-700 mb-2">{{ t('popupEvent') }}</label>
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            @click="selectedEventId = null"
                            class="px-3 py-1.5 rounded-full text-sm border"
                            :class="selectedEventId === null
                                ? 'bg-stone-900 text-white border-stone-900'
                                : 'bg-white text-stone-700 border-stone-200 hover:bg-stone-50'"
                        >{{ t('none') }}</button>
                        <button
                            v-for="ev in events"
                            :key="ev.id"
                            type="button"
                            @click="selectedEventId = ev.id"
                            class="px-3 py-1.5 rounded-full text-sm border"
                            :class="selectedEventId === ev.id
                                ? 'bg-stone-900 text-white border-stone-900'
                                : 'bg-white text-stone-700 border-stone-200 hover:bg-stone-50'"
                        >
                            {{ ev.name || ev.title || ev.location || `Event #${ev.id}` }}
                            <span v-if="ev.start_date" class="text-stone-400 ml-1">{{ formatEventDate(ev) }}</span>
                        </button>
                    </div>
                </div>

                <div v-if="error" class="mt-4 text-sm text-rose-600">{{ error }}</div>

                <button
                    type="button"
                    @click="submit"
                    :disabled="submitting || amount === ''"
                    class="mt-auto h-14 bg-stone-900 hover:bg-stone-800 text-white rounded-2xl font-semibold text-base disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {{ submitting ? t('loading') : t('startShift') }}
                </button>
            </div>

            <!-- Right: today's absences -->
            <div class="bg-white rounded-2xl border border-stone-200 p-6">
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-5 h-5 text-stone-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    <h2 class="font-medium text-stone-800">{{ t('todayAbsences') }}</h2>
                </div>

                <div v-if="absences.length === 0" class="text-sm text-stone-400 italic">
                    {{ t('noAbsences') }}
                </div>
                <ul v-else class="space-y-2">
                    <li
                        v-for="abs in absences"
                        :key="abs.id || `${abs.user_name}-${abs.date_start}`"
                        class="flex items-center gap-3 p-3 bg-stone-50 rounded-xl"
                    >
                        <div class="w-9 h-9 rounded-full bg-stone-200 flex items-center justify-center text-stone-700 font-semibold text-sm">
                            {{ (abs.user_name || abs.name || '?').charAt(0).toUpperCase() }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium truncate">{{ abs.user_name || abs.name }}</div>
                            <div class="text-xs text-stone-500">{{ abs.type || abs.reason || 'Leave' }}</div>
                        </div>
                    </li>
                </ul>
            </div>

        </div>
    </div>
</template>
