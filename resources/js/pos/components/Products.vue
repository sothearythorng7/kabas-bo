<template>
  <div>
    <h2>Produits</h2>
    <ul>
      <li v-for="p in products" :key="p.id">{{ p.name }} - {{ p.price }}$</li>
    </ul>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { db } from '../db.js';

const products = ref([]);

onMounted(async () => {
  products.value = await db.products.toArray();
});
</script>
