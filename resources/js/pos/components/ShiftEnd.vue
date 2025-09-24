<template>
  <div class="modal fade" id="shiftEndModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Clôture du shift</h5>
        </div>
        <div class="modal-body">
          <label>Cash en caisse:</label>
          <input type="number" v-model="cash" class="form-control" />
        </div>
        <div class="modal-footer">
          <button class="btn btn-primary" @click="endShiftHandler">Clôturer</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useUserStore } from '../store/User.js';
import { closeShift, syncShifts } from '../db.js';
import { closeShiftEnd } from './ModalManager.vue';

const cash = ref(0);
const store = useUserStore();

async function endShiftHandler() {
  if (!store.activeShift) return;
  await closeShift(store.activeShift.id, cash.value);
  await syncShifts();
  store.setActiveShift(null);
  closeShiftEnd();
}
</script>
