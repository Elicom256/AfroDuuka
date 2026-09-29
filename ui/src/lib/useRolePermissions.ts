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

  const canModifyStock = canManageBranch;

  const canCreatePurchaseOrder = canManageBranch || isProcurement;

  const canApprovePurchaseOrder = canManageBranch;

  const canReceivePurchaseOrder = canManageBranch || isProcurement;

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
  };
};
