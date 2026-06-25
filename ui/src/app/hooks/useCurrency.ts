import { useLoggedinUserQuery } from '@/app/store/features/auth/authQuery';

export const useCurrency = (): { currency: string; currencySymbol: string } => {
  const { data: userData } = useLoggedinUserQuery();
  const country = userData?.data?.business?.country;
  const currency = country?.currency_code;

  return {
    currency,
    currencySymbol: currency,
  };
};
