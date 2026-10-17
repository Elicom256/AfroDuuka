import { describe, expect, it } from 'vitest';

import { formatDate } from './safeDate';

/**
 * Offline records carry no server timestamp, and an empty/garbage `created_at` used to
 * make `format(new Date(value))` throw a RangeError inside a table row — unmounting the
 * page, which is one of the ways a list renders blank. formatDate must always return a
 * string.
 */
describe('formatDate', () => {
  it('formats a valid ISO timestamp', () => {
    expect(formatDate('2026-01-02T03:04:05.000Z')).toMatch(/2026/);
  });

  it('accepts a Date instance', () => {
    expect(formatDate(new Date('2026-05-06T00:00:00.000Z'))).toMatch(/2026/);
  });

  it.each([null, undefined, '', 'not-a-date', '2026-13-45T99:99:99Z'])(
    'returns the placeholder instead of throwing for %p',
    (value) => {
      expect(formatDate(value as never)).toBe('-');
    }
  );

  it('honours a custom pattern', () => {
    expect(formatDate('2026-01-02T03:04:05.000Z', 'yyyy')).toBe('2026');
  });
});
