import { describe, expect, it } from 'vitest';
import { isDashboardPath, isPublicPath, PUBLIC_NAV_LINKS, PUBLIC_PATHS } from './routes';

describe('isPublicPath', () => {
  it('accepts the declared public pages', () => {
    for (const path of PUBLIC_PATHS) {
      expect(isPublicPath(path)).toBe(true);
    }
  });

  it('rejects protected paths', () => {
    // /login and /onboarding are intentionally on the public list: each renders its
    // own signed-out form, and a stale token must never be able to hide the login
    // screen — that is the lockout this whole change set exists to remove.
    expect(isPublicPath('/dashboard')).toBe(false);
    expect(isPublicPath('/dashboard/orders')).toBe(false);
    expect(isPublicPath('/settings')).toBe(false);
  });

  it('matches whole paths only, not prefixes', () => {
    // Prefix matching would let /pricing-faq and /about-us be treated as public,
    // which is harmless here but breaks the moment a protected route is ever added
    // underneath one of these names.
    expect(isPublicPath('/pricing-faq')).toBe(false);
    expect(isPublicPath('/login/callback')).toBe(false);
    expect(isPublicPath('/')).toBe(true);
  });

  it('is not fooled by a trailing slash', () => {
    // These URLs are reachable: a server or hosting rewrite will happily serve
    // "/pricing/", and treating that as protected would reintroduce the redirect for
    // a page the visitor can see in their address bar.
    expect(isPublicPath('/pricing/')).toBe(true);
    expect(isPublicPath('/about/')).toBe(true);
  });

  it('is not fooled by a query string or hash', () => {
    expect(isPublicPath('/pricing?plan=pro')).toBe(true);
    expect(isPublicPath('/documentation#install')).toBe(true);
  });

  it('treats the token-driven verification page as public', () => {
    // The emailed link is opened on whatever device has the inbox, often signed out, so
    // this page cannot require a session — a dead or missing token must not bounce the
    // visitor to /login before they can see why their link failed.
    expect(isPublicPath('/verify-email')).toBe(true);
    expect(PUBLIC_PATHS).toContain('/verify-email');
  });
});

describe('isDashboardPath', () => {
  it('matches the dashboard root and everything under it', () => {
    expect(isDashboardPath('/dashboard')).toBe(true);
    expect(isDashboardPath('/dashboard/')).toBe(true);
    expect(isDashboardPath('/dashboard/orders')).toBe(true);
    expect(isDashboardPath('/dashboard/settings/users')).toBe(true);
  });

  it('respects segment boundaries', () => {
    // Every role mounts a single /dashboard/* tree, so the segment is the only
    // reliable marker. A plain startsWith('dashboard') would also claim
    // /dashboard-notes, /dashboardarchive and /dashboard-export as protected.
    expect(isDashboardPath('/dashboard-notes')).toBe(false);
    expect(isDashboardPath('/dashboardarchive')).toBe(false);
    expect(isDashboardPath('/dashboard-export')).toBe(false);
  });

  it('rejects public and auth paths', () => {
    expect(isDashboardPath('/')).toBe(false);
    expect(isDashboardPath('/pricing')).toBe(false);
    expect(isDashboardPath('/login')).toBe(false);
    expect(isDashboardPath('/onboarding')).toBe(false);
  });
});

describe('PUBLIC_NAV_LINKS', () => {
  it('only links to paths that are declared public', () => {
    // A nav entry pointing outside PUBLIC_PATHS rebuilds the original bug through the
    // back door: a link that looks public and lands on a redirect.
    for (const link of PUBLIC_NAV_LINKS) {
      expect(isPublicPath(link.to)).toBe(true);
    }
  });

  it('does not link straight into a dashboard, where an anonymous click cannot go', () => {
    for (const link of PUBLIC_NAV_LINKS) {
      expect(isDashboardPath(link.to)).toBe(false);
    }
  });

  it('covers every public marketing page that exists', () => {
    // /login is deliberately absent: it is an action, not a place you navigate to read,
    // and NavBar renders it as a button. /onboarding is absent too because it is where
    // the sign-up CTAs point rather than a marketing page. The rest of PUBLIC_PATHS is
    // either a marketing page reachable from the nav or a route the nav must not
    // advertise (/terms, /privacy), which is why only a subset is asserted here.
    const targets = PUBLIC_NAV_LINKS.map((link) => link.to);
    expect(targets).toEqual(['/', '/pricing', '/about', '/documentation']);
  });
});
