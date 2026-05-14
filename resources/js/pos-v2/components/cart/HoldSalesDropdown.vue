<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue';
import { useCartStore } from '../../stores/cart.js';
import { useSessionStore } from '../../stores/session.js';

const cart = useCartStore();
const session = useSessionStore();

const open = ref(false);
const held = ref([]);
const rootRef = ref(null);

const count = computed(() => held.value.length);

async function refresh() {
    if (!session.currentShift?.id) {
        held.value = [];
        return;
    }
    held.value = await cart.listHeld(session.currentShift.id);
}

async function toggle() {
    open.value = !open.value;
    if (open.value) {
        await refresh();
        setTimeout(() => document.addEventListener('mousedown', onDocClick), 0);
    } else {
        document.removeEventListener('mousedown', onDocClick);
    }
}

function onDocClick(ev) {
    if (rootRef.value && !rootRef.value.contains(ev.target)) {
        open.value = false;
        document.removeEventListener('mousedown', onDocClick);
    }
}

async function resume(id) {
    await cart.resumeHeld(id);
    open.value = false;
    document.removeEventListener('mousedown', onDocClick);
}

async function discard(id) {
    await cart.discardHeld(id);
    await refresh();
}

watch(() => session.currentShift?.id, refresh, { immediate: true });

onBeforeUnmount(() => document.removeEventListener('mousedown', onDocClick));

function fmt(v) {
    return `$${(Math.round((v || 0) * 100) / 100).toFixed(2)}`;
}

function itemCount(sale) {
    return (sale.items || []).reduce((s, it) => s + (Number(it.quantity) || 0), 0);
}
</script>

<template>
    <div ref="rootRef" v-if="count > 0" class="relative">
        <button
            type="button"
            @click="toggle"
            class="flex items-center gap-1.5 px-3 py-1 bg-amber-50 text-amber-800 border border-amber-200 rounded-full text-xs font-medium hover:bg-amber-100"
        >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
            <span>{{ count }} held</span>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
        </button>

        <div v-if="open" class="absolute right-0 top-full mt-1 w-72 bg-white border border-stone-200 rounded-xl shadow-lg z-20 py-1">
            <div v-for="sale in held" :key="sale.id" class="px-3 py-2 hover:bg-stone-50 flex items-center gap-3">
                <div class="flex-1 min-w-0">
                    <div class="text-[13px] font-medium tabular-nums">{{ fmt(sale.total) }}</div>
                    <div class="text-[11px] text-stone-500">{{ itemCount(sale) }} items · held {{ new Date(sale.held_at || sale.updated_at || sale.created_at).toLocaleTimeString() }}</div>
                </div>
                <button @click="resume(sale.id)" class="text-[11px] font-medium bg-stone-900 text-white px-2.5 py-1 rounded-md hover:bg-stone-800">Resume</button>
                <button @click="discard(sale.id)" class="text-stone-400 hover:text-rose-500" title="Discard">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2L5 6"/></svg>
                </button>
            </div>
        </div>
    </div>
</template>
