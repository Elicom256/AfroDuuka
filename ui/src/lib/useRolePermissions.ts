import { useLoggedinUserQuery } from '@/app/store/features/auth/authQuery';

export const useRolePermissions = () => {
  const { data } = useLoggedinUserQuery();
  const role = data?.data?.role?.name;
  const isOperations = role === 'Operations';
  const isProcurement = role === 'Procurement';
  const isBranchManager = role === 'BranchManager';
  const isExecutive = role === 'Executive';

  const canManageBranch = isExecutive || isBranchManager;

  const canDelete = canManageBranch;
  const canCreate = canManageBranch;

  const canManageCatalog = canManageBranch;

  const canModifyStock = canManageBranch || isOperations;

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
