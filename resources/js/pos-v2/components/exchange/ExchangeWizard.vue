<script setup>
import { ref, computed, watch } from 'vue';
import { useExchangeStore } from '../../stores/exchange.js';
import { useI18nStore } from '../../stores/i18n.js';
import { useCatalogStore } from '../../stores/catalog.js';
import { useSessionStore } from '../../stores/session.js';
import VoucherInputDialog from '../payment/VoucherInputDialog.vue';
import NumPad from '../NumPad.vue';

const exchange = useExchangeStore();
const i18n = useI18nStore();
const catalog = useCatalogStore();
const session = useSessionStore();

const t = computed(() => i18n.t);
const locale = computed(() => i18n.locale);

const saleIdInput = ref('');
const productSearch = ref('');
const voucherOpen = ref(false);
const paymentLines = ref([]);
const focusedLine = ref(0);
const inputBuffer = ref('');
const submitting = ref(false);

const paymentMethods = computed(() => {
    if (!catalog.payments || catalog.payments.length === 0) {
        return [
            { id: 'CASH', code: 'CASH', name: 'Cash' },
            { id: 'CARD', code: 'CARD', name: 'Card' },
            { id: 'MOBILE', code: 'MOBILE', name: 'Mobile' },
        ];
    }
    return catalog.payments;
});

function methodCode(m) {
    return String(m.code || m.name || m.id).toUpperCase();
}

// Search products to add as new items (limit to keep it fast)
const productMatches = computed(() => {
    const q = productSearch.value.trim();
    if (!q) return [];
    return catalog.searchLocal(q, { limit: 12 });
});

function productName(item) {
    const n = item.name;
    if (!n) return '';
    if (typeof n === 'string') return n;
    return n[locale.value] || n.en || n.fr || '';
}

function fmt(v) {
    return `$${(Math.round((Number(v) || 0) * 100) / 100).toFixed(2)}`;
}

function fmtDate(v) {
    if (!v) return '—';
    try { return new Date(v).toLocaleDateString(); } catch { return v; }
}

function round2(v) {
    return Math.round((Number(v) || 0) * 100) / 100;
}

// ─── Step navigation ───
async function doLookup() {
    if (!saleIdInput.value.trim()) return;
    await exchange.runLookup(saleIdInput.value.trim());
}

function gotoReturn() { exchange.step = 'return'; }
function gotoNew()    { exchange.step = 'new'; }
function gotoPay() {
    paymentLines.value = exchange.amountToPay > 0
        ? [{ method: 'CASH', amount: exchange.amountToPay }]
        : [];
    focusedLine.value = 0;
    inputBuffer.value = exchange.amountToPay > 0 ? String(exchange.amountToPay) : '';
    exchange.step = 'pay';
}
function gotoConfirm() {
    if (exchange.amountToPay > 0) return gotoPay();
    submit();
}

// ─── Returns step ───
function toggle(item) {
    exchange.toggleReturn(item.sale_item_id);
}

// ─── New items step ───
function addToNew(item) {
    exchange.addNewItem(item, 1);
    productSearch.value = '';
}

function decNewItem(item) {
    exchange.setNewItemQuantity(item.product_id, item.type, (item.quantity || 0) - 1);
}
function incNewItem(item) {
    exchange.setNewItemQuantity(item.product_id, item.type, (item.quantity || 0) + 1);
}

// ─── Payment lines (mirrors PaymentDialog split logic) ───
watch(inputBuffer, (v) => {
    const line = paymentLines.value[focusedLine.value];
    if (!line) return;
    const parsed = parseFloat(v);
    line.amount = isNaN(parsed) ? 0 : parsed;
});

function focusLine(i) {
    focusedLine.value = i;
    inputBuffer.value = paymentLines.value[i] ? String(paymentLines.value[i].amount || '') : '';
}

function addPaymentLine() {
    paymentLines.value.push({ method: 'CASH', amount: 0 });
    focusedLine.value = paymentLines.value.length - 1;
    inputBuffer.value = '';
}

function removePaymentLine(i) {
    paymentLines.value.splice(i, 1);
    if (focusedLine.value >= paymentLines.value.length) {
        focusedLine.value = Math.max(0, paymentLines.value.length - 1);
    }
}

function fillRemaining() {
    const line = paymentLines.value[focusedLine.value];
    if (!line) return;
    const others = paymentLines.value.reduce((s, l, i) => i === focusedLine.value ? s : s + (Number(l.amount) || 0), 0);
    const fill = round2(exchange.amountToPay - others);
    line.amount = Math.max(0, fill);
    inputBuffer.value = String(line.amount);
}

