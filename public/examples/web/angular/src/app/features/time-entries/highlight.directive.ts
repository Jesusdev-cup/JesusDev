// features/time-entries/highlight.directive.ts — an attribute directive:
// reusable DOM behavior shared by domains, kept out of components.

import { Directive, effect, ElementRef, inject, input } from '@angular/core';

@Directive({ selector: '[appHighlight]' })
export class HighlightDirective {
  private el = inject(ElementRef<HTMLElement>);

  readonly appHighlight = input(false);

  constructor() {
    effect(() => {
      this.el.nativeElement.classList.toggle('highlight', this.appHighlight());
    });
  }
}
