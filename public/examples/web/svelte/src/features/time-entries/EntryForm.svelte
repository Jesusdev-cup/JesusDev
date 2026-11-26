<script lang="ts">
  // EntryForm.svelte — reads the reactive domain state, calls its public API.
  import { entries } from './entries.svelte';

  let task = $state('');
  let minutes = $state(30);
  let pending = $state(false);

  async function submit(e: SubmitEvent) {
    e.preventDefault();
    if (!task.trim() || pending) return;
    pending = true;
    try {
      await entries.create({ task: task.trim(), minutes });
      task = '';
      minutes = 30;
    } finally {
      pending = false;
    }
  }
</script>

<form onsubmit={submit}>
  <input bind:value={task} placeholder="Task" required />
  <input bind:value={minutes} type="number" min="1" required />
  <button type="submit" disabled={pending}>
    {pending ? 'Saving…' : 'Add entry'}
  </button>
</form>

<ul>
  {#each entries.list as entry (entry.id)}
    <li>{entry.task} — {entry.minutes}min</li>
  {/each}
</ul>