function setMethod(i, method) {
    paymentLines.value[i].method = methodCode(method);
    if (paymentLines.value[i].method !== 'VOUCHER') {
        delete paymentLines.value[i].voucher_code;
    }
}

function handleVoucherValidated({ code, amount }) {
    const existing = paymentLines.value.find((l) => l.method === 'VOUCHER' && l.voucher_code === code);
    const remaining = round2(exchange.amountToPay - paymentLines.value.reduce((s, l) => s + Number(l.amount || 0), 0));
    if (existing) {
        existing.amount = Math.min(amount, exchange.amountToPay);
    } else {
        const applied = Math.min(amount, remaining > 0 ? remaining : amount);
        paymentLines.value.push({
            method: 'VOUCHER',
            amount: round2(Math.max(0, applied)),
            voucher_code: code,
        });
    }
    voucherOpen.value = false;
}

const paymentsTotal = computed(() => round2(paymentLines.value.reduce((s, l) => s + (Number(l.amount) || 0), 0)));
const paymentRemaining = computed(() => round2(exchange.amountToPay - paymentsTotal.value));
const canConfirmPayment = computed(() => Math.abs(paymentRemaining.value) <= 0.01);

// ─── Submit ───
async function submit() {
    if (submitting.value) return;
    submitting.value = true;
    exchange.setPayments(paymentLines.value);
    await exchange.submit({ shiftId: session.currentShift?.id });
    submitting.value = false;
}

function close() {
    exchange.close();
}
</script>

