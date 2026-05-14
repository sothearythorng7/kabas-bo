<script setup>
import { ref, watch } from 'vue';
import NumPad from '../NumPad.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
});
const emit = defineEmits(['close', 'submit']);

const address = ref('');
const fee = ref('');

watch(() => props.open, (v) => {
    if (v) {
        address.value = '';
        fee.value = '';
    }
});

function submit() {
    const parsed = parseFloat(fee.value);
    if (!address.value.trim() || isNaN(parsed) || parsed < 0) return;
    emit('submit', { address: address.value.trim(), fee: parsed });
}
</script>

<template>
    <Teleport to="body">
        <Transition name="dialog">
            <div v-if="open" class="fixed inset-0 z-40 flex items-center justify-center p-4 bg-stone-900/40 backdrop-blur-sm" @click.self="emit('close')">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-serif text-2xl">Delivery</h3>
                        <button @click="emit('close')" class="text-stone-400 hover:text-stone-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>

                    <label class="block text-sm font-medium text-stone-700 mb-2">Address</label>
                    <textarea
                        v-model="address"
                        rows="3"
                        placeholder="e.g. House #42, St. 240, Phnom Penh"
                        class="w-full bg-stone-50 border border-stone-200 rounded-xl px-4 py-3 text-[15px] mb-4 focus:border-stone-400 focus:outline-none focus:ring-4 focus:ring-stone-200/50 resize-none"
                    ></textarea>

                    <label class="block text-sm font-medium text-stone-700 mb-2">Fee</label>
                    <div class="flex items-baseline gap-2 mb-3">
                        <span class="text-stone-400 text-xl">$</span>
                        <div class="font-serif text-3xl tabular-nums">{{ fee === '' ? '0.00' : fee }}</div>
                    </div>
                    <NumPad v-model:value="fee" />

                    <div class="flex gap-3 mt-5">
                        <button type="button" @click="emit('close')" class="flex-1 h-12 bg-white border border-stone-200 rounded-xl font-medium text-stone-700 hover:bg-stone-50">Cancel</button>
                        <button type="button" @click="submit" :disabled="!address.trim() || fee === ''" class="flex-1 h-12 bg-stone-900 hover:bg-stone-800 text-white rounded-xl font-semibold disabled:opacity-50">Add to cart</button>
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
