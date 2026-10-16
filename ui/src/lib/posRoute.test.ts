import { describe, expect, it } from 'vitest';
import { isPosSessionActive } from './posRoute';

describe('isPosSessionActive', () => {
  it('is true on the POS route and its sub-routes', () => {
    expect(isPosSessionActive('/pos')).toBe(true);
    expect(isPosSessionActive('/pos/something')).toBe(true);
  });

  it('is false elsewhere, including a path that merely contains "pos"', () => {
    expect(isPosSessionActive('/dashboard/executive/sales')).toBe(false);
    expect(isPosSessionActive('/')).toBe(false);
    expect(isPosSessionActive('/reports/pos-summary')).toBe(false);
  });
});
