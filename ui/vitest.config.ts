import { defineConfig } from 'vitest/config';
import react from '@vitejs/plugin-react';
import path from 'path';

/**
 * Kept separate from vite.config.ts so the test runner never has to agree with the
 * build about the Tailwind plugin, and so `npm run build` cannot be broken by a
 * test-only concern.
 */
export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['./src/test/setup.ts'],
    include: ['src/**/*.test.{ts,tsx}'],
    restoreMocks: true,
    // Routes are code-split, so the first test to mount a dashboard tree waits for
    // Vite to transform that tree's on-demand chunks. That is a one-off cost in the
    // test runner, not a slow render, and it can exceed the 5s default on a cold worker.
    testTimeout: 20000,
  },
});