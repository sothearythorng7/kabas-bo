<script setup>
import { useSessionStore } from '../stores/session.js';

const session = useSessionStore();
</script>

<template>
    <div class="flex-1 flex items-center justify-center p-6">
        <div class="text-center">
            <div class="font-serif text-4xl mb-2">Dashboard</div>
            <p class="text-stone-600 max-w-md mx-auto">
                Phase 3 will mount the catalog + cart layout shown in the
                <a class="underline" href="/pos-v2-dashboard-mockup.html">approved mockup</a>.
            </p>
            <div class="mt-6 text-xs text-stone-400">
                online: {{ session.isOnline ? 'yes' : 'no' }}
            </div>
        </div>
    </div>
</template>
