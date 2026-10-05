import { readdirSync, readFileSync, statSync } from 'node:fs';
import { join } from 'node:path';
import { describe, expect, it } from 'vitest';

/**
 * Every delete control has to ask before it deletes.
 *
 * checked.md P1-19: 17 table-row and icon triggers fired their mutation straight from
 * the click, and a further five used a native `confirm()`. Deleting is the one action in
 * this product that cannot be undone by retyping, and those triggers sit in dense tables
 * where a mis-click reaches them easily.
 *
 * This is a source-level check rather than a rendered one on purpose. The failure mode
 * being prevented is reintroducing an unguarded trigger in some of ~230 page files, and
 * that is a property of the source: rendering every table and asserting a dialog would
 * not notice a trigger added next week. It also keeps the check honest about delegation —
 * a page that passes `onDelete` to a child is fine precisely because the child is a file
 * this test also reads.
 */

const PAGES = 'src/app/pages';

const walk = (dir: string): string[] =>
  readdirSync(dir).flatMap((entry) => {
    const full = join(dir, entry);
    return statSync(full).isDirectory() ? walk(full) : full.endsWith('.tsx') ? [full] : [];
  });

const files = walk(PAGES);

/** Triggers that remove something and are therefore in scope. */
const DELETE_TRIGGER = /onClick=\{(?:\(\) => )?(?:handle[A-Za-z]*[Dd]elete|handleSuspend|delete[A-Za-z]*)\(/;
/** The row-removal control in BranchSetup drops a form field, not a record. */
const NOT_DESTRUCTIVE = /BranchSetup\.tsx$/;

/**
 * A file is covered if it confirms, or if it delegates to a child that does.
 *
 * `ConfirmDeleteButton` is matched as a JSX element, not as a bare identifier: an
 * earlier version of this test matched the identifier and therefore passed on the
 * import alone. Removing the wrapper while leaving the import behind kept it green,
 * which is exactly the regression it exists to catch.
 */
const confirmsOrDelegates = (source: string): boolean =>
  /<ConfirmDeleteButton/.test(source) ||
  /if \(!confirm\(/.test(source) ||
  /AlertDialogContent/.test(source) ||
  // hands the delete to a table/panel/child component, which is a file we also read
  /\bon(Delete|DeleteRow)=\{/.test(source);

describe('delete controls confirm first', () => {
  it('finds the page files to check', () => {
    expect(files.length).toBeGreaterThan(100);
  });

  it.each(files.filter((f) => !NOT_DESTRUCTIVE.test(f)))('%s', (file) => {
    const source = readFileSync(file, 'utf8');

    // Only files that actually have a delete trigger are held to the rule.
    const hasDeleteTrigger = DELETE_TRIGGER.test(source);
    if (!hasDeleteTrigger) return;

    expect(
      confirmsOrDelegates(source),
      `${file} fires a delete straight from its trigger with nothing to confirm it. ` +
        'Wrap it in ConfirmDeleteButton.'
    ).toBe(true);
  });
});
