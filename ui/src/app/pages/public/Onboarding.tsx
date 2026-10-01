import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { toast } from 'sonner';
import { useRegisterMutation } from '@/app/store/features/auth/authQuery';
import {
  useCreateBusinessMutation,
  useGetPublicBusinessCategoriesQuery,
} from '@/app/store/features/business/setup/onboardingQuery';
import { useAddBranchMutation } from '@/app/store/features/business/branches/branchesQuery';
import { useCountriesQuery } from '@/app/store/features/countries/countriesQuery';
import { Stepper } from './onboarding/Stepper';
import { AccountSetup } from './onboarding/AccountSetup';
import { BusinessSetup } from './onboarding/BusinessSetup';
import { BranchSetup } from './onboarding/BranchSetup';
import { Preview } from './onboarding/Preview';
import {
  initialAccountData,
  initialBusinessData,
  initialBranchData,
  type AccountData,
  type BusinessData,
  type BranchData,
} from './onboarding/types';

const STEPS = [
  { label: 'Account' },
  { label: 'Business' },
  { label: 'Branches' },
  { label: 'Preview' },
];

export const Onboarding: React.FC = () => {
  const navigate = useNavigate();
  const [currentStep, setCurrentStep] = useState(1);
  const [account, setAccount] = useState<AccountData>(() => ({
    ...initialAccountData,
    username: `@${initialAccountData.firstname.toLowerCase().replace(/\s/g, '')}`,
  }));
  const [business, setBusiness] = useState<BusinessData>(initialBusinessData);
  const [branches, setBranches] = useState<BranchData[]>([{ ...initialBranchData }]);

  const [register, { isLoading: isRegistering }] = useRegisterMutation();
  const [createBusiness, { isLoading: isCreatingBusiness }] = useCreateBusinessMutation();
  const [addBranch] = useAddBranchMutation();
  const { data: countriesData } = useCountriesQuery();
  const { data: businessCategories } = useGetPublicBusinessCategoriesQuery();

  const countries = countriesData?.data || [];
  const categories = businessCategories?.data || [];

  const getCountryName = (id: string) => {
    const country = countries.find((c: any) => String(c.id) === String(id));
    return country?.name || id;
  };

  const getCategoryName = (id: string) => {
    const category = categories.find((c: any) => String(c.id) === String(id));
    return category?.name || id;
  };

  const isLoading = isRegistering || isCreatingBusiness;

  const authenticate = async (email: string, password: string): Promise<string | null> => {
    try {
      const login = await fetch(`${import.meta.env.VITE_BASE_URL}/users/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ email, password }),
      }).then((r) => r.json());

      if (login?.data?.token) {
        localStorage.setItem('token', login.data.token);
        return login.data.token as string;
      }
    } catch {
      // Handled by the caller's fallback below.
    }
    return null;
  };

  const handleSave = async () => {
    try {
      await register({
        firstname: account.firstname,
        lastname: account.lastname,
        email: account.email,
        phone: account.phone,
        password: account.password,
        username: account.username,
      }).unwrap();

      const token = await authenticate(account.email, account.password);

      if (!token) {
        toast.success('Account created. Sign in to finish setting up your business.');
        return navigate('/login');
      }

      await createBusiness({
        name: business.name.trim(),
        business_category_id: Number(business.business_category_id),
        country_id: Number(business.country_id),
        email: business.email,
        phone: business.phone,
        address: business.address,
      }).unwrap();

      for (const branch of branches) {
        await addBranch({
          name: branch.name.trim(),
          address: branch.address.trim(),
          phone: branch.phone.trim(),
        }).unwrap();
      }

      toast.success('Welcome to DuukaFlow! Your 30-day free trial has started.');
      return navigate('/login');
    } catch (err: any) {
      const fieldErrors = err?.data?.errors;
      if (fieldErrors) {
        const first = Object.values(fieldErrors)[0] as string[];
        toast.error(first?.[0] || 'Please check the form and try again.');
        return;
      }
      toast.error(err?.data?.message || 'Something went wrong');
    }
  };

  const handleEdit = (step: number) => {
    setCurrentStep(step);
  };

  return (
    <div className='grid min-h-[calc(100vh-8rem)] place-items-center bg-background px-4 py-12'>
      <Card className='w-full max-w-lg border border-border/70 bg-card/95 shadow-xl'>
        <CardHeader className='space-y-1 px-6 pt-6'>
          <CardTitle className='text-2xl font-semibold'>Set up your business</CardTitle>
          <CardDescription className='text-sm text-muted-foreground'>
            Create your account and configure your business in a few steps.
          </CardDescription>
        </CardHeader>
        <CardContent className='px-6 pb-6 pt-4'>
          <div className='mb-6'>
            <Stepper currentStep={currentStep} steps={STEPS} />
          </div>

          {currentStep === 1 && (
            <AccountSetup data={account} onChange={setAccount} onNext={() => setCurrentStep(2)} />
          )}

          {currentStep === 2 && (
            <BusinessSetup
              data={business}
              onChange={setBusiness}
              onNext={() => setCurrentStep(3)}
              onBack={() => setCurrentStep(1)}
            />
          )}

          {currentStep === 3 && (
            <BranchSetup
              branches={branches}
              onChange={setBranches}
              onNext={() => setCurrentStep(4)}
              onBack={() => setCurrentStep(2)}
            />
          )}

          {currentStep === 4 && (
            <Preview
              account={account}
              business={{
                ...business,
                country_id: getCountryName(business.country_id),
                business_category_id: getCategoryName(business.business_category_id),
              }}
              branches={branches}
              onEdit={handleEdit}
              onSave={handleSave}
              isLoading={isLoading}
            />
          )}
        </CardContent>
      </Card>
    </div>
  );
};
