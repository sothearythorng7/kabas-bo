<script setup>
import { ref, computed, watch } from 'vue';
import NumPad from '../NumPad.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    scope: { type: String, default: 'line' }, // 'line' | 'global'
    initial: { type: Object, default: null }, // existing discount, if any
});

const emit = defineEmits(['close', 'apply', 'clear']);

const mode = ref('percent'); // 'percent' | 'amount'
const value = ref('');

watch(() => props.open, (v) => {
    if (v) {
        if (props.initial) {
            mode.value = props.initial.type === 'amount' ? 'amount' : 'percent';
            value.value = String(props.initial.value || '');
        } else {
            mode.value = 'percent';
            value.value = '';
        }
    }
});

const title = computed(() => props.scope === 'global' ? 'Global discount' : 'Line discount');

function apply() {
    const parsed = parseFloat(value.value);
    if (isNaN(parsed) || parsed <= 0) return;
    emit('apply', { type: mode.value, value: parsed });
}
function clear() {
    emit('clear');
}
function close() {
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

                    <div class="grid grid-cols-2 gap-2 mb-4 bg-stone-100 rounded-xl p-1">
                        <button type="button" @click="mode = 'percent'" class="h-10 rounded-lg text-sm font-medium"
                            :class="mode === 'percent' ? 'bg-white shadow-sm text-stone-900' : 'text-stone-500'"
                        >Percent (%)</button>
                        <button type="button" @click="mode = 'amount'" class="h-10 rounded-lg text-sm font-medium"
                            :class="mode === 'amount' ? 'bg-white shadow-sm text-stone-900' : 'text-stone-500'"
                        >Amount ($)</button>
                    </div>

                    <div class="flex items-baseline gap-2 mb-4">
                        <span v-if="mode === 'amount'" class="text-stone-400 text-xl">$</span>
                        <div class="font-serif text-4xl tabular-nums">{{ value === '' ? '0' : value }}</div>
                        <span v-if="mode === 'percent'" class="text-stone-400 text-xl">%</span>
                    </div>

                    <NumPad v-model:value="value" />

                    <div class="flex gap-3 mt-5">
                        <button v-if="initial" type="button" @click="clear" class="px-4 h-12 bg-rose-50 text-rose-700 border border-rose-200 rounded-xl font-medium hover:bg-rose-100">Remove</button>
                        <button type="button" @click="close" class="flex-1 h-12 bg-white border border-stone-200 rounded-xl font-medium text-stone-700 hover:bg-stone-50">Cancel</button>
                        <button type="button" @click="apply" :disabled="value === ''" class="flex-1 h-12 bg-stone-900 hover:bg-stone-800 text-white rounded-xl font-semibold disabled:opacity-50">Apply</button>
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
