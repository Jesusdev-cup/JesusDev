// features/time-entries/entries.page.ts — the routed page.
// Resolved data arrives via input() (withComponentInputBinding), the
// mutation goes through the domain service. The page only wires pieces.

import { ChangeDetectionStrategy, Component, inject, input } from '@angular/core';
import { EntriesService } from './entries.service';
import { EntryForm } from './entry-form.component';
import { EntryList } from './entry-list.component';
import type { TimeEntry } from './entry.model';

@Component({
  selector: 'app-entries-page',
  imports: [EntryForm, EntryList],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <h1 i18n="@@entries.title">Time entries</h1>
    <app-entry-form />
    <app-entry-list [entries]="entries()" />
  `,
})
export class EntriesPage {
  // Bound by the router from resolve: { entries: entriesResolver }.
  readonly entries = input.required<TimeEntry[]>();
  readonly day = input.required<string>();

  protected readonly service = inject(EntriesService);
}
