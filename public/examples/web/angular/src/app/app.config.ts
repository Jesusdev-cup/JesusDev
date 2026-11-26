// app.config.ts — bootstrap providers: routing + http client + i18n.

import { ApplicationConfig } from '@angular/core';
import { provideHttpClient, withInterceptors } from '@angular/common/http';
import { provideRouter, withComponentInputBinding } from '@angular/router';
import { reqIdInterceptor } from './request-id.interceptor';
import { routes } from './app.routes';

export const appConfig: ApplicationConfig = {
  providers: [
    provideRouter(routes, withComponentInputBinding()),
    provideHttpClient(withInterceptors([reqIdInterceptor])),
    // $localize is enabled via "localize": true in angular.json + messages.*.xlf
  ],
};
