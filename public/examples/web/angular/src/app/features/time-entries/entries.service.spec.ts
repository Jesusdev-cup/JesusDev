// features/time-entries/entries.service.spec.ts — domain services are
// tested with HttpTestingController: no real backend, no component.

import { TestBed } from '@angular/core/testing';
import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { EntriesService } from './entries.service';

describe('EntriesService', () => {
  let service: EntriesService;
  let http: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [EntriesService, provideHttpClient(), provideHttpClientTesting()],
    });
    service = TestBed.inject(EntriesService);
    http = TestBed.inject(HttpTestingController);
  });

  afterEach(() => http.verify());

  it('POSTs the entry to the contract endpoint', () => {
    const dto = { task: 'Deep work', minutes: 90 };
    const created = { id: 'e1', ...dto, created_at: '2025-01-01' };

    service.create(dto).subscribe((e) => expect(e).toEqual(created));

    const req = http.expectOne('/api/time-entries');
    expect(req.request.method).toBe('POST');
    expect(req.request.body).toEqual(dto);
    req.flush(created);
  });

  it('updates the domain list after create', () => {
    service.create({ task: 'Deep work', minutes: 90 }).subscribe();
    http.expectOne('/api/time-entries').flush({
      id: 'e1', task: 'Deep work', minutes: 90, created_at: '2025-01-01',
    });

    service.entries$.subscribe((list) => expect(list).toHaveLength(1));
  });
});
