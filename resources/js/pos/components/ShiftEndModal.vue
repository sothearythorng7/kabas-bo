<template>
  <div class="modal show d-block" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Clôturer le shift</h5>
        </div>
        <div class="modal-body">
          <input type="number" v-model.number="cash" class="form-control" placeholder="Montant cash en caisse">
        </div>
        <div class="modal-footer">
          <button class="btn btn-danger" @click="endShift">Clôturer</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
const props = defineProps({ shift: Object })
const emit = defineEmits(['end'])
const cash = ref(0)

watch(() => props.shift, (s) => {
  if (s && s.cash_start) cash.value = s.cash_start
})

function endShift() {
  emit('end', cash.value)
}
</script>
