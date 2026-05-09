import { useState } from 'react';
import { Link } from 'react-router-dom';
import { Lock, Mail, Eye, EyeOff } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { AuthLayout } from './AuthLayout';
import { useLoginMutation } from '@/app/store/features/auth/authQuery';
import { LoadingState } from '@/utils/LoadingState';
import { toast } from 'sonner';

export const Login: React.FC = () => {
  const [formState, setFormState] = useState({ email: '', password: '' });
  const [showPassword, setShowPassword] = useState(false);
  const [login, { isLoading }] = useLoginMutation();
  const handleChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = event.target;
    setFormState((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (event: React.SyntheticEvent<HTMLFormElement>) => {
    event.preventDefault();
    try {
      const res = await login(formState).unwrap();
      console.log('res from login==>', res);
      const token = await res.data.token;
      console.log('token==>', token);
      toast.success(res.message);
      localStorage.setItem('token', token);

      return (window.location.href = '/admin');
    } catch (error) {
      console.error('Login failed:', error);
    }
  };

  return (
    <AuthLayout title='Welcome back' description='Sign in to your DuukaFlow account to continue managing inventory.'>
      <form onSubmit={handleSubmit} className='space-y-6'>
        {/* Email Field */}
        <div className='space-y-2'>
          <Label htmlFor='email' className='flex items-center gap-2'>
            <Mail className='h-4 w-4 text-muted-foreground' />
            Email Address
          </Label>
          <Input
            id='email'
            name='email'
            type='email'
            autoComplete='email'
            value={formState.email}
            onChange={handleChange}
            placeholder='you@example.com'
            required
          />
        </div>

        {/* Password Field */}
        <div className='space-y-2'>
          <Label htmlFor='password' className='flex items-center gap-2'>
            <Lock className='h-4 w-4 text-muted-foreground' />
            Password
          </Label>
          <div className='relative'>
            <Input
              id='password'
              name='password'
              type={showPassword ? 'text' : 'password'}
              autoComplete='current-password'
              value={formState.password}
              onChange={handleChange}
              placeholder='••••••••'
              required
            />
            <button
              type='button'
              onClick={() => setShowPassword(!showPassword)}
              className='absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground transition'
            >
              {showPassword ? <EyeOff className='h-4 w-4' /> : <Eye className='h-4 w-4' />}
            </button>
          </div>
        </div>

        <Button type='submit' className='w-full' disabled={isLoading}>
          {isLoading ? <LoadingState /> : 'Sign in'}
        </Button>
      </form>

      <p className='text-center text-sm text-muted-foreground mt-6'>
        Don&apos;t have an account?{' '}
        <Link to='/signup' className='font-semibold text-primary hover:underline'>
          Sign up
        </Link>
      </p>
    </AuthLayout>
  );
};
