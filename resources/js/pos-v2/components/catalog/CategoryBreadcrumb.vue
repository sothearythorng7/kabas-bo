<script setup>
import { computed } from 'vue';
import { useI18nStore } from '../../stores/i18n.js';

const props = defineProps({
    path: { type: Array, default: () => [] }, // [{ id, name }]
    productCount: { type: Number, default: 0 },
});

const emit = defineEmits(['select']);

const i18n = useI18nStore();
const locale = computed(() => i18n.locale);

function label(cat) {
    if (!cat) return '';
    if (typeof cat.name === 'string') return cat.name;
    return cat.name?.[locale.value] || cat.name?.en || cat.name?.fr || '';
}
</script>

<template>
    <nav class="flex items-center gap-1.5 text-[13px] text-stone-600">
        <button
            type="button"
            @click="emit('select', [])"
            class="hover:text-stone-900 font-medium"
        >All</button>
        <template v-for="(cat, i) in path" :key="cat.id">
            <svg class="w-3.5 h-3.5 text-stone-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 18 15 12 9 6"/></svg>
            <button
                type="button"
                @click="emit('select', path.slice(0, i + 1))"
                class="hover:text-stone-900"
                :class="i === path.length - 1 ? 'text-stone-900 font-semibold' : 'font-medium'"
            >{{ label(cat) }}</button>
        </template>
        <span v-if="productCount" class="ml-2 text-stone-400 text-[12px]">· {{ productCount }} products</span>
    </nav>
</template>
