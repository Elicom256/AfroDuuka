import { useLoggedinUserQuery } from '@/app/store/features/auth/authQuery';

export const useRolePermissions = () => {
  const { data } = useLoggedinUserQuery();
  const role = data?.data?.role?.name;
  const isOperations = role === 'Operations';

  // Operations runs the floor: it counts stock, records adjustments and sells. It does
  // not author the catalogue and it does not remove records. The API is the authority
  // on all of this; these flags only stop the UI offering buttons that would 403.
  const canDelete = !isOperations;
  const canCreate = !isOperations;

  // Whether the caller may change a product's identity, pricing or classification, as
  // opposed to just its stock level. Read-only views are always available.
  const canManageCatalog = !isOperations;

  return { role, isOperations, canDelete, canCreate, canManageCatalog };
};
