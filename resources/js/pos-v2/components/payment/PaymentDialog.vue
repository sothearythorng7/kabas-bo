<script setup>
import { ref, computed, watch } from 'vue';
import { useI18nStore } from '../../stores/i18n.js';
import { useCartStore } from '../../stores/cart.js';
import { useCatalogStore } from '../../stores/catalog.js';
import NumPad from '../NumPad.vue';
import VoucherInputDialog from './VoucherInputDialog.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
});
const emit = defineEmits(['close', 'paid']);

const i18n = useI18nStore();
const cart = useCartStore();
const catalog = useCatalogStore();
const t = computed(() => i18n.t);

const mode = ref('single'); // 'single' | 'split'
const lines = ref([]); // [{ method: 'CASH', amount: number, voucher_code? }]
const focusedLine = ref(0);
const inputBuffer = ref('');
const error = ref('');
const submitting = ref(false);
const voucherOpen = ref(false);

const total = computed(() => cart.total);
const paid = computed(() => lines.value.reduce((s, l) => s + (Number(l.amount) || 0), 0));
const remaining = computed(() => round2(total.value - paid.value));
const canConfirm = computed(() => Math.abs(remaining.value) <= 0.01 && lines.value.length > 0);

const paymentMethods = computed(() => {
    if (!catalog.payments || catalog.payments.length === 0) {
        return [
            { id: 'CASH', name: 'Cash', code: 'CASH' },
            { id: 'CARD', name: 'Card', code: 'CARD' },
            { id: 'MOBILE', name: 'Mobile', code: 'MOBILE' },
        ];
    }
    return catalog.payments;
});

watch(() => props.open, (v) => {
    if (v) {
        mode.value = 'single';
        lines.value = [];
        inputBuffer.value = '';
        focusedLine.value = 0;
        error.value = '';
        submitting.value = false;
    }
});

function methodCode(m) {
    return String(m.code || m.name || m.id).toUpperCase();
}

function selectSingle(method) {
    lines.value = [{ method: methodCode(method), label: method.name, amount: total.value }];
    error.value = '';
}

function switchToSplit() {
    if (lines.value.length === 0) {
        lines.value = [{ method: 'CASH', label: 'Cash', amount: 0 }];
    }
    mode.value = 'split';
    focusedLine.value = 0;
    inputBuffer.value = '';
}

function switchToSingle() {
    lines.value = [];
    mode.value = 'single';
}

function addLine() {
    lines.value.push({ method: 'CASH', label: 'Cash', amount: 0 });
    focusedLine.value = lines.value.length - 1;
    inputBuffer.value = '';
}

function removeLine(index) {
    lines.value.splice(index, 1);
    if (focusedLine.value >= lines.value.length) {
        focusedLine.value = Math.max(0, lines.value.length - 1);
    }
}

function focusLine(i) {
    focusedLine.value = i;
    inputBuffer.value = lines.value[i] ? String(lines.value[i].amount || '') : '';
}

function setLineMethod(index, method) {
    const code = methodCode(method);
    lines.value[index].method = code;
    lines.value[index].label = method.name;
    if (code !== 'VOUCHER') {
        delete lines.value[index].voucher_code;
    }
}

watch(inputBuffer, (v) => {
    if (mode.value !== 'split') return;
    const line = lines.value[focusedLine.value];
    if (!line) return;
    const parsed = parseFloat(v);
    line.amount = isNaN(parsed) ? 0 : parsed;
});

function fillRemaining() {
    const line = lines.value[focusedLine.value];
    if (!line) return;
    const others = lines.value.reduce((s, l, i) => i === focusedLine.value ? s : s + (Number(l.amount) || 0), 0);
    const fill = round2(total.value - others);
    line.amount = Math.max(0, fill);
    inputBuffer.value = String(line.amount);
}

function openVoucher() {
    voucherOpen.value = true;
}

function handleVoucherValidated({ code, amount }) {
    // If in single mode → switch to split and replace
    if (mode.value === 'single') {
        lines.value = [];
        mode.value = 'split';
    }
    // Find existing voucher line for the same code (avoid duplicates)
    const existing = lines.value.find((l) => l.method === 'VOUCHER' && l.voucher_code === code);
    if (existing) {
        existing.amount = Math.min(amount, total.value);
    } else {
        const applied = Math.min(amount, remaining.value > 0 ? remaining.value : amount);
        lines.value.push({
            method: 'VOUCHER',
            label: 'Voucher',
            amount: round2(Math.max(0, applied)),
            voucher_code: code,
        });
    }
    voucherOpen.value = false;
    focusedLine.value = lines.value.length - 1;
}

