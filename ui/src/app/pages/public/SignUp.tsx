import { useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { UserPlus, Mail, Phone, Lock, Globe } from 'lucide-react';
import { Button } from '@/components/ui/button';
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
import { LoadingState } from '@/utils/LoadingState';

export const SignUp: React.FC = () => {
  const navigate = useNavigate();

  const [formState, setFormState] = useState<any>({
    name: '',
    email: '',
    phone: '',
    password: '',
    role: '',
    username: '',
    country_id: '',
  });

  const [register, { isLoading }] = useRegisterMutation();
  const [updateUser, { isLoading: loadUpdate }] = useUpdateUserMutation();
  const { data } = useLoggedinUserQuery();
  const { data: countriesData } = useCountriesQuery();
  const countries = countriesData?.data || [];
  // Prefill when editing
  useEffect(() => {
    if (data?.data) {
      setFormState({
        name: data.data.name || '',
        email: data.data.email || '',
        phone: data.data.phone || '',
        password: '',
        role: data.data.role || '',
        username: data.data.username || '',
        country_id: data.data.country_id || '',
      });
    }
  }, [data]);

  const handleChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = event.target;
    setFormState((prev: any) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (event: React.SyntheticEvent<HTMLFormElement>) => {
    event.preventDefault();

    try {
      if (data) {
        // TODO: replace with update mutation when ready
        const res = await updateUser(formState).unwrap();
        console.log('update res==>', res);
        if (res) {
          toast.success(res.message);
        }
        return (window.location.href = '/admin');
      } else {
        await register(formState).unwrap();
        toast.success('Account created successfully');
        navigate('/login');
      }
    } catch (err: any) {
      console.error(err);
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
        {/* Name */}
        <div className='space-y-2'>
          <Label htmlFor='name'>
            <UserPlus className='h-4 w-4 text-muted-foreground' />
            Name
          </Label>
          <Input
            id='name'
            name='name'
            type='text'
            autoComplete='name'
            value={formState.name}
            onChange={handleChange}
            placeholder='Jane Doe'
            required
          />
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

        {/* Password */}
        {!data && (
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
            />
          </div>
        )}

        {/* Role (ONLY when editing) */}
        {data && (
          <div className=''>
            {/* <div className='space-y-2'>
              <Label>Role</Label>
              <Select
                value={formState.role}
                onValueChange={(value) => setFormState((prev: any) => ({ ...prev, role: value }))}
              >
                <SelectTrigger>
                  <SelectValue placeholder='Select role' />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value='admin'>Admin</SelectItem>
                  <SelectItem value='user'>User</SelectItem>
                </SelectContent>
              </Select>
            </div> */}

            {/* username */}
            <div className='space-y-2'>
              <Label htmlFor='name'>
                <UserPlus className='h-4 w-4 text-muted-foreground' />
                Name
              </Label>
              <Input
                id='username'
                name='username'
                type='text'
                autoComplete='username'
                value={formState.username}
                onChange={handleChange}
                placeholder='@john'
                required
              />
            </div>
          </div>
        )}

        <Button type='submit' className='w-full' disabled={isLoading}>
          {isLoading || loadUpdate ? <LoadingState /> : data ? 'Update account' : 'Create account'}
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