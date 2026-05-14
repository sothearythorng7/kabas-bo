<script setup>
import { ref } from 'vue';

const pin = ref('');
const status = ref('Scaffolding placeholder — Phase 2 implements PIN validation against local users table.');
</script>

<template>
    <div class="flex-1 flex items-center justify-center p-6">
        <div class="w-full max-w-md bg-white rounded-3xl shadow-sm border border-stone-200 p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 bg-stone-900 rounded-2xl flex items-center justify-center text-white text-2xl font-semibold">K</div>
                <div>
                    <div class="text-xl font-semibold tracking-tight">Kabas POS</div>
                    <div class="text-xs text-stone-500">V2 · BKK 1 · Aeon Mall</div>
                </div>
            </div>

            <label class="block text-sm font-medium text-stone-700 mb-2">PIN</label>
            <input
                v-model="pin"
                type="password"
                inputmode="numeric"
                maxlength="6"
                placeholder="••••••"
                class="w-full h-14 text-center text-2xl tracking-[0.5em] font-semibold bg-stone-50 border border-stone-200 rounded-xl focus:border-stone-400 focus:outline-none focus:ring-4 focus:ring-stone-200/50"
            />

            <button class="w-full mt-4 h-12 bg-stone-900 hover:bg-stone-800 text-white rounded-xl font-medium">
                Sign in
            </button>

            <p class="text-xs text-stone-500 mt-6 text-center">{{ status }}</p>
        </div>
    </div>
</template>
