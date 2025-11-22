<template>
  <div class="virtual-keypad">
    <!-- Display -->
    <div class="keypad-display mb-3">
      <input
        type="text"
        :value="displayValue"
        readonly
        class="form-control form-control-lg text-end"
        :placeholder="placeholder"
      />
    </div>

    <!-- Keypad buttons -->
    <div class="keypad-grid">
      <button type="button" @click="addDigit('7')" class="keypad-btn">7</button>
      <button type="button" @click="addDigit('8')" class="keypad-btn">8</button>
      <button type="button" @click="addDigit('9')" class="keypad-btn">9</button>

      <button type="button" @click="addDigit('4')" class="keypad-btn">4</button>
      <button type="button" @click="addDigit('5')" class="keypad-btn">5</button>
      <button type="button" @click="addDigit('6')" class="keypad-btn">6</button>

      <button type="button" @click="addDigit('1')" class="keypad-btn">1</button>
      <button type="button" @click="addDigit('2')" class="keypad-btn">2</button>
      <button type="button" @click="addDigit('3')" class="keypad-btn">3</button>

      <button type="button" @click="clearAll" class="keypad-btn btn-danger">C</button>
      <button type="button" @click="addDigit('0')" class="keypad-btn">0</button>
      <button type="button" v-if="allowDecimal" @click="addDigit('.')" class="keypad-btn">.</button>
      <button type="button" v-else @click="backspace" class="keypad-btn btn-warning">⌫</button>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  modelValue: {
    type: [Number, String],
    default: 0
  },
  allowDecimal: {
    type: Boolean,
    default: false
  },
  placeholder: {
    type: String,
    default: '0'
  }
});

const emit = defineEmits(['update:modelValue']);

const displayValue = ref('');

// Watch for external changes to modelValue
watch(() => props.modelValue, (newVal) => {
  if (newVal === 0 || newVal === null || newVal === '') {
    displayValue.value = '';
  } else {
    displayValue.value = String(newVal);
  }
}, { immediate: true });

function addDigit(digit) {
  // Prevent multiple decimals
  if (digit === '.' && displayValue.value.includes('.')) {
    return;
  }

  // Append digit
  displayValue.value = displayValue.value + digit;

  // Update model
  updateModel();
}

function backspace() {
  if (displayValue.value.length > 0) {
    displayValue.value = displayValue.value.slice(0, -1);
    updateModel();
  }
}

function clearAll() {
  displayValue.value = '';
  updateModel();
}

function updateModel() {
  if (displayValue.value === '' || displayValue.value === '.') {
    emit('update:modelValue', 0);
  } else {
    const numValue = props.allowDecimal
      ? parseFloat(displayValue.value)
      : parseInt(displayValue.value, 10);
    emit('update:modelValue', numValue);
  }
}
</script>

<style scoped>
.virtual-keypad {
  width: 100%;
  max-width: 400px;
  margin: 0 auto;
}

.keypad-display input {
  font-size: 2rem;
  font-weight: bold;
  background-color: #f8f9fa;
  border: 2px solid #0d6efd;
  pointer-events: none;
  height: 70px;
}

.keypad-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
}

.keypad-btn {
  font-size: 1.5rem;
  font-weight: bold;
  padding: 1.5rem;
  border: 2px solid #dee2e6;
  border-radius: 8px;
  background: white;
  cursor: pointer;
  transition: all 0.15s;
  min-height: 70px;
}

.keypad-btn:hover {
  background: #e9ecef;
  border-color: #0d6efd;
}

.keypad-btn:active {
  transform: scale(0.95);
  background: #0d6efd;
  color: white;
}

.keypad-btn.btn-danger {
  background: #dc3545;
  color: white;
  border-color: #dc3545;
}

.keypad-btn.btn-danger:hover {
  background: #bb2d3b;
}

.keypad-btn.btn-warning {
  background: #ffc107;
  border-color: #ffc107;
}

.keypad-btn.btn-warning:hover {
  background: #ffca2c;
}

/* Touch device optimizations */
@media (hover: none) and (pointer: coarse) {
  .keypad-btn {
    font-size: 2rem;
    padding: 2rem;
    min-height: 80px;
  }

  .keypad-display input {
    font-size: 2.5rem;
    height: 80px;
  }
}
</style>
