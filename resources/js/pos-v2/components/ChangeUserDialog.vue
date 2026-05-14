<script setup>
import { ref, computed, watch } from 'vue';
import { useSessionStore } from '../stores/session.js';
import { useI18nStore } from '../stores/i18n.js';
import { changeShiftUser } from '../api/endpoints/shifts.js';
import { db } from '../db/index.js';
import PinPad from './PinPad.vue';

const props = defineProps({
    open: { type: Boolean, default: false },
});
const emit = defineEmits(['close', 'switched']);

const session = useSessionStore();
const i18n = useI18nStore();

const pin = ref('');
const status = ref('idle'); // idle | submitting | error
const errorMessage = ref('');

const t = computed(() => i18n.t);

watch(() => props.open, (v) => {
    if (v) {
        pin.value = '';
        status.value = 'idle';
        errorMessage.value = '';
    }
});

async function submit(value) {
    if (status.value === 'submitting') return;
    status.value = 'submitting';
    errorMessage.value = '';

    const match = await db.table('users').where('pin_code').equals(value).first();
    if (!match) {
        status.value = 'error';
        errorMessage.value = t.value('invalidPin');
        setTimeout(() => {
            pin.value = '';
            status.value = 'idle';
        }, 700);
        return;
    }

    if (match.id === session.currentUser?.id) {
        // Same user — no-op
        emit('close');
        return;
    }

    try {
        const res = await changeShiftUser({
            shift_id: session.currentShift.id,
            old_user_id: session.currentUser.id,
            new_user_id: match.id,
        });
        if (res && (res.success === true || res.shift)) {
            session.currentUser = match;
            if (res.shift) session.setShift(res.shift);
            emit('switched', match);
            emit('close');
        } else {
            throw new Error('Unexpected response');
        }
    } catch (err) {
        console.error('[POS V2] changeShiftUser failed', err);
        status.value = 'error';
        errorMessage.value = err.message || 'Switch failed';
        setTimeout(() => {
            pin.value = '';
            status.value = 'idle';
        }, 700);
    }
}

function close() {
    if (status.value === 'submitting') return;
    emit('close');
}
</script>

<template>
    <Teleport to="body">
        <Transition name="dialog">
            <div v-if="open" class="fixed inset-0 z-40 flex items-center justify-center p-4 bg-stone-900/40 backdrop-blur-sm" @click.self="close">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-serif text-2xl">{{ t('switchCashier') }}</h3>
                        <button type="button" @click="close" class="text-stone-400 hover:text-stone-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        </button>
                    </div>

                    <div class="text-center mb-4">
                        <div class="text-sm font-medium text-stone-700">{{ t('enterPin') }}</div>
                        <div v-if="errorMessage" class="text-xs text-rose-600 mt-1">{{ errorMessage }}</div>
                        <div v-else class="text-xs text-transparent mt-1">·</div>
                    </div>

                    <PinPad
                        v-model:value="pin"
                        :length="6"
                        :disabled="status === 'submitting'"
                        :error="status === 'error'"
                        @submit="submit"
                    />
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.dialog-enter-active, .dialog-leave-active { transition: opacity 0.15s ease; }
.dialog-enter-from, .dialog-leave-to { opacity: 0; }
</style>
