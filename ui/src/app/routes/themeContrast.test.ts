import { readdirSync, readFileSync, statSync } from 'node:fs';
import { join } from 'node:path';
import { describe, expect, it } from 'vitest';

/**
 * Text colour has to come from the theme tokens, not from a hardcoded palette entry.
 *
 * checked.md P2 claimed dark mode failed WCAG AA at 3.37:1 and blamed the
 * `--muted-foreground` token. Measured, that token is 8.18:1 on the dark background and
 * passes comfortably — the review had compared the *light* value against the dark
 * background. The real failure was elsewhere: 46 uses of `text-gray-500` and friends with
 * no `dark:` counterpart, which is 3.87:1 on the dark card and passes in light mode. So
 * the symptom was real, the diagnosis was not, and changing the passing token would have
 * "fixed" nothing while making the light theme worse.
 *
 * This holds the actual property: no hardcoded grey without a dark override.
 */
const SRC = 'src';

const walk = (dir: string): string[] =>
  readdirSync(dir).flatMap((entry) => {
    const full = join(dir, entry);
    return statSync(full).isDirectory() ? walk(full) : full.endsWith('.tsx') ? [full] : [];
  });

const files = walk(SRC);

const HARD_CODED_GREY = /text-(?:gray|slate|zinc|neutral|stone)-\d{3}/;

describe('theme-aware text colour', () => {
  it('finds the files to check', () => {
    expect(files.length).toBeGreaterThan(100);
  });

  it.each(files)('%s', (file) => {
    const lines = readFileSync(file, 'utf8').split('\n');
    const offenders: string[] = [];

    lines.forEach((line, i) => {
      if (HARD_CODED_GREY.test(line) && !line.includes('dark:text-')) {
        offenders.push(`${i + 1}: ${line.trim()}`);
      }
    });

    expect(
      offenders,
      `${file} uses a hardcoded grey with no dark: override, which fails WCAG AA in dark ` +
        'mode. Use text-muted-foreground instead.'
    ).toEqual([]);
  });
});
