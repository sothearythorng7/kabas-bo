<template>
  <div class="modal fade" id="shiftStartModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Démarrage du shift</h5>
        </div>
        <div class="modal-body">
          <!-- Popup Event selector -->
          <div v-if="events.length > 0" class="mb-3">
            <label class="form-label fw-bold">Popup Event (optionnel):</label>
            <select v-model="selectedEventId" class="form-select">
              <option :value="null">-- Aucun --</option>
              <option v-for="evt in events" :key="evt.id" :value="evt.id">
                {{ evt.name }} <span v-if="evt.location">- {{ evt.location }}</span>
              </option>
            </select>
          </div>

          <label class="form-label fw-bold mb-3">Cash en caisse ($):</label>
          <VirtualKeypad v-model="cash" :allow-decimal="true" placeholder="0.00000" />
        </div>
        <div class="modal-footer">
          <button class="btn btn-primary" @click="startShiftHandler">Démarrer</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useUserStore } from '../store/User.js';
import { db, syncShifts } from '../db.js';
import { closeShiftStart } from './ModalManager.vue';
import VirtualKeypad from './VirtualKeypad.vue';

const cash = ref(0);
const selectedEventId = ref(null);
const events = ref([]);
const store = useUserStore();

onMounted(async () => {
  try {
    const allEvents = await db.popup_events.toArray();
    events.value = allEvents;
  } catch {
    events.value = [];
  }
});

async function startShiftHandler() {
  if (!store.user) return;
  await store.startShift(cash.value, selectedEventId.value);
  await syncShifts();
  closeShiftStart();
}
</script>
