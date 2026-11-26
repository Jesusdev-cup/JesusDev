<script lang="ts">
  // EntryForm.svelte — reads the reactive domain state, calls its public API.
  import { entries } from './entries.svelte';

  let task = $state('');
  let minutes = $state(30);
  let pending = $state(false);

  // Client-side bounds mirror the server contract.
  const TASK_MAX = 255;
  const MINUTES_MAX = 1440;

  async function submit(e: SubmitEvent) {
    e.preventDefault();
    const value = task.trim();
    if (!value || value.length > TASK_MAX || !Number.isInteger(minutes) ||
        minutes < 1 || minutes > MINUTES_MAX || pending) {
      return;
    }
    pending = true;
    try {
      await entries.create({ task: value, minutes });
      task = '';
      minutes = 30;
    } finally {
      pending = false;
    }
  }
</script>

<form onsubmit={submit}>
  <input bind:value={task} placeholder="Task" maxlength={TASK_MAX} autocomplete="off" required />
  <input bind:value={minutes} type="number" min="1" max={MINUTES_MAX} required />
  <button type="submit" disabled={pending}>
    {pending ? 'Saving…' : 'Add entry'}
  </button>
</form>

<ul>
  {#each entries.list as entry (entry.id)}
    <li>{entry.task} — {entry.minutes}min</li>
  {/each}
</ul>
