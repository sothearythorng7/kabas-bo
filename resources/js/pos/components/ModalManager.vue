<template>
  <div>
    <!-- Modals are defined in ShiftStart.vue and ShiftEnd.vue -->
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import bootstrap from 'bootstrap/dist/js/bootstrap.bundle.min.js';

const shiftStartModal = ref(null);
const shiftEndModal = ref(null);
let startInstance = null;
let endInstance = null;

onMounted(() => {
  shiftStartModal.value = document.getElementById('shiftStartModal');
  shiftEndModal.value = document.getElementById('shiftEndModal');
});

export function openShiftStart() {
  if (!startInstance) startInstance = new bootstrap.Modal(shiftStartModal.value, { backdrop: 'static', keyboard: false });
  startInstance.show();
}

export function closeShiftStart() {
  startInstance?.hide();
}

export function openShiftEnd() {
  if (!endInstance) endInstance = new bootstrap.Modal(shiftEndModal.value, { backdrop: 'static', keyboard: false });
  endInstance.show();
}

export function closeShiftEnd() {
  endInstance?.hide();
}
</script>
