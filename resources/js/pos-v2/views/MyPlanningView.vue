<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useSessionStore } from '../stores/session.js';
import { useI18nStore } from '../stores/i18n.js';
import { usePlanningStore } from '../stores/planning.js';

const router = useRouter();
const session = useSessionStore();
const i18n = useI18nStore();
const planning = usePlanningStore();

const t = computed(() => i18n.t);

const cursor = ref(new Date()); // any day in the displayed month

const weekdays = computed(() => {
    // Locale-aware short weekday labels, Monday-first.
    const base = new Date(2026, 0, 5); // Mon 2026-01-05
    const fmt = new Intl.DateTimeFormat(i18n.locale === 'fr' ? 'fr-FR' : 'en-US', { weekday: 'short' });
    return Array.from({ length: 7 }, (_, i) => {
        const d = new Date(base);
        d.setDate(base.getDate() + i);
        return fmt.format(d);
    });
});

const monthLabel = computed(() => {
    return new Intl.DateTimeFormat(i18n.locale === 'fr' ? 'fr-FR' : 'en-US', { month: 'long', year: 'numeric' }).format(cursor.value);
});

const gridCells = computed(() => {
    const year = cursor.value.getFullYear();
    const month = cursor.value.getMonth();
    const firstOfMonth = new Date(year, month, 1);
    const lastOfMonth = new Date(year, month + 1, 0);

    // Compute leading blank cells so the grid starts on Monday.
    let leading = firstOfMonth.getDay() - 1; // JS: 0=Sun, 1=Mon…; we want Mon-first
    if (leading < 0) leading = 6;

    const cells = [];
    for (let i = 0; i < leading; i++) cells.push({ blank: true });

    for (let day = 1; day <= lastOfMonth.getDate(); day++) {
        const dt = new Date(year, month, day);
        const iso = isoOf(dt);
        cells.push({
            blank: false,
            date: dt,
            iso,
            isToday: isoOf(new Date()) === iso,
            info: planning.dayInfo(iso),
        });
    }

    // Pad to full 6 weeks for visual consistency (42 cells).
    while (cells.length < 42) cells.push({ blank: true });
    return cells;
});

