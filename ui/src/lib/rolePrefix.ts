export const getRolePrefix = (role: string | undefined): string => {
  if (!role) return '/dashboard';
  return `/${role.toLowerCase()}`;
};
