<script setup>
import { computed } from 'vue';
import { useI18nStore } from '../../stores/i18n.js';

const props = defineProps({
    // Children of the current node (siblings to show as chips)
    categories: { type: Array, default: () => [] },
    selectedId: { type: [Number, String, null], default: null },
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
    <div v-if="categories.length" class="flex items-center gap-2 overflow-x-auto scrollbar-thin">
        <button
            type="button"
            @click="emit('select', null)"
            class="flex-shrink-0 px-3.5 py-1.5 rounded-full text-[12px] font-medium transition-colors"
            :class="selectedId === null
                ? 'bg-stone-900 text-white'
                : 'bg-white border border-stone-200 text-stone-700 hover:bg-stone-50'"
        >All</button>
        <button
            v-for="cat in categories"
            :key="cat.id"
            type="button"
            @click="emit('select', cat)"
            class="flex-shrink-0 px-3.5 py-1.5 rounded-full text-[12px] font-medium transition-colors"
            :class="selectedId === cat.id
                ? 'bg-stone-900 text-white'
                : 'bg-white border border-stone-200 text-stone-700 hover:bg-stone-50'"
        >{{ label(cat) }}</button>
    </div>
</template>
