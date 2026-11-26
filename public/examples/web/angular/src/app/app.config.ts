// app.config.ts — the interceptor is registered once and covers every domain.

import { ApplicationConfig } from '@angular/core';
import { provideHttpClient, withInterceptors } from '@angular/common/http';
import { reqIdInterceptor } from './request-id.interceptor';

export const appConfig: ApplicationConfig = {
  providers: [provideHttpClient(withInterceptors([reqIdInterceptor]))],
};
