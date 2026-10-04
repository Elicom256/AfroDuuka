import { beforeEach } from 'vitest';

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
