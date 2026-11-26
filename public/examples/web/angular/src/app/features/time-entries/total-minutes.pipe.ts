// features/time-entries/total-minutes.pipe.ts — pure pipes are pure
// functions: deterministic, cached by Angular, trivially testable.

import { Pipe, PipeTransform } from '@angular/core';
import type { TimeEntry } from './entry.model';

@Pipe({ name: 'totalMinutes', pure: true })
export class TotalMinutesPipe implements PipeTransform {
  transform(entries: TimeEntry[]): number {
    return entries.reduce((sum, e) => sum + e.minutes, 0);
  }
}
