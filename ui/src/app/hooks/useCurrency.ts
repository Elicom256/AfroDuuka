import { useLoggedinUserQuery } from '@/app/store/features/auth/authQuery';

export const useCurrency = () => {
  const { data: userData } = useLoggedinUserQuery();
  const country = userData?.data?.business?.country;
  console.log('test currecy==>', userData);

  return {
    currency: country?.currency_code,
    currencySymbol: country?.currency_symbol,
    flagEmoji: country?.flag_emoji,
    countryName: country?.name,
  };
};
