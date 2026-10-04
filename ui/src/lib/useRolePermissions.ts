import { useLoggedinUserQuery } from '@/app/store/features/auth/authQuery';
import { normaliseRoleName } from '@/lib/roles';

/**
 * Client-side mirror of api/app/Support/Auth/RolePermissions.php.
 *
 * This is a convenience for hiding controls the user cannot use — never the control
 * itself. Every one of these is enforced server-side, and the backend's copy is the
 * authority: `MutatingEndpointAuthorizationTest` and `BlockRestrictedRoleActions` are
 * what actually stop a request. A divergence here shows a button that then 403s,
 * which is a cosmetic bug, so the values below are kept aligned with the backend
 * rather than tuned independently.
 *
 * Role names are normalised first because they are stored inconsistently
 * ("BranchManager" / "branch_manager" / "Branch Manager" all appear in real data).
 * Comparing raw strings meant a differently-cased role silently evaluated every
 * capability to false.
 */
export const useRolePermissions = () => {
  const { data } = useLoggedinUserQuery();
  const role = data?.data?.role?.name;
  const key = normaliseRoleName(role);

  const isOperations = key === 'operations';
  const isProcurement = key === 'procurement';
  const isBranchManager = key === 'branchmanager';

  // ELEVATED_ROLES on the backend is executive / coresupport / siteadmin.
  const isElevated = key === 'executive' || key === 'coresupport' || key === 'siteadmin';
  const isExecutive = key === 'executive';

  const canManageBranch = isElevated || isBranchManager;

  const canDelete = canManageBranch;
  const canCreate = canManageBranch;

  const canManageCatalog = canManageBranch;

  // Matches RolePermissions::canModifyStock(), which is canManageBranch() only.
  // This used to add `|| isOperations`, which contradicted the backend: Operations may
  // move quantity, but only through InventoryService so that a stock_movements row is
  // left behind — not by authoring stock records directly. The extra clause here only
  // ever produced a control the server refused.
  const canModifyStock = canManageBranch;

  const canCreatePurchaseOrder = canManageBranch || isProcurement;

  const canApprovePurchaseOrder = canManageBranch;

  const canReceivePurchaseOrder = canManageBranch || isProcurement;

  // Suppliers are the business's counterparties, not a branch's. A BranchManager
  // reads them (their purchases render a supplier name) but must not author them,
  // so this is executive-only rather than canManageBranch. Mirrors
  // RolePermissions::canManageSuppliers() on the api side.
  const canManageSuppliers = isExecutive;

  return {
    role,
    isOperations,
    isProcurement,
    isBranchManager,
    isExecutive,
    canDelete,
    canCreate,
    canManageCatalog,
    canModifyStock,
    canCreatePurchaseOrder,
    canApprovePurchaseOrder,
    canReceivePurchaseOrder,
    canManageSuppliers,
  };
};