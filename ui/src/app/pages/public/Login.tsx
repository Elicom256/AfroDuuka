import { useState } from 'react';
import type { ChangeEvent, FormEvent } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Lock, Mail } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { AuthLayout } from './AuthLayout';

const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

export const Login: React.FC = () => {
  const navigate = useNavigate();
  const [formState, setFormState] = useState({ email: '', password: '' });
  const [submitted, setSubmitted] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  const emailError = !formState.email
    ? 'Please enter your email.'
    : !emailRegex.test(formState.email)
    ? 'Enter a valid email address.'
    : '';

  const passwordError = !formState.password ? 'Please enter your password.' : '';

  const handleChange = (event: ChangeEvent<HTMLInputElement>) => {
    const { name, value } = event.target;
    setFormState((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setSubmitted(true);

    if (emailError || passwordError) {
      return;
    }

    setSubmitting(true);
    window.setTimeout(() => {
      setSubmitting(false);
      navigate('/');
    }, 700);
  };

  return (
    <AuthLayout
      title='Welcome back'
      description='Sign in to your DuukaFlow account to continue managing inventory.'
    >
      <form onSubmit={handleSubmit} className='space-y-5'>
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
            aria-invalid={Boolean(submitted && emailError)}
            aria-describedby='email-error'
            placeholder='you@example.com'
          />
          {submitted && emailError ? (
            <p id='email-error' className='text-sm text-destructive'>
              {emailError}
            </p>
          ) : null}
        </div>

        <div className='space-y-2'>
          <Label htmlFor='password'>
            <Lock className='h-4 w-4 text-muted-foreground' />
            Password
          </Label>
          <Input
            id='password'
            name='password'
            type='password'
            autoComplete='current-password'
            value={formState.password}
            onChange={handleChange}
            aria-invalid={Boolean(submitted && passwordError)}
            aria-describedby='password-error'
            placeholder='••••••••'
          />
          {submitted && passwordError ? (
            <p id='password-error' className='text-sm text-destructive'>
              {passwordError}
            </p>
          ) : null}
        </div>

        <Button type='submit' className='w-full' disabled={submitting}>
          {submitting ? 'Signing in…' : 'Sign in'}
        </Button>
      </form>

      <p className='text-center text-sm text-muted-foreground'>
        Don&apos;t have an account?{' '}
        <Link className='font-semibold text-primary hover:underline' to='/signup'>
          Sign up
        </Link>
      </p>
    </AuthLayout>
  );
};
