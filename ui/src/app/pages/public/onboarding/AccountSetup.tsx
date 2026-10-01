import { UserPlus, Mail, Phone, Lock } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { toast } from 'sonner';
import type { AccountData } from './types';

interface AccountSetupProps {
  data: AccountData;
  onChange: (data: AccountData) => void;
  onNext: () => void;
}

export const AccountSetup: React.FC<AccountSetupProps> = ({ data, onChange, onNext }) => {
  // Auto-generate username from firstname
  const generatedUsername = `@${data.firstname.toLowerCase().replace(/\s/g, '')}`;

  const handleChange = (field: keyof AccountData, value: string) => {
    // Re-generate username when firstname changes
    if (field === 'firstname') {
      onChange({ ...data, [field]: value, username: `@${value.toLowerCase().replace(/\s/g, '')}` });
    } else {
      onChange({ ...data, [field]: value });
    }
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    if (data.password !== data.confirmPassword) {
      toast.error('Passwords do not match.');
      return;
    }

    onNext();
  };

  return (
    <form onSubmit={handleSubmit} className='space-y-5'>
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
            value={data.firstname}
            onChange={(e) => handleChange('firstname', e.target.value)}
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
            value={data.lastname}
            onChange={(e) => handleChange('lastname', e.target.value)}
            placeholder='Doe'
            required
          />
        </div>
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
          value={data.email}
          onChange={(e) => handleChange('email', e.target.value)}
          placeholder='you@example.com'
          required
        />
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
          value={data.phone}
          onChange={(e) => handleChange('phone', e.target.value)}
          placeholder='+256 700 000 000'
          required
        />
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
          value={data.password}
          onChange={(e) => handleChange('password', e.target.value)}
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
          value={data.confirmPassword}
          onChange={(e) => handleChange('confirmPassword', e.target.value)}
          placeholder='Re-enter your password'
          required
          minLength={6}
          aria-invalid={
            data.confirmPassword.length > 0 && data.password !== data.confirmPassword
          }
        />
        {data.confirmPassword.length > 0 && data.password !== data.confirmPassword && (
          <p className='text-xs text-destructive'>Passwords do not match.</p>
        )}
      </div>

      <div className='space-y-2'>
        <p className='text-sm text-muted-foreground'>
          Suggested username: <strong className='text-primary'>{generatedUsername}</strong>
        </p>
      </div>

      <Button type='submit' className='w-full'>
        Continue
      </Button>
    </form>
  );
};