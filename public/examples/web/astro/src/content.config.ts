// src/content.config.ts — the collection IS the contract:
// loader + schema declare the data shape; invalid content fails at
// build time, not in production runtime.

import { defineCollection, z } from 'astro:content';
import { glob } from 'astro/loaders';

const docs = defineCollection({
  loader: glob({ pattern: '**/*.md', base: './src/content/docs' }),
  schema: z.object({
    title: z.string(),
    description: z.string(),
    sidebar: z.string().optional(),
  }),
});

export const collections = { docs };
