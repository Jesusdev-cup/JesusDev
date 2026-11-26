<script setup lang="ts">
// EntryList.vue — declarative rendering: v-for, v-if, props down.
import { computed } from 'vue';
import type { TimeEntry } from './useEntries';

const props = defineProps<{ entries: TimeEntry[] }>();
const total = computed(() => props.entries.reduce((s, e) => s + e.minutes, 0));
</script>

<template>
  <p v-if="entries.length === 0">No entries yet.</p>
  <section v-else>
    <ul>
      <li v-for="e in entries" :key="e.id">{{ e.task }} — {{ e.minutes }}min</li>
    </ul>
    <p>Total: {{ total }}min</p>
  </section>
</template>
