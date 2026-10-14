import { describe, expect, it } from 'vitest';

import { normalizeBusinessCategories } from './onboardingQuery';

/**
 * The category dropdown was permanently empty because the controller answers with a
 * bare JSON array while the consumer read `.data` off it. These cases pin every shape
 * the endpoint has ever produced, plus the malformed ones, so the normalizer — not
 * each screen — decides what an empty list looks like.
 */
describe('normalizeBusinessCategories', () => {
  const categories = [
    { id: 1, name: 'Retail' },
    { id: 2, name: 'Wholesale' },
  ];

  it('passes through the bare array the controller returns', () => {
    expect(normalizeBusinessCategories(categories)).toEqual(categories);
  });

  it('unwraps a paginator-style { data } envelope', () => {
    expect(normalizeBusinessCategories({ data: categories })).toEqual(categories);
  });

  it('unwraps a { categories } envelope', () => {
    expect(normalizeBusinessCategories({ categories })).toEqual(categories);
  });

  it('returns an empty array for absent or malformed payloads', () => {
    expect(normalizeBusinessCategories(undefined)).toEqual([]);
    expect(normalizeBusinessCategories(null)).toEqual([]);
    expect(normalizeBusinessCategories({})).toEqual([]);
    expect(normalizeBusinessCategories({ data: null })).toEqual([]);
    expect(normalizeBusinessCategories('not-a-list')).toEqual([]);
  });
});
