<script setup lang="ts">
// EntryForm.vue — the view calls the domain's public function.
import { ref } from 'vue';
import { useEntries } from './useEntries';

const { list, create } = useEntries();

const task = ref('');
const minutes = ref(30);
const pending = ref(false);

async function submit() {
  if (!task.value.trim() || pending.value) return;
  pending.value = true;
  try {
    await create({ task: task.value.trim(), minutes: minutes.value });
    task.value = '';
    minutes.value = 30;
  } finally {
    pending.value = false;
  }
}
</script>

<template>
  <form @submit.prevent="submit">
    <input v-model="task" placeholder="Task" required />
    <input v-model.number="minutes" type="number" min="1" required />
    <button type="submit" :disabled="pending">
      {{ pending ? 'Saving…' : 'Add entry' }}
    </button>
  </form>
  <ul>
    <li v-for="e in list" :key="e.id">{{ e.task }} — {{ e.minutes }}min</li>
  </ul>
</template>
