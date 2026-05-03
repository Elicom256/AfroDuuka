import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { UserPlus, Mail, Phone, Lock } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { AuthLayout } from './AuthLayout';
import { toast } from 'sonner';

const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

export const SignUp: React.FC = () => {
  const navigate = useNavigate();
  const [formState, setFormState] = useState({ name: '', email: '', phone: '', password: '' });
  const [submitted, setSubmitted] = useState(false);
  const [submitting, setSubmitting] = useState(false);

  const nameError = !formState.name ? 'Please enter your name.' : '';
  const emailError = !formState.email
    ? 'Please enter your email.'
    : !emailRegex.test(formState.email)
      ? 'Enter a valid email address.'
      : '';
  const phoneError = !formState.phone ? 'Please enter your phone number.' : '';
  const passwordError = !formState.password
    ? 'Please create a password.'
    : formState.password.length < 6
      ? 'Password must be at least 6 characters.'
      : '';

  const handleChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    const { name, value } = event.target;
    setFormState((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setSubmitted(true);

    if (nameError || emailError || phoneError || passwordError) {
      return;
    }

    setSubmitting(true);
    toast.success('Your account was created successfully!');

    window.setTimeout(() => {
      setSubmitting(false);
      navigate('/login');
    }, 700);
  };

  return (
    <AuthLayout
      title='Create an account'
      description='Sign up for DuukaFlow and get started with fast inventory management.'
    >
      <form onSubmit={handleSubmit} className='space-y-5'>
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
            aria-invalid={Boolean(submitted && nameError)}
            aria-describedby='name-error'
            placeholder='Jane Doe'
          />
          {submitted && nameError ? (
            <p id='name-error' className='text-sm text-destructive'>
              {nameError}
            </p>
          ) : null}
        </div>

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
            aria-invalid={Boolean(submitted && phoneError)}
            aria-describedby='phone-error'
            placeholder='+256 700 000 000'
          />
          {submitted && phoneError ? (
            <p id='phone-error' className='text-sm text-destructive'>
              {phoneError}
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
            autoComplete='new-password'
            value={formState.password}
            onChange={handleChange}
            aria-invalid={Boolean(submitted && passwordError)}
            aria-describedby='password-error'
            placeholder='Enter a secure password'
          />
          {submitted && passwordError ? (
            <p id='password-error' className='text-sm text-destructive'>
              {passwordError}
            </p>
          ) : null}
        </div>

        <Button type='submit' className='w-full' disabled={submitting}>
          {submitting ? 'Creating account…' : 'Create account'}
        </Button>
      </form>

      <p className='text-center text-sm text-muted-foreground'>
        Already have an account?{' '}
        <Link className='font-semibold text-primary hover:underline' to='/login'>
          Login
        </Link>
      </p>
    </AuthLayout>
  );
};
