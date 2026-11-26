<script setup lang="ts">
// EntryForm.vue — the view calls the domain's public function.
import { ref } from 'vue';
import { useEntries } from './useEntries';

const { list, create } = useEntries();

const task = ref('');
const minutes = ref(30);
const pending = ref(false);

// Client-side bounds mirror the server contract.
const TASK_MAX = 255;
const MINUTES_MAX = 1440;

async function submit() {
  const value = task.value.trim();
  if (!value || value.length > TASK_MAX || !Number.isInteger(minutes.value) ||
      minutes.value < 1 || minutes.value > MINUTES_MAX || pending.value) {
    return;
  }
  pending.value = true;
  try {
    await create({ task: value, minutes: minutes.value });
    task.value = '';
    minutes.value = 30;
  } finally {
    pending.value = false;
  }
}
</script>

<template>
  <form @submit.prevent="submit">
    <input v-model="task" placeholder="Task" :maxlength="TASK_MAX" autocomplete="off" required />
    <input v-model.number="minutes" type="number" min="1" :max="MINUTES_MAX" required />
    <button type="submit" :disabled="pending">
      {{ pending ? 'Saving…' : 'Add entry' }}
    </button>
  </form>
  <ul>
    <li v-for="e in list" :key="e.id">{{ e.task }} — {{ e.minutes }}min</li>
  </ul>
</template>
