<template>
  <div class="modal-header">
    <h5 class="modal-title">Démarrer un shift</h5>
  </div>
  <div class="modal-body">
    <div class="mb-3">
      <label class="form-label">Montant en caisse</label>
      <input v-model="cash" type="number" class="form-control" />
    </div>
    <div v-if="syncing" class="alert alert-info">Synchronisation en cours...</div>
  </div>
  <div class="modal-footer">
    <button class="btn btn-primary" @click="startShift" :disabled="syncing">Démarrer</button>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useUserStore } from '../store/user'
import { db, syncShifts } from '../db.js'
import { hideShiftStart } from './ModalManager.vue'

const userStore = useUserStore()
const cash = ref(0)
const syncing = ref(false)

async function startShift() {
  if (!userStore.user) return
  syncing.value = true

  const id = await db.shifts.add({
    user_id: userStore.user.id,
    store_id: userStore.user.store_id,
    cash_start: Number(cash.value),
    started_at: new Date().toISOString(),
    ended_at: null,
    synced: 0,
  })

  userStore.activeShift = await db.shifts.get(id)

  // Synchronisation bloquante
  await syncShifts()

  syncing.value = false
  hideShiftStart()
}
</script>
