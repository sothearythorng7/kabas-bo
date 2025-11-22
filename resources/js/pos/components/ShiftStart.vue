<template>
  <div class="modal fade" id="shiftStartModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Démarrage du shift</h5>
        </div>
        <div class="modal-body">
          <label class="form-label fw-bold mb-3">Cash en caisse ($):</label>
          <VirtualKeypad v-model="cash" :allow-decimal="true" placeholder="0.00" />
        </div>
        <div class="modal-footer">
          <button class="btn btn-primary" @click="startShiftHandler">Démarrer</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useUserStore } from '../store/User.js';
import { syncShifts } from '../db.js';
import { closeShiftStart } from './ModalManager.vue';
import VirtualKeypad from './VirtualKeypad.vue';

const cash = ref(0);
const store = useUserStore();

async function startShiftHandler() {
  if (!store.user) return;
  // démarrer le shift
  await store.startShift(cash.value);
  // synchronisation bloquante
  await syncShifts();
  closeShiftStart();
}
</script>
