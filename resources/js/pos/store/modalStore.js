import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useModalStore = defineStore('modal', () => {
  const shiftStartVisible = ref(false)
  const shiftEndVisible = ref(false)

  const showShiftStart = () => { shiftStartVisible.value = true }
  const hideShiftStart = () => { shiftStartVisible.value = false }

  const showShiftEnd = () => { shiftEndVisible.value = true }
  const hideShiftEnd = () => { shiftEndVisible.value = false }

  return {
    shiftStartVisible,
    shiftEndVisible,
    showShiftStart,
    hideShiftStart,
    showShiftEnd,
    hideShiftEnd,
  }
})
