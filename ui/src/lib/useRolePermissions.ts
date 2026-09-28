import { useLoggedinUserQuery } from '@/app/store/features/auth/authQuery';

export const useRolePermissions = () => {
  const { data } = useLoggedinUserQuery();
  const role = data?.data?.role?.name;
  const isOperations = role === 'Operations';
  const canDelete = !isOperations;
  const canCreate = !isOperations;
  return { role, isOperations, canDelete, canCreate };
};
