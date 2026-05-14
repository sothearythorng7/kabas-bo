<script setup>
import ProductCard from './ProductCard.vue';

const props = defineProps({
    items: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
});

const emit = defineEmits(['add']);
</script>

<template>
    <div>
        <div v-if="loading" class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-5 gap-3">
            <div v-for="n in 10" :key="n" class="bg-white rounded-2xl border border-stone-200/60 overflow-hidden">
                <div class="aspect-square bg-stone-100 animate-pulse"></div>
                <div class="p-3 space-y-2">
                    <div class="h-2.5 bg-stone-100 rounded animate-pulse w-1/3"></div>
                    <div class="h-3 bg-stone-100 rounded animate-pulse"></div>
                    <div class="h-3 bg-stone-100 rounded animate-pulse w-2/3"></div>
                </div>
            </div>
        </div>

        <div v-else-if="items.length === 0" class="py-16 text-center text-stone-400">
            <svg class="w-10 h-10 mx-auto mb-2 text-stone-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <p class="text-sm">No products to show</p>
        </div>

        <div v-else class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-5 gap-3">
            <ProductCard
                v-for="item in items"
                :key="item.id"
                :item="item"
                @add="emit('add', $event)"
            />
        </div>
    </div>
</template>
