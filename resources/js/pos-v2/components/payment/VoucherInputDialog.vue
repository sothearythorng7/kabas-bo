<script setup>
import { ref, computed, watch } from 'vue';
import { useI18nStore } from '../../stores/i18n.js';
import { validateVoucher } from '../../api/endpoints/voucher.js';

const props = defineProps({
    open: { type: Boolean, default: false },
});
const emit = defineEmits(['close', 'validated']);

const i18n = useI18nStore();
const t = computed(() => i18n.t);

const code = ref('');
const status = ref('idle'); // idle | validating | valid | invalid
const result = ref(null); // { amount, expires_at }
const errorMessage = ref('');

watch(() => props.open, (v) => {
    if (v) {
        code.value = '';
        status.value = 'idle';
        result.value = null;
        errorMessage.value = '';
    }
});

async function validate() {
    if (status.value === 'validating') return;
    const trimmed = code.value.trim().toUpperCase();
    if (!trimmed) {
        errorMessage.value = t.value('invalidVoucher');
        status.value = 'invalid';
        return;
    }
    status.value = 'validating';
    errorMessage.value = '';
    try {
        const res = await validateVoucher(trimmed);
        if (res?.success && Number(res.amount) > 0) {
            result.value = {
                code: trimmed,
                amount: Number(res.amount),
                expires_at: res.expires_at || null,
            };
            status.value = 'valid';
        } else {
            errorMessage.value = res?.error || t.value('invalidVoucher');
            status.value = 'invalid';
            result.value = null;
        }
    } catch (err) {
        console.error('[POS V2] voucher validate failed', err);
        errorMessage.value = err.message || t.value('invalidVoucher');
        status.value = 'invalid';
        result.value = null;
    }
}

function apply() {
    if (status.value !== 'valid' || !result.value) return;
    emit('validated', result.value);
}

function close() {
    emit('close');
}

function fmt(v) {
    return `$${(Math.round((v || 0) * 100) / 100).toFixed(2)}`;
}
</script>

<template>
    <Teleport to="body">
        <Transition name="dialog">
            <div v-if="open" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-stone-900/40 backdrop-blur-sm" @click.self="close">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-serif text-2xl">{{ t('voucher') }}</h3>
                        <button @click="close" class="text-stone-400 hover:text-stone-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>

                    <label class="block text-sm font-medium text-stone-700 mb-2">{{ t('voucherCode') }}</label>
                    <input
                        v-model="code"
                        type="text"
                        @keyup.enter="validate"
                        placeholder="e.g. EX-AB12-CD34"
                        class="w-full bg-stone-50 border border-stone-200 rounded-xl px-4 h-12 text-[15px] font-mono tracking-wider uppercase mb-3 focus:border-stone-400 focus:outline-none focus:ring-4 focus:ring-stone-200/50"
                    >

                    <button
                        type="button"
                        @click="validate"
                        :disabled="status === 'validating' || !code.trim()"
                        class="w-full h-11 bg-stone-100 hover:bg-stone-200 rounded-xl font-medium text-stone-800 disabled:opacity-50 mb-4"
                    >
                        {{ status === 'validating' ? t('loading') : t('validate') }}
                    </button>

                    <div v-if="status === 'valid' && result" class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 mb-4">
                        <div class="flex items-baseline justify-between">
                            <span class="text-sm text-emerald-800 font-medium">{{ t('voucherAvailable') }}</span>
                            <span class="font-serif text-2xl text-emerald-900 tabular-nums">{{ fmt(result.amount) }}</span>
                        </div>
                        <div v-if="result.expires_at" class="text-xs text-emerald-700 mt-1">{{ t('expires') }}: {{ new Date(result.expires_at).toLocaleDateString() }}</div>
                    </div>

                    <div v-else-if="status === 'invalid'" class="bg-rose-50 border border-rose-200 rounded-xl p-3 mb-4 text-sm text-rose-700">
                        {{ errorMessage }}
                    </div>

                    <div class="flex gap-3">
                        <button type="button" @click="close" class="flex-1 h-12 bg-white border border-stone-200 rounded-xl font-medium text-stone-700 hover:bg-stone-50">{{ t('cancel') }}</button>
                        <button type="button" @click="apply" :disabled="status !== 'valid'" class="flex-1 h-12 bg-stone-900 hover:bg-stone-800 text-white rounded-xl font-semibold disabled:opacity-50">{{ t('applyVoucher') }}</button>
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
