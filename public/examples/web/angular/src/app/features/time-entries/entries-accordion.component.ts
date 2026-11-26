// features/time-entries/entries-accordion.component.ts — Angular Aria:
// headless directives give keyboard navigation + ARIA semantics for free;
// the styling stays ours. Accessibility is part of the mold, not an addon.

import { ChangeDetectionStrategy, Component, input } from '@angular/core';
import { AccordionContent, AccordionGroup, AccordionPanel, AccordionTrigger } from '@angular/aria/accordion';
import type { TimeEntry } from './entry.model';

@Component({
  selector: 'app-entries-accordion',
  imports: [AccordionGroup, AccordionTrigger, AccordionPanel, AccordionContent],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <div ngAccordionGroup [multiExpandable]="true">
      @for (e of entries(); track e.id) {
        <div class="accordion-item">
          <h3>
            <button ngAccordionTrigger [panel]="panel">{{ e.task }}</button>
          </h3>
          <div ngAccordionPanel #panel="ngAccordionPanel">
            <ng-template ngAccordionContent>
              <p>{{ e.minutes }}min — {{ e.created_at }}</p>
            </ng-template>
          </div>
        </div>
      }
    </div>
  `,
})
export class EntriesAccordion {
  readonly entries = input.required<TimeEntry[]>();
}
