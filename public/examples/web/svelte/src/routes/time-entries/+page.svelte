<script lang="ts">
  // +page.svelte — the page consumes load() data and posts to the action.
  // Progressive enhancement via use:enhance — works without JS too.

  import { enhance } from '$app/forms';
  import type { PageProps } from './$types';

  let { data }: PageProps = $props();
</script>

<main>
  <h1>Time entries — {data.day}</h1>

  <form method="POST" action="?/create" use:enhance>
    <input name="task" placeholder="Task" required />
    <input name="minutes" type="number" min="1" value="30" required />
    <button type="submit">Add entry</button>
  </form>

  <ul>
    {#each data.list as entry (entry.id)}
      <li>{entry.task} — {entry.minutes}min</li>
    {:else}
      <li>No entries yet.</li>
    {/each}
  </ul>
</main>
