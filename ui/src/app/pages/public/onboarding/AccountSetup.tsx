import { Building, Lock, Mail, Phone, User as UserIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Field, FieldDescription, FieldError, FieldGroup, FieldLabel } from '@/components/ui/field';
import type { AccountData, StepErrors } from './types';

interface AccountSetupProps {
  data: AccountData;
  errors: StepErrors;
  onChange: (data: AccountData) => void;
  onNext: () => void;
}

/**
 * Step 1 — the owner's own account.
 *
 * Isolated: it holds no state of its own, only the account slice of the draft, so the
 * owner can leave for step 2 and come back without retyping.
 */
export const AccountSetup: React.FC<AccountSetupProps> = ({ data, errors, onChange, onNext }) => {
  const set = (field: keyof AccountData, value: string) => onChange({ ...data, [field]: value });

  return (
    <form
      noValidate
      onSubmit={(e) => {
        e.preventDefault();
        onNext();
      }}
      className='space-y-6'
    >
      <FieldGroup className='gap-5'>
        <div className='grid gap-5 sm:grid-cols-2'>
          <Field data-invalid={Boolean(errors.firstname)}>
            <FieldLabel htmlFor='firstname'>
              <UserIcon className='h-4 w-4 text-muted-foreground' aria-hidden />
              First name
            </FieldLabel>
            <Input
              id='firstname'
              name='firstname'
              autoComplete='given-name'
              value={data.firstname}
              onChange={(e) => set('firstname', e.target.value)}
              placeholder='Jane'
              aria-invalid={Boolean(errors.firstname)}
            />
            {errors.firstname && <FieldError>{errors.firstname}</FieldError>}
          </Field>

          <Field data-invalid={Boolean(errors.lastname)}>
            <FieldLabel htmlFor='lastname'>Last name</FieldLabel>
            <Input
              id='lastname'
              name='lastname'
              autoComplete='family-name'
              value={data.lastname}
              onChange={(e) => set('lastname', e.target.value)}
              placeholder='Doe'
              aria-invalid={Boolean(errors.lastname)}
            />
            {errors.lastname && <FieldError>{errors.lastname}</FieldError>}
          </Field>
        </div>

        <Field data-invalid={Boolean(errors.email)}>
          <FieldLabel htmlFor='email'>
            <Mail className='h-4 w-4 text-muted-foreground' aria-hidden />
            Email
          </FieldLabel>
          <Input
            id='email'
            name='email'
            type='email'
            autoComplete='email'
            value={data.email}
            onChange={(e) => set('email', e.target.value)}
            placeholder='you@example.com'
            aria-invalid={Boolean(errors.email)}
          />
          {errors.email && <FieldError>{errors.email}</FieldError>}
        </Field>

        <Field data-invalid={Boolean(errors.phone)}>
          <FieldLabel htmlFor='phone'>
            <Phone className='h-4 w-4 text-muted-foreground' aria-hidden />
            Phone
          </FieldLabel>
          <Input
            id='phone'
            name='phone'
            type='tel'
            autoComplete='tel'
            value={data.phone}
            onChange={(e) => set('phone', e.target.value)}
            placeholder='+256 700 000 000'
            aria-invalid={Boolean(errors.phone)}
          />
          {errors.phone ? (
            <FieldError>{errors.phone}</FieldError>
          ) : (
            <FieldDescription>Used to sign in and to reach you about your business.</FieldDescription>
          )}
        </Field>

        <Field data-invalid={Boolean(errors.password)}>
          <FieldLabel htmlFor='password'>
            <Lock className='h-4 w-4 text-muted-foreground' aria-hidden />
            Password
          </FieldLabel>
          <Input
            id='password'
            name='password'
            type='password'
            autoComplete='new-password'
            value={data.password}
            onChange={(e) => set('password', e.target.value)}
            aria-invalid={Boolean(errors.password)}
          />
          {errors.password && <FieldError>{errors.password}</FieldError>}
        </Field>

        <Field data-invalid={Boolean(errors.confirmPassword)}>
          <FieldLabel htmlFor='confirmPassword'>Confirm password</FieldLabel>
          <Input
            id='confirmPassword'
            name='confirmPassword'
            type='password'
            autoComplete='new-password'
            value={data.confirmPassword}
            onChange={(e) => set('confirmPassword', e.target.value)}
            aria-invalid={Boolean(errors.confirmPassword)}
          />
          {errors.confirmPassword && <FieldError>{errors.confirmPassword}</FieldError>}
        </Field>
      </FieldGroup>

      <div className='rounded-lg border border-border/60 bg-muted/40 p-3'>
        <p className='flex items-start gap-2 text-xs text-muted-foreground'>
          <Building className='mt-0.5 h-3.5 w-3.5 shrink-0' aria-hidden />
          This account will own the business you set up next, so it is the one that can
          invite staff and change settings later.
        </p>
      </div>

      <Button type='submit' className='w-full sm:w-auto'>
        Continue to business details
      </Button>
    </form>
  );
};