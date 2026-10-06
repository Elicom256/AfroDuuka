import { beforeEach } from 'vitest';
import { configure } from '@testing-library/react';

// Routes are code-split, so a page only appears after its dynamic import resolves.
// The default 1s waitFor budget was occasionally shorter than the first transform of
// a dashboard chunk under a cold test worker, which made route tests flaky rather
// than wrong. Waiting longer does not slow the suite: an assertion that passes returns
// as soon as it is true.
configure({ asyncUtilTimeout: 15000 });

/**
 * jsdom does not implement navigation, and two things under test navigate for real:
 * UserProfile.handleLogout assigns window.location.href, and authListener calls
 * location.replace() when a 401 lands on a protected page. Left alone these raise
 * "Not implemented: navigation" noise that buries genuine failures, so window.location
 * is replaced with a plain object and every navigation is recorded instead.
 */
export const navigations: string[] = [];

beforeEach(() => {
  navigations.length = 0;

  localStorage.clear();
  sessionStorage.clear();

  Object.defineProperty(window, 'location', {
    configurable: true,
    writable: true,
    value: {
      get href() {
        return window.location.pathname;
      },
      set href(next: string) {
        navigations.push(next);
        window.location.pathname = next;
      },
      pathname: '/',
      origin: 'http://localhost',
      search: '',
      hash: '',
      assign(next: string) {
        navigations.push(next);
        window.location.pathname = next;
      },
      replace(next: string) {
        navigations.push(next);
        window.location.pathname = next;
      },
      reload: () => {},
    },
  });
});
