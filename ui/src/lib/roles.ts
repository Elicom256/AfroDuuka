/**
 * Role names are stored inconsistently — "BranchManager", "branch_manager" and
 * "Branch Manager" all appear in real data — so nothing may compare one with `===`.
 *
 * The api side was bitten by exactly this and already fixed it: see
 * `RolePermissions::roleName()` in api/app/Support/Auth/RolePermissions.php, whose
 * docblock records that a CoreSupport account with no business was locked out of
 * everything because every capability check read an empty role name. This is the
 * same rule, applied on this side so the two agree.
 *
 * Lowercasing first, then stripping every non-alphanumeric character, means
 * "Branch Manager", "branch_manager" and "branchmanager" all reduce to
 * `branchmanager`.
 */
/**
 * Typed `unknown` rather than `string | null | undefined` on purpose.
 *
 * This sits directly under `data?.data?.role?.name`, four optional chains from the
 * network response, so in practice anything can arrive here — including an object
 * from a half-hydrated payload. A narrower type would push a runtime crash
 * (`name.toLowerCase is not a function`) into a code path that cannot fail a
 * compile-time check, and the crash would happen inside the router while it is
 * deciding whether to redirect.
 */
export const normaliseRoleName = (name: unknown): string =>
  typeof name === 'string' ? name.toLowerCase().replace(/[^a-z0-9]/g, '') : '';

/**
 * Role name -> dashboard tree. Keyed by normalised name so the caller does not
 * have to normalise first, and held in one object rather than a chain of
 * `role === '...'` conditionals so there is a single place to look when a role
 * gains or loses a dashboard.
 *
 * There is deliberately no `staff` entry. Every person in a business is staff, so a
 * separate role with its own dashboard split one job across two vocabularies: the
 * seeder never created a `staff` row, so its tree and six pages were unreachable, and
 * the roles people actually hold (Executive, BranchManager, Operations, Procurement)
 * all cover that work. The tree has been removed rather than seeded.
 *
 * `editor`, `supplier` and `customer` are also absent, and that is a real gap rather
 * than dead code — they are seeded and have no dashboard yet. They stay seeded because
 * a business needs to record the people and companies it buys from and sells to; the
 * missing piece is the portal UI, not the role. See NoDashboardAccess for what a signed
 * -in user without a tree currently sees.
 */
export const ROLE_DASHBOARD_TREE = {
  executive: 'ExecutiveRoutes',
  branchmanager: 'BranchManagerRoutes',
  coresupport: 'SuperadminRoutes',
  siteadmin: 'SuperadminRoutes',
  operations: 'OperationsRoutes',
  procurement: 'ProcurementRoutes',
} as const;

export type RoleDashboardKey = keyof typeof ROLE_DASHBOARD_TREE;

export const dashboardTreeForRole = (name: unknown): RoleDashboardKey | null => {
  const key = normaliseRoleName(name);

  return key in ROLE_DASHBOARD_TREE ? (key as RoleDashboardKey) : null;
};