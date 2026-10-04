import { describe, expect, it } from 'vitest';
import { dashboardTreeForRole, normaliseRoleName } from './roles';

describe('normaliseRoleName', () => {
  it('folds the casing and separator styles that occur in real data', () => {
    // api/app/Support/Auth/RolePermissions.php::roleName() already does exactly
    // this, which means a token can carry any of these spellings for one role. The
    // router used to compare raw strings, so "branch_manager" matched no branch at
    // all and a legitimate login landed on the marketing 404.
    for (const spelling of ['Executive', 'executive', 'EXECUTIVE', '  Executive ']) {
      expect(normaliseRoleName(spelling)).toBe('executive');
    }

    expect(normaliseRoleName('BranchManager')).toBe('branchmanager');
    expect(normaliseRoleName('branch_manager')).toBe('branchmanager');
    expect(normaliseRoleName('branch manager')).toBe('branchmanager');
    expect(normaliseRoleName('CoreSupport')).toBe('coresupport');
    expect(normaliseRoleName('core-support')).toBe('coresupport');
  });

  it('survives missing and non-string input', () => {
    // `data?.data?.role?.name` is four optional chains deep; an unauthenticated or
    // partially hydrated user can make this null, undefined, or an object.
    expect(normaliseRoleName(null)).toBe('');
    expect(normaliseRoleName(undefined)).toBe('');
    expect(normaliseRoleName('')).toBe('');
    expect(normaliseRoleName({} as unknown as string)).toBe('');
  });
});

describe('dashboardTreeForRole', () => {
  it('resolves the seeded roles that have a dashboard', () => {
    expect(dashboardTreeForRole('Executive')).toBe('executive');
    expect(dashboardTreeForRole('BranchManager')).toBe('branchmanager');
    expect(dashboardTreeForRole('CoreSupport')).toBe('coresupport');
    expect(dashboardTreeForRole('siteadmin')).toBe('siteadmin');
    expect(dashboardTreeForRole('Operations')).toBe('operations');
    expect(dashboardTreeForRole('Procurement')).toBe('procurement');
  });

  it('resolves the same tree whatever the spelling', () => {
    expect(dashboardTreeForRole('branch_manager')).toBe(dashboardTreeForRole('BranchManager'));
    expect(dashboardTreeForRole('branch manager')).toBe(dashboardTreeForRole('BranchManager'));
  });

  it('returns null for roles this build has no tree for, so they can be told apart from 404s', () => {
    // editor, supplier and customer are all seeded real roles with no dashboard.
    // They used to fall through to the catch-all and were shown a 404, which reads
    // as "you navigated wrong" rather than "nothing is published for you yet".
    expect(dashboardTreeForRole('editor')).toBeNull();
    expect(dashboardTreeForRole('supplier')).toBeNull();
    expect(dashboardTreeForRole('customer')).toBeNull();
    expect(dashboardTreeForRole('nonexistent')).toBeNull();
  });

  it('returns null when there is no role at all', () => {
    expect(dashboardTreeForRole(undefined)).toBeNull();
    expect(dashboardTreeForRole(null)).toBeNull();
  });
});
