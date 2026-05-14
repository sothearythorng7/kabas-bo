<script setup>
import { computed } from 'vue';
import { useI18nStore } from '../../stores/i18n.js';
import { useCartStore } from '../../stores/cart.js';
import { lineTotal } from '../../composables/useCartCalculations.js';

const props = defineProps({
    item: { type: Object, required: true },
});

const emit = defineEmits(['discount']);

const i18n = useI18nStore();
const cart = useCartStore();

const locale = computed(() => i18n.locale);

const itemName = computed(() => {
    const n = props.item.name;
    if (!n) return '';
    if (typeof n === 'string') return n;
    return n[locale.value] || n.en || n.fr || '';
});

const variant = computed(() => {
    if (props.item.is_delivery || props.item.type === 'delivery') return 'delivery';
    if (props.item.is_custom_service || props.item.type === 'service') return 'service';
    if (props.item.type === 'gift_card') return 'gift_card';
    return 'product';
});

const photoUrl = computed(() => {
    const photos = props.item.photos;
    if (!Array.isArray(photos) || photos.length === 0) return null;
    const first = photos[0];
    if (typeof first === 'string') return first;
    return first?.url || first?.path || null;
});

const hasLineDiscount = computed(() => Array.isArray(props.item.discounts) && props.item.discounts.length > 0);

const lineSubtotalLabel = computed(() => {
    const sub = Number(props.item.price || 0) * Number(props.item.quantity || 0);
    return `$${sub.toFixed(2)}`;
});

const lineTotalLabel = computed(() => `$${lineTotal(props.item).toFixed(2)}`);

const discountSummary = computed(() => {
    if (!hasLineDiscount.value) return null;
    const d = props.item.discounts[0];
    if (d.type === 'percent') return `−${d.value}%`;
    return `−$${Number(d.value).toFixed(2)}`;
});

function inc() {
    cart.incrementQuantity(props.item.line_id);
}
function dec() {
    cart.decrementQuantity(props.item.line_id);
}
function remove() {
    cart.removeLine(props.item.line_id);
}
</script>

<template>
    <!-- DELIVERY -->
    <div v-if="variant === 'delivery'" class="bg-sky-50/60 border border-sky-200/60 rounded-xl p-3">
        <div class="flex items-start gap-3">
            <div class="w-12 h-12 bg-sky-100 rounded-lg flex-shrink-0 flex items-center justify-center text-sky-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <span class="text-[10px] uppercase tracking-wider font-semibold text-sky-800 bg-sky-100 px-1.5 py-0.5 rounded">Delivery</span>
                <div class="text-[13px] font-medium leading-tight mt-1">{{ item.delivery_address || 'Local courier' }}</div>
            </div>
            <button @click="remove" class="text-stone-400 hover:text-rose-500 -mt-1 -mr-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="flex items-center justify-between mt-2.5">
            <span class="text-[11px] text-stone-500">Qty 1</span>
            <div class="text-[14px] font-semibold">{{ lineTotalLabel }}</div>
        </div>
    </div>

    <!-- CUSTOM SERVICE -->
    <div v-else-if="variant === 'service'" class="bg-amber-50/60 border border-amber-200/60 rounded-xl p-3">
        <div class="flex items-start gap-3">
            <div class="w-12 h-12 bg-amber-100 rounded-lg flex-shrink-0 flex items-center justify-center text-amber-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <span class="text-[10px] uppercase tracking-wider font-semibold text-amber-800 bg-amber-100 px-1.5 py-0.5 rounded">Service</span>
                <div class="text-[13px] font-medium leading-tight mt-1">{{ item.custom_service_description || 'Custom service' }}</div>
            </div>
            <button @click="remove" class="text-stone-400 hover:text-rose-500 -mt-1 -mr-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="flex items-center justify-between mt-2.5">
            <span class="text-[11px] text-stone-500">Qty 1 · custom</span>
            <div class="text-[14px] font-semibold">{{ lineTotalLabel }}</div>
        </div>
    </div>

    <!-- GIFT CARD -->
    <div v-else-if="variant === 'gift_card'" class="bg-gradient-to-br from-stone-900 to-stone-700 text-white rounded-xl p-3">
        <div class="flex items-start gap-3">
            <div class="w-12 h-12 bg-white/10 rounded-lg flex-shrink-0 flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><line x1="12" y1="22" x2="12" y2="7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7zM12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/></svg>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-[13px] font-medium leading-tight">{{ itemName }}</div>
                <div class="text-[10px] font-mono opacity-80 mt-1 tracking-wider">{{ item.generated_code }}</div>
            </div>
            <button @click="remove" class="text-white/60 hover:text-white -mt-1 -mr-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="flex items-center justify-between mt-2.5">
            <span class="text-[11px] opacity-70">Qty 1 · code auto-generated</span>
            <div class="text-[14px] font-semibold">{{ lineTotalLabel }}</div>
        </div>
    </div>

    <!-- PRODUCT (default) -->
    <div v-else class="bg-stone-50/80 border border-stone-200/60 rounded-xl p-3">
        <div class="flex items-start gap-3">
            <div class="w-12 h-12 bg-stone-200 rounded-lg flex-shrink-0 overflow-hidden">
                <img v-if="photoUrl" :src="photoUrl" :alt="itemName" class="w-full h-full object-cover">
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-[13px] font-medium leading-tight">{{ itemName }}</div>
                <div class="text-[11px] text-stone-500 mt-0.5">EAN {{ item.ean || '—' }}</div>
            </div>
            <button @click="remove" class="text-stone-400 hover:text-rose-500 -mt-1 -mr-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div class="flex items-center justify-between mt-2.5">
            <div class="inline-flex items-center bg-white border border-stone-200 rounded-lg">
                <button @click="dec" class="w-8 h-8 flex items-center justify-center text-stone-600 hover:bg-stone-100 rounded-l-lg">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
                <span class="w-9 text-center text-[14px] font-semibold tabular-nums">{{ item.quantity }}</span>
                <button @click="inc" class="w-8 h-8 flex items-center justify-center text-stone-600 hover:bg-stone-100 rounded-r-lg">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                </button>
            </div>
            <div class="text-right">
                <div class="flex items-baseline gap-1.5">
                    <span v-if="hasLineDiscount" class="text-[11px] text-stone-400 line-through">{{ lineSubtotalLabel }}</span>
                    <span class="text-[14px] font-semibold">{{ lineTotalLabel }}</span>
                </div>
                <button @click="emit('discount', item)" class="text-[10px] font-medium hover:underline mt-0.5 flex items-center gap-0.5"
                    :class="hasLineDiscount ? 'text-amber-700' : 'text-stone-400'">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>
                    {{ hasLineDiscount ? discountSummary : 'Add discount' }}
                </button>
            </div>
        </div>
    </div>
</template>