async function confirm() {
    if (!canConfirm.value || submitting.value) return;
    submitting.value = true;
    error.value = '';
    try {
        const payload = {
            payment_type: lines.value[0]?.method || 'CASH',
            split_payments: lines.value.map((l) => ({
                payment_type: l.method,
                amount: round2(Number(l.amount) || 0),
                voucher_code: l.voucher_code || undefined,
            })),
        };
        const finalizedId = await cart.finalize(payload);
        emit('paid', { localId: finalizedId });
    } catch (err) {
        console.error('[POS V2] finalize failed', err);
        error.value = err.message || 'Payment failed';
    } finally {
        submitting.value = false;
    }
}

function fmt(v) {
    return `$${(Math.round((v || 0) * 100) / 100).toFixed(2)}`;
}
function round2(v) {
    return Math.round((Number(v) || 0) * 100) / 100;
}
</script>

<template>
    <Teleport to="body">
        <Transition name="dialog">
            <div v-if="open" class="fixed inset-0 z-40 flex items-center justify-center p-4 bg-stone-900/40 backdrop-blur-sm" @click.self="emit('close')">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[92vh] flex flex-col overflow-hidden">
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-stone-200 flex items-center justify-between">
                        <div>
                            <h3 class="font-serif text-2xl">{{ t('payment') }}</h3>
                            <div class="text-xs text-stone-500 mt-0.5">{{ t('totalDue') }} <span class="font-semibold text-stone-900 tabular-nums ml-1">{{ fmt(total) }}</span></div>
                        </div>
                        <button @click="emit('close')" class="text-stone-400 hover:text-stone-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>

                    <!-- Mode toggle -->
                    <div class="px-6 pt-4">
                        <div class="grid grid-cols-2 gap-1 bg-stone-100 rounded-xl p-1">
                            <button type="button" @click="switchToSingle" class="h-9 rounded-lg text-sm font-medium" :class="mode === 'single' ? 'bg-white shadow-sm text-stone-900' : 'text-stone-500'">{{ t('single') }}</button>
                            <button type="button" @click="switchToSplit" class="h-9 rounded-lg text-sm font-medium" :class="mode === 'split' ? 'bg-white shadow-sm text-stone-900' : 'text-stone-500'">{{ t('split') }}</button>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="flex-1 overflow-y-auto p-6 scrollbar-thin">

                        <!-- SINGLE MODE: method grid -->
                        <div v-if="mode === 'single'">
                            <div class="grid grid-cols-3 gap-3">
                                <button
                                    v-for="m in paymentMethods"
                                    :key="m.id || m.code || m.name"
                                    type="button"
                                    @click="selectSingle(m)"
                                    class="h-24 rounded-2xl border text-base font-semibold flex flex-col items-center justify-center gap-1 transition-all"
                                    :class="lines[0] && lines[0].method === methodCode(m)
                                        ? 'bg-stone-900 text-white border-stone-900 shadow-lg'
                                        : 'bg-white text-stone-800 border-stone-200 hover:bg-stone-50'"
                                >
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                                    {{ m.name }}
                                </button>
                                <button
                                    type="button"
                                    @click="openVoucher"
                                    class="h-24 rounded-2xl border-2 border-dashed border-stone-300 text-stone-600 hover:bg-stone-50 text-base font-medium flex flex-col items-center justify-center gap-1"
                                >
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                    {{ t('voucher') }}
                                </button>
                            </div>
                        </div>

                        <!-- SPLIT MODE: lines + numpad -->
                        <div v-else class="grid grid-cols-2 gap-6">
                            <!-- Lines -->
                            <div class="space-y-2">
                                <div
                                    v-for="(line, i) in lines"
                                    :key="i"
                                    @click="focusLine(i)"
                                    class="p-3 rounded-xl border cursor-pointer transition-all"
                                    :class="focusedLine === i ? 'border-stone-900 bg-stone-50' : 'border-stone-200 hover:bg-stone-50'"
                                >
                                    <div class="flex items-center justify-between mb-2">
                                        <select
                                            :value="line.method"
                                            @change="(e) => setLineMethod(i, paymentMethods.find(m => methodCode(m) === e.target.value) || { code: e.target.value, name: e.target.value })"
                                            @click.stop
                                            :disabled="line.method === 'VOUCHER'"
                                            class="text-sm font-medium bg-transparent border-none focus:outline-none"
                                        >
                                            <option v-if="line.method === 'VOUCHER'" value="VOUCHER">Voucher</option>
                                            <option v-for="m in paymentMethods" :key="m.id || m.code || m.name" :value="methodCode(m)">{{ m.name }}</option>
                                        </select>
                                        <button @click.stop="removeLine(i)" class="text-stone-400 hover:text-rose-500">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        </button>
                                    </div>
                                    <div class="flex items-baseline gap-1">
                                        <span class="text-stone-400 text-sm">$</span>
                                        <span class="font-serif text-2xl tabular-nums">{{ Number(line.amount || 0).toFixed(2) }}</span>
                                    </div>
                                    <div v-if="line.voucher_code" class="text-[10px] font-mono text-stone-500 mt-1">{{ line.voucher_code }}</div>
                                </div>

                                <button
                                    type="button"
                                    @click="addLine"
                                    class="w-full h-12 rounded-xl border border-dashed border-stone-300 text-sm text-stone-600 hover:bg-stone-50 flex items-center justify-center gap-1.5"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    {{ t('addLine') }}
                                </button>
                                <button
                                    type="button"
                                    @click="openVoucher"
                                    class="w-full h-12 rounded-xl border border-dashed border-amber-300 text-sm text-amber-700 hover:bg-amber-50 flex items-center justify-center gap-1.5"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                    {{ t('applyVoucher') }}
                                </button>
                            </div>

                            <!-- NumPad -->
                            <div>
                                <div class="text-xs text-stone-500 mb-2">{{ t('amount') }}</div>
                                <NumPad v-model:value="inputBuffer" />
                                <button
                                    type="button"
                                    @click="fillRemaining"
                                    class="w-full mt-2 h-10 text-xs font-medium text-stone-600 bg-stone-100 hover:bg-stone-200 rounded-lg"
                                >Fill remaining</button>
                            </div>
                        </div>
                    </div>

                    <!-- Footer totals + confirm -->
                    <div class="px-6 py-4 border-t border-stone-200 bg-stone-50">
                        <div class="grid grid-cols-3 gap-4 mb-4 text-sm">
                            <div>
                                <div class="text-stone-500 text-xs">{{ t('totalDue') }}</div>
                                <div class="font-semibold tabular-nums">{{ fmt(total) }}</div>
                            </div>
                            <div>
                                <div class="text-stone-500 text-xs">{{ t('totalPaid') }}</div>
                                <div class="font-semibold tabular-nums">{{ fmt(paid) }}</div>
                            </div>
                            <div>
                                <div class="text-stone-500 text-xs">{{ t('remaining') }}</div>
                                <div class="font-semibold tabular-nums"
                                    :class="Math.abs(remaining) > 0.01 ? (remaining > 0 ? 'text-amber-700' : 'text-rose-700') : 'text-emerald-700'"
                                >{{ remaining >= 0 ? '' : '+' }}{{ fmt(Math.abs(remaining)) }}</div>
                            </div>
                        </div>
                        <div v-if="error" class="text-sm text-rose-600 mb-3">{{ error }}</div>
                        <div class="flex gap-3">
                            <button type="button" @click="emit('close')" :disabled="submitting" class="flex-1 h-12 bg-white border border-stone-200 rounded-xl font-medium text-stone-700 hover:bg-stone-50 disabled:opacity-50">{{ t('cancel') }}</button>
                            <button type="button" @click="confirm" :disabled="!canConfirm || submitting" class="flex-1 h-12 bg-stone-900 hover:bg-stone-800 text-white rounded-xl font-semibold disabled:opacity-50 disabled:cursor-not-allowed">
                                {{ submitting ? t('loading') : t('confirmPayment') }}
                            </button>
                        </div>
                    </div>
                </div>

                <VoucherInputDialog :open="voucherOpen" @close="voucherOpen = false" @validated="handleVoucherValidated" />
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.dialog-enter-active, .dialog-leave-active { transition: opacity 0.15s ease; }
.dialog-enter-from, .dialog-leave-to { opacity: 0; }
</style>
