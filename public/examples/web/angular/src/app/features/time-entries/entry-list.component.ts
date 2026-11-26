// features/time-entries/entry-list.component.ts — updated templates:
// @for/@if control flow, signal inputs, pure pipe, animate.enter/leave.

import { ChangeDetectionStrategy, Component, input } from '@angular/core';
import { TotalMinutesPipe } from './total-minutes.pipe';
import { HighlightDirective } from './highlight.directive';
import type { TimeEntry } from './entry.model';

@Component({
  selector: 'app-entry-list',
  imports: [TotalMinutesPipe, HighlightDirective],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    @if (entries().length === 0) {
      <p i18n="@@entries.empty">No entries yet.</p>
    } @else {
      <ul>
        @for (e of entries(); track e.id) {
          <li animate.enter="enter" animate.leave="leave" [appHighlight]="e.minutes > 60">
            {{ e.task }} — {{ e.minutes }}min
          </li>
        } @empty {
          <li i18n="@@entries.empty">No entries yet.</li>
        }
      </ul>
      <p i18n="@@entries.total">Total: {{ entries() | totalMinutes }}min</p>
    }
  `,
})
export class EntryList {
  // Signal inputs — the modern, reactive component API.
  readonly entries = input.required<TimeEntry[]>();
}