<template>
    <Teleport to="body">
        <Transition name="dialog">
            <div v-if="exchange.open" class="fixed inset-0 z-40 flex items-center justify-center p-4 bg-stone-900/50 backdrop-blur-sm" @click.self="close">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-4xl max-h-[92vh] flex flex-col overflow-hidden">

                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-stone-200 flex items-center justify-between">
                        <div>
                            <h3 class="font-serif text-2xl">{{ t('exchange') }}</h3>
                            <div v-if="exchange.originalSale" class="text-xs text-stone-500 mt-0.5">
                                {{ t('saleNo') }}{{ exchange.originalSale.id }} ·
                                {{ exchange.originalSale.days_since_purchase ?? '?' }} {{ t('daysAgo') }}
                            </div>
                            <div v-else class="text-xs text-stone-500 mt-0.5">{{ t('lookupSale') }}</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <!-- Step pills -->
                            <template v-if="exchange.originalSale">
                                <span
                                    v-for="(label, key, i) in { return: t('returnedItems'), new: t('newItems'), pay: t('payment'), done: t('exchangeDone') }"
                                    :key="key"
                                    class="text-[10px] uppercase tracking-wider px-2 py-1 rounded-full font-semibold"
                                    :class="exchange.step === key
                                        ? 'bg-stone-900 text-white'
                                        : (Object.keys({ return: 1, new: 1, pay: 1, done: 1 }).indexOf(exchange.step) > i
                                            ? 'bg-emerald-100 text-emerald-700'
                                            : 'bg-stone-100 text-stone-400')"
                                    v-show="key !== 'pay' || exchange.amountToPay > 0"
                                >{{ label }}</span>
                            </template>
                            <button @click="close" class="ml-2 text-stone-400 hover:text-stone-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="flex-1 overflow-y-auto p-6 scrollbar-thin">

                        <!-- STEP: LOOKUP -->
                        <div v-if="exchange.step === 'lookup'" class="max-w-md mx-auto pt-8">
                            <label class="block text-sm font-medium text-stone-700 mb-2">{{ t('enterSaleId') }}</label>
                            <div class="flex gap-2">
                                <input
                                    v-model="saleIdInput"
                                    type="text"
                                    inputmode="numeric"
                                    @keyup.enter="doLookup"
                                    placeholder="e.g. 12345"
                                    class="flex-1 bg-stone-50 border border-stone-200 rounded-xl px-4 h-12 text-lg font-mono focus:border-stone-400 focus:outline-none focus:ring-4 focus:ring-stone-200/50"
                                >
                                <button
                                    @click="doLookup"
                                    :disabled="exchange.loading || !saleIdInput.trim()"
                                    class="h-12 px-5 bg-stone-900 hover:bg-stone-800 text-white rounded-xl font-medium disabled:opacity-50"
                                >{{ exchange.loading ? t('loading') : t('lookupSale') }}</button>
                            </div>
                            <div v-if="exchange.error === 'not_found'" class="mt-4 text-sm text-rose-700 bg-rose-50 border border-rose-200 rounded-xl p-3">{{ t('saleNotFound') }}</div>
                            <div v-else-if="exchange.error === 'too_old'" class="mt-4 text-sm text-rose-700 bg-rose-50 border border-rose-200 rounded-xl p-3">{{ t('saleTooOld') }}</div>
                            <div v-else-if="exchange.error" class="mt-4 text-sm text-rose-700 bg-rose-50 border border-rose-200 rounded-xl p-3">{{ exchange.error }}</div>
                        </div>

                        <!-- STEP: RETURN ITEMS -->
                        <div v-else-if="exchange.step === 'return'">
                            <h4 class="text-sm font-medium text-stone-700 mb-3">{{ t('selectReturnItems') }}</h4>
                            <div class="space-y-2">
                                <div
                                    v-for="item in exchange.originalSale.items"
                                    :key="item.sale_item_id"
                                    class="p-3 border rounded-xl flex items-center gap-3"
                                    :class="!item.is_exchangeable
                                        ? 'bg-stone-50 border-stone-200 opacity-50'
                                        : (exchange.isReturned(item.sale_item_id)
                                            ? 'bg-emerald-50 border-emerald-300'
                                            : 'bg-white border-stone-200 hover:bg-stone-50 cursor-pointer')"
                                    @click="item.is_exchangeable && toggle(item)"
                                >
                                    <input
                                        type="checkbox"
                                        :disabled="!item.is_exchangeable"
                                        :checked="exchange.isReturned(item.sale_item_id)"
                                        @click.stop
                                        @change="toggle(item)"
                                        class="w-5 h-5 rounded text-stone-900 border-stone-300 focus:ring-stone-400"
                                    >
                                    <div class="flex-1">
                                        <div class="font-medium text-sm">{{ item.product_name }}</div>
                                        <div class="text-xs text-stone-500">
                                            {{ item.quantity }} × {{ fmt(item.unit_price) }}
                                            <span v-if="!item.is_exchangeable" class="text-rose-600 ml-2">· {{ t('notExchangeable') }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-semibold tabular-nums text-sm">{{ fmt(item.unit_price * item.quantity) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- STEP: NEW ITEMS -->
                        <div v-else-if="exchange.step === 'new'">
                            <h4 class="text-sm font-medium text-stone-700 mb-3">{{ t('selectNewItems') }}</h4>

                            <!-- Search input -->
                            <div class="relative mb-3">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                                <input
                                    v-model="productSearch"
                                    type="text"
                                    placeholder="Search by name or EAN…"
                                    class="w-full pl-10 pr-3 h-11 bg-stone-50 border border-stone-200 rounded-xl text-sm focus:border-stone-400 focus:outline-none focus:ring-4 focus:ring-stone-200/50"
                                >
                            </div>

                            <!-- Search matches -->
                            <div v-if="productMatches.length > 0" class="mb-4 max-h-48 overflow-y-auto border border-stone-200 rounded-xl bg-white scrollbar-thin">
                                <button
                                    v-for="match in productMatches"
                                    :key="match.id"
                                    type="button"
                                    @click="addToNew(match)"
                                    class="w-full px-3 py-2 flex items-center justify-between hover:bg-stone-50 text-left border-b border-stone-100 last:border-b-0"
                                >
                                    <div>
                                        <div class="text-sm font-medium">{{ productName(match) }}</div>
                                        <div class="text-[11px] text-stone-500">EAN {{ match.ean || '—' }}</div>
                                    </div>
                                    <span class="font-semibold tabular-nums text-sm">{{ fmt(match.price) }}</span>
                                </button>
                            </div>

                            <!-- Selected new items -->
                            <div v-if="exchange.newItems.length > 0" class="space-y-2">
                                <div
                                    v-for="item in exchange.newItems"
                                    :key="`${item.type}-${item.product_id}`"
                                    class="p-3 border border-stone-200 bg-white rounded-xl flex items-center gap-3"
                                >
                                    <div class="flex-1">
                                        <div class="text-sm font-medium">{{ productName(item) }}</div>
                                        <div class="text-[11px] text-stone-500">{{ fmt(item.price) }} ea</div>
                                    </div>
                                    <div class="inline-flex items-center bg-stone-50 border border-stone-200 rounded-lg">
                                        <button @click="decNewItem(item)" class="w-7 h-7 flex items-center justify-center text-stone-600 hover:bg-stone-100 rounded-l-lg">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                        </button>
                                        <span class="w-8 text-center text-sm font-semibold tabular-nums">{{ item.quantity }}</span>
                                        <button @click="incNewItem(item)" class="w-7 h-7 flex items-center justify-center text-stone-600 hover:bg-stone-100 rounded-r-lg">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                        </button>
                                    </div>
                                    <div class="font-semibold tabular-nums text-sm w-16 text-right">{{ fmt(item.price * item.quantity) }}</div>
                                </div>
                            </div>
                            <div v-else class="text-center text-sm text-stone-400 py-6">{{ t('noNewItems') }}</div>
                        </div>

                        <!-- STEP: PAYMENT -->
                        <div v-else-if="exchange.step === 'pay'">
                            <h4 class="text-sm font-medium text-stone-700 mb-3">{{ t('payment') }} — {{ fmt(exchange.amountToPay) }} {{ t('balanceToPay') }}</h4>

                            <div class="grid grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <div
                                        v-for="(line, i) in paymentLines"
                                        :key="i"
                                        @click="focusLine(i)"
                                        class="p-3 rounded-xl border cursor-pointer"
                                        :class="focusedLine === i ? 'border-stone-900 bg-stone-50' : 'border-stone-200 hover:bg-stone-50'"
                                    >
                                        <div class="flex items-center justify-between mb-1">
                                            <select
                                                :value="line.method"
                                                @change="(e) => setMethod(i, paymentMethods.find(m => methodCode(m) === e.target.value) || { code: e.target.value, name: e.target.value })"
                                                @click.stop
                                                :disabled="line.method === 'VOUCHER'"
                                                class="text-sm font-medium bg-transparent border-none focus:outline-none"
                                            >
                                                <option v-if="line.method === 'VOUCHER'" value="VOUCHER">Voucher</option>
                                                <option v-for="m in paymentMethods" :key="m.id || m.code || m.name" :value="methodCode(m)">{{ m.name }}</option>
                                            </select>
                                            <button @click.stop="removePaymentLine(i)" class="text-stone-400 hover:text-rose-500">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                            </button>
                                        </div>
                                        <div class="flex items-baseline gap-1">
                                            <span class="text-stone-400 text-sm">$</span>
                                            <span class="font-serif text-2xl tabular-nums">{{ Number(line.amount || 0).toFixed(2) }}</span>
                                        </div>
                                        <div v-if="line.voucher_code" class="text-[10px] font-mono text-stone-500 mt-1">{{ line.voucher_code }}</div>
                                    </div>

                                    <button @click="addPaymentLine" class="w-full h-10 rounded-xl border border-dashed border-stone-300 text-xs text-stone-600 hover:bg-stone-50">+ {{ t('addLine') }}</button>
                                    <button @click="voucherOpen = true" class="w-full h-10 rounded-xl border border-dashed border-amber-300 text-xs text-amber-700 hover:bg-amber-50">+ {{ t('applyVoucher') }}</button>
                                </div>

                                <div>
                                    <div class="text-xs text-stone-500 mb-2">{{ t('amount') }}</div>
                                    <NumPad v-model:value="inputBuffer" />
                                    <button @click="fillRemaining" class="w-full mt-2 h-10 text-xs font-medium text-stone-600 bg-stone-100 hover:bg-stone-200 rounded-lg">Fill remaining</button>

                                    <div class="mt-4 grid grid-cols-2 gap-3 text-xs">
                                        <div class="bg-stone-50 rounded-lg p-2">
                                            <div class="text-stone-500">{{ t('totalPaid') }}</div>
                                            <div class="font-semibold tabular-nums">{{ fmt(paymentsTotal) }}</div>
                                        </div>
                                        <div class="bg-stone-50 rounded-lg p-2">
                                            <div class="text-stone-500">{{ t('remaining') }}</div>
                                            <div class="font-semibold tabular-nums" :class="paymentRemaining > 0.01 ? 'text-amber-700' : (paymentRemaining < -0.01 ? 'text-rose-700' : 'text-emerald-700')">
                                                {{ fmt(paymentRemaining) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- STEP: DONE -->
                        <div v-else-if="exchange.step === 'done'" class="text-center max-w-md mx-auto py-8">
                            <div class="w-16 h-16 mx-auto bg-emerald-100 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-10 h-10 text-emerald-700" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
                            </div>
                            <h3 class="font-serif text-3xl">{{ t('exchangeDone') }}</h3>

                            <dl v-if="exchange.result?.exchange" class="mt-6 text-sm divide-y divide-stone-100 text-left bg-white border border-stone-200 rounded-xl p-4">
                                <div class="flex justify-between py-2">
                                    <dt class="text-stone-600">{{ t('returnTotal') }}</dt>
                                    <dd class="font-medium tabular-nums">{{ fmt(exchange.result.exchange.return_total) }}</dd>
                                </div>
                                <div class="flex justify-between py-2">
                                    <dt class="text-stone-600">{{ t('newItemsTotal') }}</dt>
                                    <dd class="font-medium tabular-nums">{{ fmt(exchange.result.exchange.new_items_total) }}</dd>
                                </div>
                                <div class="flex justify-between py-2">
                                    <dt class="text-stone-600">{{ t('balance') }}</dt>
                                    <dd class="font-medium tabular-nums">{{ fmt(exchange.result.exchange.balance) }}</dd>
                                </div>
                                <div v-if="exchange.result.exchange.voucher_generated" class="py-3 bg-amber-50 -mx-4 px-4 mt-2">
                                    <div class="text-xs text-amber-800 font-semibold mb-1">{{ t('voucherGenerated') }}</div>
                                    <div class="font-mono text-lg tracking-wider">{{ exchange.result.exchange.voucher_generated.code }}</div>
                                    <div class="text-sm font-semibold tabular-nums">{{ fmt(exchange.result.exchange.voucher_generated.amount) }}</div>
                                </div>
                            </dl>

                            <button @click="close" class="mt-6 h-12 px-6 bg-stone-900 hover:bg-stone-800 text-white rounded-xl font-semibold">{{ t('cancel') }}</button>
                        </div>

                    </div>

                    <!-- Footer with totals + nav buttons -->
                    <div v-if="exchange.originalSale && exchange.step !== 'done' && exchange.step !== 'lookup'" class="px-6 py-4 border-t border-stone-200 bg-stone-50">
                        <div class="grid grid-cols-3 gap-4 mb-4 text-sm">
                            <div>
                                <div class="text-stone-500 text-xs">{{ t('returnTotal') }}</div>
                                <div class="font-semibold tabular-nums">{{ fmt(exchange.returnTotal) }}</div>
                            </div>
                            <div>
                                <div class="text-stone-500 text-xs">{{ t('newItemsTotal') }}</div>
                                <div class="font-semibold tabular-nums">{{ fmt(exchange.newItemsTotal) }}</div>
                            </div>
                            <div>
                                <div class="text-stone-500 text-xs">{{ t('balance') }}</div>
                                <div class="font-semibold tabular-nums"
                                    :class="exchange.creditDue > 0 ? 'text-emerald-700' : (exchange.amountToPay > 0 ? 'text-amber-700' : 'text-stone-700')"
                                >
                                    <template v-if="exchange.creditDue > 0">+{{ fmt(exchange.creditDue) }} → {{ t('autoVoucher') }}</template>
                                    <template v-else-if="exchange.amountToPay > 0">−{{ fmt(exchange.amountToPay) }}</template>
                                    <template v-else>{{ t('evenSwap') }}</template>
                                </div>
                            </div>
                        </div>

                        <div v-if="exchange.error" class="text-sm text-rose-600 mb-3">{{ exchange.error }}</div>

                        <div class="flex gap-3">
                            <button v-if="exchange.step === 'return'" @click="close" class="flex-1 h-12 bg-white border border-stone-200 rounded-xl font-medium text-stone-700 hover:bg-stone-50">{{ t('cancel') }}</button>
                            <button v-else-if="exchange.step === 'new'" @click="gotoReturn" class="flex-1 h-12 bg-white border border-stone-200 rounded-xl font-medium text-stone-700 hover:bg-stone-50">← {{ t('back') }}</button>
                            <button v-else-if="exchange.step === 'pay'" @click="gotoNew" class="flex-1 h-12 bg-white border border-stone-200 rounded-xl font-medium text-stone-700 hover:bg-stone-50">← {{ t('back') }}</button>

                            <button
                                v-if="exchange.step === 'return'"
                                @click="gotoNew"
                                :disabled="exchange.returnedItems.length === 0"
                                class="flex-1 h-12 bg-stone-900 hover:bg-stone-800 text-white rounded-xl font-semibold disabled:opacity-50"
                            >{{ t('next') }} →</button>

                            <button
                                v-else-if="exchange.step === 'new'"
                                @click="gotoConfirm"
                                class="flex-1 h-12 bg-stone-900 hover:bg-stone-800 text-white rounded-xl font-semibold"
                            >
                                <template v-if="exchange.amountToPay > 0">{{ t('next') }} →</template>
                                <template v-else>{{ t('processExchange') }}</template>
                            </button>

                            <button
                                v-else-if="exchange.step === 'pay'"
                                @click="submit"
                                :disabled="!canConfirmPayment || submitting"
                                class="flex-1 h-12 bg-stone-900 hover:bg-stone-800 text-white rounded-xl font-semibold disabled:opacity-50"
                            >{{ submitting ? t('loading') : t('processExchange') }}</button>
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
