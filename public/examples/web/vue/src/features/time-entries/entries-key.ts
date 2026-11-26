// features/time-entries/entries-key.ts — provide/inject = Vue's DI.
// The page provides the domain once; deep children inject the same API.

import type { InjectionKey } from 'vue';
import { useEntries } from './useEntries';

export const EntriesKey: InjectionKey<ReturnType<typeof useEntries>> =
  Symbol('time-entries');
