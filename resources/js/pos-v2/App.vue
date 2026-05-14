<script setup>
import { onMounted, ref } from 'vue';
import { useSessionStore } from './stores/session.js';

const session = useSessionStore();
const buildId = ref(import.meta.env.MODE);

onMounted(() => {
    session.detectOnline();
});
</script>

<template>
    <div class="min-h-screen flex flex-col bg-stone-50">
        <RouterView />
    </div>
</template>