function isoOf(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

function prevMonth() {
    const d = new Date(cursor.value);
    d.setDate(1);
    d.setMonth(d.getMonth() - 1);
    cursor.value = d;
}
function nextMonth() {
    const d = new Date(cursor.value);
    d.setDate(1);
    d.setMonth(d.getMonth() + 1);
    cursor.value = d;
}

function goRequest() {
    router.push({ name: 'leave-request' });
}

function back() {
    router.push({ name: 'dashboard' });
}

function recentLeaves() {
    return [...planning.leaves]
        .sort((a, b) => new Date(b.date_start || b.start_date || b.date) - new Date(a.date_start || a.start_date || a.date))
        .slice(0, 5);
}

function leaveTypeLabel(type) {
    const map = i18n.t('leaveTypes');
    if (typeof map === 'object' && map[type]) return map[type];
    return type || '—';
}

function statusLabel(l) {
    const s = l.status || 'pending';
    if (s === 'approved') return { label: 'Approved', tone: 'emerald' };
    if (s === 'rejected') return { label: 'Rejected', tone: 'rose' };
    return { label: 'Pending', tone: 'amber' };
}

onMounted(async () => {
    if (session.currentUser?.id) {
        await planning.load(session.currentUser.id);
    }
});

watch(() => session.currentUser?.id, async (id) => {
    if (id) await planning.load(id);
});
</script>

<template>
    <div class="flex-1 flex flex-col p-6 max-w-6xl mx-auto w-full overflow-y-auto">
        <header class="flex items-center justify-between mb-6">
            <div>
                <div class="font-serif text-3xl">{{ t('myPlanning') }}</div>
                <div class="text-sm text-stone-500 mt-0.5">{{ session.currentUser?.name }}</div>
            </div>
            <div class="flex items-center gap-2">
                <button @click="goRequest" class="h-10 px-4 bg-stone-900 hover:bg-stone-800 text-white rounded-xl font-medium text-sm">{{ t('requestLeave') }}</button>
                <button @click="back" class="h-10 px-4 text-sm text-stone-500 hover:text-stone-900">← {{ t('back') }}</button>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Calendar -->
            <section class="lg:col-span-2 bg-white border border-stone-200 rounded-2xl p-5">
                <div class="flex items-center justify-between mb-4">
                    <button @click="prevMonth" class="w-9 h-9 rounded-lg hover:bg-stone-100 flex items-center justify-center" :title="t('prevMonth')">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
                    </button>
                    <h2 class="font-serif text-2xl capitalize">{{ monthLabel }}</h2>
                    <button @click="nextMonth" class="w-9 h-9 rounded-lg hover:bg-stone-100 flex items-center justify-center" :title="t('nextMonth')">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
                    </button>
                </div>

                <div class="grid grid-cols-7 gap-1 mb-2 text-[11px] text-stone-500 font-medium uppercase tracking-wider text-center">
                    <div v-for="d in weekdays" :key="d">{{ d }}</div>
                </div>

                <div class="grid grid-cols-7 gap-1">
                    <div
                        v-for="(cell, i) in gridCells"
                        :key="i"
                        class="aspect-square rounded-lg flex flex-col items-center justify-start p-1.5 text-xs"
                        :class="cell.blank
                            ? ''
                            : (cell.info?.leave
                                ? 'bg-amber-50 border border-amber-200'
                                : (cell.info?.shift
                                    ? 'bg-sky-50 border border-sky-200'
                                    : 'bg-stone-50 border border-stone-100'))"
                    >
                        <template v-if="!cell.blank">
                            <span
                                class="font-semibold tabular-nums"
                                :class="cell.isToday ? 'bg-stone-900 text-white rounded-full w-6 h-6 flex items-center justify-center' : 'text-stone-700'"
                            >{{ cell.date.getDate() }}</span>
                            <div class="mt-auto w-full">
                                <div v-if="cell.info?.leave" class="text-[9px] uppercase tracking-wider text-amber-800 font-semibold truncate text-center">
                                    {{ leaveTypeLabel(cell.info.leave.type) }}
                                </div>
                                <div v-else-if="cell.info?.shift" class="text-[9px] uppercase tracking-wider text-sky-800 font-semibold truncate text-center">
                                    {{ cell.info.shift.start_time ? cell.info.shift.start_time.slice(0, 5) : 'Shift' }}
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Legend -->
                <div class="flex items-center gap-4 mt-4 text-[11px] text-stone-500">
                    <span class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded bg-sky-100 border border-sky-200"></span>
                        {{ t('shiftPlanned') }}
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded bg-amber-100 border border-amber-200"></span>
                        {{ t('onLeave') }}
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="w-3 h-3 rounded-full bg-stone-900"></span>
                        {{ t('today') }}
                    </span>
                </div>
            </section>

            <!-- Side panel -->
            <aside class="space-y-4">
                <section class="bg-white border border-stone-200 rounded-2xl p-5">
                    <h3 class="text-sm font-medium text-stone-700 mb-3">{{ t('leaveBalance') }}</h3>
                    <div v-if="planning.balance" class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-stone-600">{{ t('availableDays') }}</span>
                            <span class="font-semibold tabular-nums">{{ planning.balance.available_days ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-stone-600">{{ t('usedDays') }}</span>
                            <span class="font-semibold tabular-nums">{{ planning.balance.used_days ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-stone-600">{{ t('pendingRequests') }}</span>
                            <span class="font-semibold tabular-nums">{{ planning.balance.pending_requests ?? '—' }}</span>
                        </div>
                    </div>
                    <div v-else class="text-sm text-stone-400 italic">{{ t('loading') }}</div>
                </section>

                <section class="bg-white border border-stone-200 rounded-2xl p-5">
                    <h3 class="text-sm font-medium text-stone-700 mb-3">Recent leaves</h3>
                    <ul v-if="recentLeaves().length > 0" class="space-y-2 text-sm">
                        <li v-for="l in recentLeaves()" :key="l.id || `${l.date_start}-${l.type}`" class="flex items-center justify-between">
                            <div>
                                <div class="text-stone-900">{{ leaveTypeLabel(l.type) }}</div>
                                <div class="text-[11px] text-stone-500">{{ (l.date_start || l.start_date || l.date || '').slice(0, 10) }} → {{ (l.date_end || l.end_date || l.date || '').slice(0, 10) }}</div>
                            </div>
                            <span
                                class="text-[10px] uppercase tracking-wider px-2 py-0.5 rounded-full font-semibold"
                                :class="{
                                    'bg-emerald-100 text-emerald-800': statusLabel(l).tone === 'emerald',
                                    'bg-rose-100 text-rose-800': statusLabel(l).tone === 'rose',
                                    'bg-amber-100 text-amber-800': statusLabel(l).tone === 'amber',
                                }"
                            >{{ statusLabel(l).label }}</span>
                        </li>
                    </ul>
                    <div v-else class="text-sm text-stone-400 italic">No recent leaves</div>
                </section>
            </aside>
        </div>
    </div>
</template>
