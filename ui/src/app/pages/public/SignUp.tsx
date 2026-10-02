import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { UserPlus, Mail, Phone, Lock, Globe } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { DASHBOARD_PREFIX } from '@/lib/rolePrefix';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { AuthLayout } from './AuthLayout';
import { toast } from 'sonner';
import {
  useLoggedinUserQuery,
  useRegisterMutation,
  useUpdateUserMutation,
} from '@/app/store/features/auth/authQuery';
import { useCountriesQuery } from '@/app/store/features/countries/countriesQuery';
import {
  useCreateBusinessMutation,
  useGetPublicBusinessCategoriesQuery,
} from '@/app/store/features/business/setup/onboardingQuery';
import { LoadingState } from '@/utils/LoadingState';

export const SignUp: React.FC = () => {
  const navigate = useNavigate();

  // Signup is one screen on purpose. The backend creates the account first and the
  // business second (RequireBusiness blocks every tenant route until that happens), but
  // asking the owner for both halves in one form keeps it to a single submit, which is
  // what a cold visitor who found us through a search result will tolerate.
  const [formState, setFormState] = useState({
    firstname: '',
    lastname: '',
    email: '',
    phone: '',
    password: '',
    confirmPassword: '',
    country_id: '',
    business_name: '',
    business_category_id: '',
  });

  const [register, { isLoading }] = useRegisterMutation();
  const [createBusiness, { isLoading: isCreatingBusiness }] = useCreateBusinessMutation();
  const [updateUser, { isLoading: loadUpdate }] = useUpdateUserMutation();
  const { data } = useLoggedinUserQuery();
  const { data: countriesData } = useCountriesQuery();
  const { data: businessCategories } = useGetPublicBusinessCategoriesQuery();
  const countries = countriesData?.data || [];
  // The categories endpoint returns the collection bare, not under a `data` key.
  const categories = businessCategories || [];

  // Prefill when editing
  useEffect(() => {
    if (data?.data) {
      setFormState((prev) => ({
        ...prev,
        firstname: data.data.firstname || '',
        lastname: data.data.lastname || '',
        email: data.data.email || '',
        phone: data.data.phone || '',
        password: '',
        confirmPassword: '',
      }));
    }
  }, [data]);

  const handleChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = event.target;
    setFormState((prev) => ({ ...prev, [name]: value }));
  };

  /**
   * Exchange the just-chosen credentials for a token.
   *
   * Signup does not return a token, so it has to happen before business creation:
   * the business write is authenticated, and a business-less account is exactly the
   * state RequireBusiness is written to admit to onboarding. Doing it in this order
   * also means the user never has to retype the password they just chose.
   *
   * Returns the token, or null if sign-in failed.
   */
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

  const handleSubmit = async (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();

    if (data) {
      try {
        const res = await updateUser({
          firstname: formState.firstname,
          lastname: formState.lastname,
          email: formState.email,
          phone: formState.phone,
        }).unwrap();
        if (res) {
          toast.success(res.message);
        }
        return (window.location.href = DASHBOARD_PREFIX);
      } catch (err: any) {
        toast.error(err?.data?.message || 'Something went wrong');
      }
      return;
    }

    // Caught before the request rather than as a 422 the user cannot act on.
    if (formState.password !== formState.confirmPassword) {
      toast.error('Passwords do not match.');
      return;
    }

    try {
      await register({
        firstname: formState.firstname,
        lastname: formState.lastname,
        email: formState.email,
        phone: formState.phone,
        password: formState.password,
      }).unwrap();

      const token = await authenticate(formState.email, formState.password);

      if (!token) {
        // The account is real, so do not imply failure. Onboarding can be finished
        // after signing in normally.
        toast.success('Account created. Sign in to finish setting up your business.');
        return navigate('/login');
      }

      // Both of these are NOT NULL columns on businesses, so the API rejects a signup
      // that skipped them. Guarded here to give a readable message instead of a 422.
      if (
        !formState.business_name.trim() ||
        !formState.business_category_id ||
        !formState.country_id
      ) {
        toast.success('Account created. Finish setting up your business after signing in.');
        return navigate('/login');
      }

      await createBusiness({
        name: formState.business_name.trim(),
        business_category_id: Number(formState.business_category_id),
        country_id: Number(formState.country_id),
      }).unwrap();

      toast.success('Welcome to DuukaFlow! Your 30-day free trial has started.');

      // A brand new signup has no role until business creation provisions one, so there
      // is no dashboard to route to yet. Sign in, which resolves a role and lands in
      // the app.
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

  return (
    <AuthLayout
      title={data ? 'Update account' : 'Create an account'}
      description={
        data
          ? 'Update your profile for DuukaFlow'
          : 'Sign up for DuukaFlow and get started with fast inventory management.'
      }
    >
      <form onSubmit={handleSubmit} className='space-y-5'>
        {/* First and last name — stored separately, so they are collected separately */}
        <div className='grid grid-cols-1 gap-4 sm:grid-cols-2'>
          <div className='space-y-2'>
            <Label htmlFor='firstname'>
              <UserPlus className='h-4 w-4 text-muted-foreground' />
              First name
            </Label>
            <Input
              id='firstname'
              name='firstname'
              type='text'
              autoComplete='given-name'
              value={formState.firstname}
              onChange={handleChange}
              placeholder='Jane'
              required
              minLength={2}
            />
          </div>

          <div className='space-y-2'>
            <Label htmlFor='lastname'>Last name</Label>
            <Input
              id='lastname'
              name='lastname'
              type='text'
              autoComplete='family-name'
              value={formState.lastname}
              onChange={handleChange}
              placeholder='Doe'
            />
          </div>
        </div>

        {/* Email */}
        <div className='space-y-2'>
          <Label htmlFor='email'>
            <Mail className='h-4 w-4 text-muted-foreground' />
            Email
          </Label>
          <Input
            id='email'
            name='email'
            type='email'
            autoComplete='email'
            value={formState.email}
            onChange={handleChange}
            placeholder='you@example.com'
          />
        </div>

        {/* Phone */}
        <div className='space-y-2'>
          <Label htmlFor='phone'>
            <Phone className='h-4 w-4 text-muted-foreground' />
            Phone
          </Label>
          <Input
            id='phone'
            name='phone'
            type='tel'
            autoComplete='tel'
            value={formState.phone}
            onChange={handleChange}
            placeholder='+256 700 000 000'
          />
        </div>

        {/* Country */}
        <div className='space-y-2'>
          <Label htmlFor='country'>
            <Globe className='h-4 w-4 text-muted-foreground' />
            Country
          </Label>
          <Select
            value={formState.country_id}
            onValueChange={(value) => setFormState((prev: any) => ({ ...prev, country_id: value }))}
          >
            <SelectTrigger className='w-full'>
              <SelectValue placeholder='Select your country' />
            </SelectTrigger>
            <SelectContent>
              {countries.map((country: any) => (
                <SelectItem key={country.id} value={String(country.id)}>
                  <span className='flex items-center gap-2'>
                    <span className='text-lg'>{country.flag_emoji}</span>
                    <span>{country.name}</span>
                  </span>
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        {/* Password + confirmation */}
        {!data && (
          <>
          <div className='space-y-2'>
            <Label htmlFor='password'>
              <Lock className='h-4 w-4 text-muted-foreground' />
              Password
            </Label>
            <Input
              id='password'
              name='password'
              type='password'
              autoComplete='new-password'
              value={formState.password}
              onChange={handleChange}
              placeholder='Enter a secure password'
              required
              minLength={6}
            />
          </div>

          <div className='space-y-2'>
            <Label htmlFor='confirmPassword'>Confirm password</Label>
            <Input
              id='confirmPassword'
              name='confirmPassword'
              type='password'
              autoComplete='new-password'
              value={formState.confirmPassword}
              onChange={handleChange}
              placeholder='Re-enter your password'
              required
              minLength={6}
              aria-invalid={formState.confirmPassword.length > 0 && formState.password !== formState.confirmPassword}
            />
            {formState.confirmPassword.length > 0 &&
              formState.password !== formState.confirmPassword && (
                <p className='text-xs text-destructive'>Passwords do not match.</p>
              )}
          </div>
          </>
        )}

        {/* Business details — collected on the same screen so onboarding is one submit */}
        {!data && (
          <div className='space-y-5 rounded-lg border border-border/60 p-4'>
            <div className='space-y-1'>
              <p className='text-sm font-medium'>Your business</p>
              <p className='text-xs text-muted-foreground'>
                Used to set up your workspace. You can change these later in settings.
              </p>
            </div>

            <div className='space-y-2'>
              <Label htmlFor='business_name'>Business name</Label>
              <Input
                id='business_name'
                name='business_name'
                type='text'
                autoComplete='organization'
                value={formState.business_name}
                onChange={handleChange}
                placeholder='Jane&apos;s Shop'
                required
              />
            </div>

            <div className='space-y-2'>
              <Label htmlFor='business_category_id'>Business type</Label>
              <Select
                value={formState.business_category_id}
                onValueChange={(value) =>
                  setFormState((prev) => ({ ...prev, business_category_id: value }))
                }
              >
                <SelectTrigger id='business_category_id' className='w-full'>
                  <SelectValue placeholder='Select your business type' />
                </SelectTrigger>
                <SelectContent>
                  {categories.map((category: any) => (
                    <SelectItem key={category.id} value={String(category.id)}>
                      {category.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          </div>
        )}

        <Button type='submit' className='w-full' disabled={isLoading || loadUpdate || isCreatingBusiness}>
          {isLoading || loadUpdate || isCreatingBusiness ? (
            <LoadingState />
          ) : data ? (
            'Update account'
          ) : (
            'Create account & start free trial'
          )}
        </Button>
      </form>

      {!data && (
        <p className='text-center text-sm text-muted-foreground'>
          Already have an account?{' '}
          <Link className='font-semibold text-primary hover:underline' to='/login'>
            Login
          </Link>
        </p>
      )}
    </AuthLayout>
  );
};