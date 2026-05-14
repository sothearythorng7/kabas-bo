<script setup>
const props = defineProps({
    value: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
    allowDecimal: { type: Boolean, default: true },
});

const emit = defineEmits(['update:value']);

function press(char) {
    if (props.disabled) return;
    if (char === '.' && (!props.allowDecimal || props.value.includes('.'))) return;
    if (char === '.' && props.value === '') {
        emit('update:value', '0.');
        return;
    }
    // Cap decimals to 2 places
    if (props.value.includes('.') && props.value.split('.')[1].length >= 2 && char !== '.') return;
    emit('update:value', props.value + char);
}

function backspace() {
    if (props.disabled || props.value.length === 0) return;
    emit('update:value', props.value.slice(0, -1));
}

function clear() {
    emit('update:value', '');
}
</script>

<template>
    <div class="grid grid-cols-3 gap-2 w-full">
        <button
            v-for="digit in [1,2,3,4,5,6,7,8,9]"
            :key="digit"
            type="button"
            :disabled="disabled"
            @click="press(String(digit))"
            class="h-14 bg-white border border-stone-200 rounded-xl text-xl font-semibold hover:bg-stone-50 active:bg-stone-100 disabled:opacity-40"
        >{{ digit }}</button>

        <button
            type="button"
            :disabled="disabled || !allowDecimal"
            @click="press('.')"
            class="h-14 bg-white border border-stone-200 rounded-xl text-xl font-semibold hover:bg-stone-50 disabled:opacity-40"
        >.</button>

        <button
            type="button"
            :disabled="disabled"
            @click="press('0')"
            class="h-14 bg-white border border-stone-200 rounded-xl text-xl font-semibold hover:bg-stone-50 active:bg-stone-100 disabled:opacity-40"
        >0</button>

        <button
            type="button"
            :disabled="disabled || value.length === 0"
            @click="backspace"
            class="h-14 bg-stone-100 border border-stone-200 rounded-xl flex items-center justify-center text-stone-600 hover:bg-stone-200 disabled:opacity-40"
            aria-label="Backspace"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M21 4H8l-7 8 7 8h13a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"/>
                <line x1="18" y1="9" x2="12" y2="15"/>
                <line x1="12" y1="9" x2="18" y2="15"/>
            </svg>
        </button>
    </div>
</template>
