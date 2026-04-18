<template>
  <div class="login-container">
    <h2>Connexion</h2>
    <input type="password" v-model="pin" class="form-control" placeholder="PIN" :disabled="syncing" />
    <button class="btn btn-primary mt-2" @click="login" :disabled="syncing">
      <span v-if="syncing" class="spinner-border spinner-border-sm me-1"></span>
      {{ syncing ? syncMessage : 'Se connecter' }}
    </button>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useUserStore } from '../store/User.js';
import { openShiftStart } from './ModalManager.vue';
import { useRouter } from 'vue-router';

const pin = ref('');
const syncing = ref(false);
const syncMessage = ref('');
const store = useUserStore();
const router = useRouter();

async function login() {
  syncing.value = true;
  syncMessage.value = 'Connexion...';

  const ok = await store.verifyPin(pin.value);
  if (!ok) {
    syncing.value = false;
    alert('PIN invalide');
    return;
  }

  syncMessage.value = 'Mise à jour du catalogue...';
  await store.refreshCatalog();
  syncMessage.value = 'Sync events...';
  await store.refreshEvents();
  syncing.value = false;

  if (!store.activeShift) {
    openShiftStart();
  } else {
    router.push('/products');
  }
}
</script>
