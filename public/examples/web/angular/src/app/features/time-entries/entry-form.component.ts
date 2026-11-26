// features/time-entries/entry-form.component.ts — updated forms:
// signal forms (form() + FormField directive + schema validators).
// The form submits to the domain service, never to HttpClient directly.

import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { form, FormField, min, required, submit } from '@angular/forms/signals';
import { firstValueFrom } from 'rxjs';
import { EntriesService } from './entries.service';
import type { NewEntry } from './entry.model';

@Component({
  selector: 'app-entry-form',
  imports: [FormField],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <form (submit)="onSubmit($event)" novalidate>
      <label>
        <span i18n="@@entry.task">Task</span>
        <input [formField]="entryForm.task" />
        @for (err of entryForm.task().errors(); track err.kind) {
          <small class="error">{{ err.message }}</small>
        }
      </label>
      <label>
        <span i18n="@@entry.minutes">Minutes</span>
        <input type="number" [formField]="entryForm.minutes" />
      </label>
      <button type="submit" [disabled]="entryForm().invalid() || saving()">
        {{ saving() ? 'Saving…' : 'Add entry' }}
      </button>
    </form>
  `,
})
export class EntryForm {
  private entries = inject(EntriesService);

  protected saving = signal(false);
  protected model = signal<NewEntry>({ task: '', minutes: 30 });

  // The schema function is the form's contract — same role as the
  // backend's request validation, on the client side.
  protected entryForm = form(this.model, (p) => {
    required(p.task, { message: 'Task is required' });
    min(p.minutes, 1, { message: 'At least 1 minute' });
  });

  onSubmit(event: Event) {
    event.preventDefault();
    submit(this.entryForm, {
      action: async () => {
        this.saving.set(true);
        try {
          await firstValueFrom(this.entries.create(this.model()));
          this.model.set({ task: '', minutes: 30 });
        } finally {
          this.saving.set(false);
        }
      },
    });
  }
}
