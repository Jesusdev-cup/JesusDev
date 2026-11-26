// features/time-entries/components/EntryForm.tsx — UI consumes the domain,
// it never builds requests itself.

import { useState, type FormEvent } from 'react';
import type { NewEntry } from '../api';

interface Props {
  onCreate: (entry: NewEntry) => Promise<unknown>;
}

export function EntryForm({ onCreate }: Props) {
  const [task, setTask] = useState('');
  const [minutes, setMinutes] = useState(30);
  const [pending, setPending] = useState(false);

  const submit = async (e: FormEvent) => {
    e.preventDefault();
    if (!task.trim() || pending) return;
    setPending(true);
    try {
      await onCreate({ task: task.trim(), minutes });
      setTask('');
      setMinutes(30);
    } finally {
      setPending(false);
    }
  };

  return (
    <form onSubmit={submit}>
      <input
        value={task}
        onChange={(e) => setTask(e.target.value)}
        placeholder="Task"
        required
      />
      <input
        type="number"
        min={1}
        value={minutes}
        onChange={(e) => setMinutes(Number(e.target.value))}
        required
      />
      <button type="submit" disabled={pending}>
        {pending ? 'Saving…' : 'Add entry'}
      </button>
    </form>
  );
}
