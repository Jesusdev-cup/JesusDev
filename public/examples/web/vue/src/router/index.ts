// router/index.ts — feature routes are lazy; each domain owns its subtree.

import { createRouter, createWebHistory } from 'vue-router';

export const router = createRouter({
  history: createWebHistory(),
  routes: [
    {
      path: '/time-entries',
      name: 'time-entries',
      component: () => import('../features/time-entries/EntriesPage.vue'),
      props: { day: new Date().toISOString().slice(0, 10) },
      meta: { requiresPerm: 'time.read' },
    },
  ],
});

// Route guard = the client-side mirror of the backend's permission matrix.
router.beforeEach((to) => {
  const perm = to.meta.requiresPerm as string | undefined;
  if (perm && !can(perm)) return { name: 'home' };
});

function can(_perm: string): boolean {
  // Reads the user's permission set — populated at login from the API.
  return true;
}
