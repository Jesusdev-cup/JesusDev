// features/time-entries/components/EntryForm.tsx — UI consumes the domain,
// it never builds requests itself. Client-side bounds mirror the server
// contract: the same limits, enforced before the request leaves.

import { useState, type FormEvent } from 'react';
import type { NewEntry } from '../api';

interface Props {
  onCreate: (entry: NewEntry) => Promise<unknown>;
}

const TASK_MAX = 255;
const MINUTES_MAX = 1440; // a day

export function EntryForm({ onCreate }: Props) {
  const [task, setTask] = useState('');
  const [minutes, setMinutes] = useState(30);
  const [pending, setPending] = useState(false);

  const submit = async (e: FormEvent) => {
    e.preventDefault();
    const value = task.trim();
    // Same validation the server enforces — fail fast client-side.
    if (!value || value.length > TASK_MAX || !Number.isInteger(minutes) ||
        minutes < 1 || minutes > MINUTES_MAX || pending) {
      return;
    }
    setPending(true);
    try {
      await onCreate({ task: value, minutes });
      setTask('');
      setMinutes(30);
    } finally {
      setPending(false);
    }
  };

  return (
    <form onSubmit={submit} noValidate={false}>
      <input
        value={task}
        onChange={(e) => setTask(e.target.value)}
        placeholder="Task"
        maxLength={TASK_MAX}
        autoComplete="off"
        required
      />
      <input
        type="number"
        min={1}
        max={MINUTES_MAX}
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
