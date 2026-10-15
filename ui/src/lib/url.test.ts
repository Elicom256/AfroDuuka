import { describe, expect, it } from 'vitest';
import { collapseSlashes } from './url';

describe('collapseSlashes', () => {
  it('folds the doubled leading slash a trailing-slash FRONTEND_URL produced', () => {
    // The exact shape of the broken link: https://host//verify-email?token=…
    expect(collapseSlashes('//verify-email')).toBe('/verify-email');
  });

  it('folds runs of three or more slashes', () => {
    expect(collapseSlashes('///verify-email')).toBe('/verify-email');
    expect(collapseSlashes('//a//b///c')).toBe('/a/b/c');
  });

  it('leaves a well-formed path alone', () => {
    expect(collapseSlashes('/verify-email')).toBe('/verify-email');
    expect(collapseSlashes('/')).toBe('/');
    expect(collapseSlashes('/dashboard/executive/products')).toBe(
      '/dashboard/executive/products'
    );
  });
});
