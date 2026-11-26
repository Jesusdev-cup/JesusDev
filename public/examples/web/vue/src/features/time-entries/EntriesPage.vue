<script setup lang="ts">
// EntriesPage.vue — provides the domain at the feature boundary;
// children inject it via EntriesKey (no prop drilling).
import { onMounted, provide } from 'vue';
import { useEntries } from './useEntries';
import { EntriesKey } from './entries-key';
import EntryForm from './EntryForm.vue';
import EntryList from './EntryList.vue';

const props = defineProps<{ day: string }>();

const entries = useEntries();
provide(EntriesKey, entries);

onMounted(() => entries.fetchAll(props.day));
</script>

<template>
  <main>
    <h1>Time entries</h1>
    <EntryForm />
    <p v-if="entries.error.value" role="alert">{{ entries.error }}</p>
    <p v-if="entries.loading.value">Loading…</p>
    <EntryList v-else :entries="entries.list.value" />
  </main>
</template>
