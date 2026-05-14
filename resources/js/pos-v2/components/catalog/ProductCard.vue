<script setup>
import { computed } from 'vue';
import { useI18nStore } from '../../stores/i18n.js';

const props = defineProps({
    item: { type: Object, required: true },
});

const emit = defineEmits(['add']);

const i18n = useI18nStore();
const locale = computed(() => i18n.locale);

const isGiftCard = computed(() => props.item.type === 'gift_card');
const isGiftBox = computed(() => props.item.type === 'gift_box');

const name = computed(() => {
    const n = props.item.name;
    if (!n) return '';
    if (typeof n === 'string') return n;
    return n[locale.value] || n.en || n.fr || '';
});

const brandName = computed(() => {
    const b = props.item.brand;
    if (!b) return '';
    if (typeof b === 'string') return b;
    return b.name || b.en || b.fr || '';
});

const photoUrl = computed(() => {
    const photos = props.item.photos;
    if (!Array.isArray(photos) || photos.length === 0) return null;
    const first = photos[0];
    if (typeof first === 'string') return first;
    return first?.url || first?.path || null;
});

const stockLabel = computed(() => {
    const s = props.item.total_stock;
    if (s == null) return null;
    if (s <= 0) return { text: 'Out', tone: 'rose' };
    if (s <= 3) return { text: `${s} left`, tone: 'amber' };
    return { text: `${s} left`, tone: 'emerald' };
});

const priceLabel = computed(() => {
    const p = Number(props.item.price || 0);
    return `$${p.toFixed(2)}`;
});

function handleClick() {
    emit('add', props.item);
}
</script>

<template>
    <button
        type="button"
        @click="handleClick"
        class="product-card text-left bg-white rounded-2xl border overflow-hidden transition-all hover:-translate-y-0.5 hover:shadow-md"
        :class="isGiftBox ? 'border-amber-300/60' : 'border-stone-200/60'"
    >
        <!-- Image area -->
        <div
            v-if="isGiftCard"
            class="aspect-square bg-gradient-to-br from-stone-900 to-stone-700 flex flex-col items-center justify-center text-white p-4"
        >
            <svg class="w-12 h-12 mb-2 opacity-90" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7zM12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>
            <div class="text-[10px] uppercase tracking-widest opacity-70">Gift Card</div>
            <div class="font-serif text-2xl mt-1">{{ priceLabel }}</div>
        </div>
        <div
            v-else
            class="aspect-square bg-stone-100 relative overflow-hidden"
        >
            <img v-if="photoUrl" :src="photoUrl" :alt="name" class="absolute inset-0 w-full h-full object-cover" loading="lazy">
            <div v-else class="absolute inset-0 flex items-center justify-center text-stone-300">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><polyline points="21 15 16 10 5 21"/></svg>
            </div>
            <span
                v-if="isGiftBox"
                class="absolute top-2 left-2 flex items-center gap-1 text-[10px] font-semibold uppercase tracking-wider text-amber-800 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200"
            >
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/></svg>
                Gift Box
            </span>
        </div>

        <!-- Info -->
        <div v-if="!isGiftCard" class="p-3">
            <div v-if="brandName" class="text-[10px] text-stone-500 uppercase tracking-wide font-medium">{{ brandName }}</div>
            <div class="text-[13px] font-medium leading-tight line-clamp-2 mt-0.5 h-9">{{ name }}</div>
            <div class="flex items-baseline justify-between mt-2">
                <span class="text-[15px] font-semibold">{{ priceLabel }}</span>
                <span
                    v-if="stockLabel"
                    class="text-[11px] font-medium"
                    :class="{
                        'text-emerald-700': stockLabel.tone === 'emerald',
                        'text-amber-700': stockLabel.tone === 'amber',
                        'text-rose-700': stockLabel.tone === 'rose',
                    }"
                >{{ stockLabel.text }}</span>
            </div>
        </div>
    </button>
</template>
