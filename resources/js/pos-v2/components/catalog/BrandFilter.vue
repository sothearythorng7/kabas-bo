<script setup>
import { ref, computed, onBeforeUnmount } from 'vue';

const props = defineProps({
    brands: { type: Array, default: () => [] }, // [{ name }]
    modelValue: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue']);

const open = ref(false);
const rootRef = ref(null);

const label = computed(() => props.modelValue || 'Brand: All');

function select(name) {
    emit('update:modelValue', name);
    open.value = false;
}

function handleDocClick(ev) {
    if (!open.value) return;
    if (rootRef.value && !rootRef.value.contains(ev.target)) {
        open.value = false;
    }
}

function toggle() {
    open.value = !open.value;
    if (open.value) {
        setTimeout(() => document.addEventListener('mousedown', handleDocClick), 0);
    } else {
        document.removeEventListener('mousedown', handleDocClick);
    }
}

onBeforeUnmount(() => document.removeEventListener('mousedown', handleDocClick));
</script>

<template>
    <div ref="rootRef" class="relative">
        <button
            type="button"
            @click="toggle"
            class="flex items-center gap-1.5 px-3 py-1.5 bg-white border border-stone-200 rounded-lg text-[12px] font-medium text-stone-700 hover:bg-stone-50"
        >
            <span>{{ label }}</span>
            <svg class="w-3.5 h-3.5 text-stone-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
        </button>

        <div
            v-if="open"
            class="absolute right-0 top-full mt-1 w-56 max-h-80 overflow-y-auto bg-white border border-stone-200 rounded-xl shadow-lg z-20 py-1"
        >
            <button
                type="button"
                @click="select('')"
                class="w-full text-left px-3 py-2 text-sm hover:bg-stone-50"
                :class="modelValue === '' ? 'text-stone-900 font-semibold' : 'text-stone-700'"
            >All brands</button>
            <button
                v-for="b in brands"
                :key="b.name"
                type="button"
                @click="select(b.name)"
                class="w-full text-left px-3 py-2 text-sm hover:bg-stone-50"
                :class="modelValue === b.name ? 'text-stone-900 font-semibold' : 'text-stone-700'"
            >{{ b.name }}</button>
        </div>
    </div>
</template>
