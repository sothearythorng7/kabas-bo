<script setup>
import { computed } from 'vue';

const props = defineProps({
    value: { type: String, default: '' },
    length: { type: Number, default: 6 },
    disabled: { type: Boolean, default: false },
    error: { type: Boolean, default: false },
});

const emit = defineEmits(['update:value', 'submit']);

const dots = computed(() => {
    return Array.from({ length: props.length }, (_, i) => i < props.value.length);
});

function press(digit) {
    if (props.disabled || props.value.length >= props.length) return;
    const next = props.value + digit;
    emit('update:value', next);
    if (next.length === props.length) {
        emit('submit', next);
    }
}

function backspace() {
    if (props.disabled || props.value.length === 0) return;
    emit('update:value', props.value.slice(0, -1));
}

function clear() {
    if (props.disabled) return;
    emit('update:value', '');
}
</script>

<template>
    <div class="flex flex-col items-center gap-6">
        <div class="flex gap-3" :class="{ 'animate-shake': error }">
            <div
                v-for="(filled, i) in dots"
                :key="i"
                class="w-4 h-4 rounded-full transition-colors duration-150"
                :class="error
                    ? 'bg-rose-500'
                    : (filled ? 'bg-stone-900' : 'bg-stone-200')"
            ></div>
        </div>

        <div class="grid grid-cols-3 gap-3 w-full max-w-xs">
            <button
                v-for="digit in [1,2,3,4,5,6,7,8,9]"
                :key="digit"
                type="button"
                :disabled="disabled"
                @click="press(String(digit))"
                class="h-16 bg-white border border-stone-200 rounded-2xl text-2xl font-semibold text-stone-800 hover:bg-stone-50 active:bg-stone-100 disabled:opacity-40 disabled:cursor-not-allowed"
            >{{ digit }}</button>

            <button
                type="button"
                :disabled="disabled || value.length === 0"
                @click="clear"
                class="h-16 bg-stone-100 border border-stone-200 rounded-2xl text-xs font-medium text-stone-600 hover:bg-stone-200 disabled:opacity-40"
            >Clear</button>

            <button
                type="button"
                :disabled="disabled"
                @click="press('0')"
                class="h-16 bg-white border border-stone-200 rounded-2xl text-2xl font-semibold text-stone-800 hover:bg-stone-50 active:bg-stone-100 disabled:opacity-40"
            >0</button>

            <button
                type="button"
                :disabled="disabled || value.length === 0"
                @click="backspace"
                class="h-16 bg-stone-100 border border-stone-200 rounded-2xl flex items-center justify-center text-stone-600 hover:bg-stone-200 disabled:opacity-40"
                aria-label="Backspace"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path d="M21 4H8l-7 8 7 8h13a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"/>
                    <line x1="18" y1="9" x2="12" y2="15"/>
                    <line x1="12" y1="9" x2="18" y2="15"/>
                </svg>
            </button>
        </div>
    </div>
</template>

<style scoped>
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    20%, 60% { transform: translateX(-6px); }
    40%, 80% { transform: translateX(6px); }
}
.animate-shake { animation: shake 0.35s cubic-bezier(0.36, 0.07, 0.19, 0.97); }
</style>
