<template>
  <div class="login-container">
    <h2>Connexion</h2>
    <input type="password" v-model="pin" class="form-control" placeholder="PIN" />
    <button class="btn btn-primary mt-2" @click="login">Se connecter</button>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useUserStore } from '../store/User.js';
import { openShiftStart } from './ModalManager.vue';
import { useRouter } from 'vue-router';

const pin = ref('');
const store = useUserStore();
const router = useRouter();

async function login() {
  const ok = await store.verifyPin(pin.value);
  if (ok) {
    if (!store.activeShift) {
      openShiftStart();
    } else {
      router.push('/products');
    }
  } else {
    alert('PIN invalide');
  }
}
</script>
