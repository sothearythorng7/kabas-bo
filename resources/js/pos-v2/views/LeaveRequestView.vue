<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useSessionStore } from '../stores/session.js';
import { useI18nStore } from '../stores/i18n.js';
import { usePlanningStore } from '../stores/planning.js';

const router = useRouter();
const session = useSessionStore();
const i18n = useI18nStore();
const planning = usePlanningStore();

const t = computed(() => i18n.t);

const dateStart = ref(formatToday());
const dateEnd = ref(formatToday());
const leaveType = ref('vacation');
const reason = ref('');

const submitting = ref(false);
const error = ref('');
const successMessage = ref('');

const types = computed(() => {
    const map = i18n.t('leaveTypes');
    if (typeof map !== 'object') return [];
    return Object.entries(map).map(([key, label]) => ({ key, label }));
});

const dayCount = computed(() => {
    const a = new Date(dateStart.value);
    const b = new Date(dateEnd.value);
    if (isNaN(a) || isNaN(b) || b < a) return 0;
    const ms = b.getTime() - a.getTime();
    return Math.floor(ms / (1000 * 60 * 60 * 24)) + 1;
});

const conflict = computed(() => {
    if (!dateStart.value || !dateEnd.value) return null;
    return planning.findConflict(dateStart.value, dateEnd.value);
});

const canSubmit = computed(() => {
    return dayCount.value > 0
        && !conflict.value
        && leaveType.value
        && !submitting.value;
});

function formatToday() {
    const d = new Date();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${d.getFullYear()}-${m}-${day}`;
}

async function submit() {
    if (!canSubmit.value) return;
    submitting.value = true;
    error.value = '';
    successMessage.value = '';
    const res = await planning.submitLeave({
        userId: session.currentUser.id,
        dateStart: dateStart.value,
        dateEnd: dateEnd.value,
        type: leaveType.value,
        reason: reason.value.trim(),
    });
    submitting.value = false;

    if (res.ok) {
        successMessage.value = t.value('leaveRequested');
        setTimeout(() => router.push({ name: 'planning' }), 1500);
    } else if (res.conflict) {
        error.value = t.value('leaveConflict');
    } else {
        error.value = res.error || 'Submit failed';
    }
}

function back() {
    router.push({ name: 'planning' });
}

onMounted(async () => {
    if (session.currentUser?.id) {
        await planning.load(session.currentUser.id);
    }
});
</script>

<template>
    <div class="flex-1 flex flex-col p-6 max-w-2xl mx-auto w-full overflow-y-auto">
        <header class="flex items-center justify-between mb-6">
            <div>
                <div class="font-serif text-3xl">{{ t('requestLeave') }}</div>
                <div class="text-sm text-stone-500 mt-0.5">{{ session.currentUser?.name }}</div>
            </div>
            <button @click="back" class="h-10 px-4 text-sm text-stone-500 hover:text-stone-900">← {{ t('back') }}</button>
        </header>

        <div class="bg-white border border-stone-200 rounded-2xl p-6 space-y-5">

            <!-- Leave type -->
            <div>
                <label class="block text-sm font-medium text-stone-700 mb-2">{{ t('leaveType') }}</label>
                <div class="grid grid-cols-4 gap-2">
                    <button
                        v-for="ty in types"
                        :key="ty.key"
                        type="button"
                        @click="leaveType = ty.key"
                        class="h-11 rounded-xl text-sm font-medium border transition-colors"
                        :class="leaveType === ty.key
                            ? 'bg-stone-900 text-white border-stone-900'
                            : 'bg-white text-stone-700 border-stone-200 hover:bg-stone-50'"
                    >{{ ty.label }}</button>
                </div>
            </div>

            <!-- Dates -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium text-stone-700 mb-2">{{ t('startDate') }}</label>
                    <input
                        v-model="dateStart"
                        type="date"
                        class="w-full bg-stone-50 border border-stone-200 rounded-xl px-4 h-11 text-[15px] focus:border-stone-400 focus:outline-none focus:ring-4 focus:ring-stone-200/50"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-stone-700 mb-2">{{ t('endDate') }}</label>
                    <input
                        v-model="dateEnd"
                        type="date"
                        :min="dateStart"
                        class="w-full bg-stone-50 border border-stone-200 rounded-xl px-4 h-11 text-[15px] focus:border-stone-400 focus:outline-none focus:ring-4 focus:ring-stone-200/50"
                    >
                </div>
            </div>

            <div v-if="dayCount > 0" class="text-xs text-stone-500">
                {{ dayCount }} day{{ dayCount > 1 ? 's' : '' }}
            </div>
            <div v-else-if="dateStart && dateEnd" class="text-sm text-rose-600">{{ t('invalidDateRange') }}</div>

            <!-- Reason -->
            <div>
                <label class="block text-sm font-medium text-stone-700 mb-2">{{ t('reasonOptional') }}</label>
                <textarea
                    v-model="reason"
                    rows="3"
                    placeholder=""
                    class="w-full bg-stone-50 border border-stone-200 rounded-xl px-4 py-3 text-[15px] focus:border-stone-400 focus:outline-none focus:ring-4 focus:ring-stone-200/50 resize-none"
                ></textarea>
            </div>

            <!-- Errors / success -->
            <div v-if="conflict" class="bg-rose-50 border border-rose-200 rounded-xl p-3 text-sm text-rose-700">
                {{ t('leaveConflict') }}
            </div>
            <div v-if="error" class="bg-rose-50 border border-rose-200 rounded-xl p-3 text-sm text-rose-700">{{ error }}</div>
            <div v-if="successMessage" class="bg-emerald-50 border border-emerald-200 rounded-xl p-3 text-sm text-emerald-700">{{ successMessage }}</div>

            <div class="flex gap-3 pt-2">
                <button type="button" @click="back" :disabled="submitting" class="flex-1 h-12 bg-white border border-stone-200 rounded-xl font-medium text-stone-700 hover:bg-stone-50 disabled:opacity-50">{{ t('cancel') }}</button>
                <button
                    type="button"
                    @click="submit"
                    :disabled="!canSubmit"
                    class="flex-1 h-12 bg-stone-900 hover:bg-stone-800 text-white rounded-xl font-semibold disabled:opacity-50"
                >{{ submitting ? t('loading') : t('submit') }}</button>
            </div>
        </div>
    </div>
</template>
