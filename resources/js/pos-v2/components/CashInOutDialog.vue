<script setup>
import { ref, computed, watch } from 'vue';
import { useSessionStore } from '../stores/session.js';
import { useCashStore } from '../stores/cash.js';
import { useI18nStore } from '../stores/i18n.js';
import NumPad from './NumPad.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    direction: { type: String, default: 'in' }, // 'in' | 'out'
});
const emit = defineEmits(['close']);

const session = useSessionStore();
const cash = useCashStore();
const i18n = useI18nStore();

const amount = ref('');
const submitting = ref(false);

const t = computed(() => i18n.t);
const title = computed(() => props.direction === 'out' ? t.value('addCashOut') : t.value('addCashIn'));

watch(() => props.open, (v) => {
    if (v) {
        amount.value = '';
    }
});

async function confirm() {
    if (submitting.value) return;
    const parsed = parseFloat(amount.value);
    if (isNaN(parsed) || parsed <= 0) return;
    if (!session.currentShift?.id) return;
    submitting.value = true;
    try {
        if (props.direction === 'out') {
            await cash.addCashOut(session.currentShift.id, parsed);
        } else {
            await cash.addCashIn(session.currentShift.id, parsed);
        }
        emit('close');
    } finally {
        submitting.value = false;
    }
}

function close() {
    if (submitting.value) return;
    emit('close');
}
</script>

<template>
    <Teleport to="body">
        <Transition name="dialog">
            <div v-if="open" class="fixed inset-0 z-40 flex items-center justify-center p-4 bg-stone-900/40 backdrop-blur-sm" @click.self="close">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-serif text-2xl">{{ title }}</h3>
                        <button type="button" @click="close" class="text-stone-400 hover:text-stone-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>

                    <label class="block text-sm font-medium text-stone-700 mb-2">{{ t('amount') }}</label>
                    <div class="flex items-baseline gap-2 mb-4">
                        <span class="text-stone-400 text-xl">$</span>
                        <div class="font-serif text-4xl tabular-nums">{{ amount === '' ? '0.00' : amount }}</div>
                    </div>

                    <NumPad v-model:value="amount" :disabled="submitting" />

                    <div class="flex gap-3 mt-5">
                        <button type="button" @click="close" :disabled="submitting" class="flex-1 h-12 bg-white border border-stone-200 rounded-xl font-medium text-stone-700 hover:bg-stone-50 disabled:opacity-50">{{ t('cancel') }}</button>
                        <button type="button" @click="confirm" :disabled="submitting || amount === ''" class="flex-1 h-12 bg-stone-900 hover:bg-stone-800 text-white rounded-xl font-semibold disabled:opacity-50">{{ t('ok') }}</button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.dialog-enter-active, .dialog-leave-active { transition: opacity 0.15s ease; }
.dialog-enter-from, .dialog-leave-to { opacity: 0; }
</style>
