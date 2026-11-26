// features/time-entries/entries.service.ts — the domain is a service.
// Components inject it; they never call HttpClient directly.

import { Injectable, inject } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, tap } from 'rxjs';
import { BehaviorSubject } from 'rxjs';
import type { NewEntry, TimeEntry } from './entry.model';

@Injectable({ providedIn: 'root' })
export class EntriesService {
  private http = inject(HttpClient);

  // Domain state exposed read-only; mutations go through create().
  private readonly list$ = new BehaviorSubject<TimeEntry[]>([]);
  readonly entries$ = this.list$.asObservable();

  fetchAll(day: string): Observable<TimeEntry[]> {
    return this.http
      .get<TimeEntry[]>(`/api/time-entries?day=${encodeURIComponent(day)}`)
      .pipe(tap((entries) => this.list$.next(entries)));
  }

  // Client-side permission check against the user's permission set
  // (populated at login from the API). The real enforcement is server-side.
  private permissions = new Set<string>(['time.read', 'time.write']);

  can(perm: string): boolean {
    return this.permissions.has(perm);
  }

  // The X-Request-Id header is added globally by reqIdInterceptor —
  // see app/request-id.interceptor.ts.
  create(entry: NewEntry): Observable<TimeEntry> {
    return this.http
      .post<TimeEntry>('/api/time-entries', entry)
      .pipe(tap((created) => this.list$.next([...this.list$.value, created])));
  }
}
