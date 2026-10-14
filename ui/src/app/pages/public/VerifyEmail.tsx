import { useEffect, useState } from 'react';
import { Link, useLocation, useSearchParams } from 'react-router-dom';
import { CheckCircle2, MailCheck, MailWarning, RefreshCw } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { AuthLayout } from './AuthLayout';
import { useLoggedinUserQuery } from '@/app/store/features/auth/authQuery';
import {
  useResendVerificationMutation,
  useVerifyEmailQuery,
} from '@/app/store/features/auth/verificationQuery';
import { getToken } from '@/lib/session';
import {
  clearVerificationEmail,
  readVerificationEmail,
} from '@/lib/verification';

/**
 * How long the resend button stays disabled after a send.
 *
 * Matches the API's per-business cooldown, so the button and the server agree and a
 * double-click cannot produce a "sent" toast followed by a silently dropped mail.
 */
const RESEND_COOLDOWN_SECONDS = 60;

/**
 * The page the verification email links to, and the page onboarding hands off to.
 *
 * One route, three states chosen by the query string:
 *   ?token=…     the emailed link — verify it, then report success or failure
 *   ?verified=1  the API's browser redirect after it verified the token itself
 *   (nothing)    "we sent you an email" — the check-inbox screen after signup
 *
 * Keeping them on one path is deliberate: the link baked into thousands of sent
 * emails is `/verify-email`, and a second page would have meant either repointing
 * that link or a redirect hop.
 */
export const VerifyEmail: React.FC = () => {
  const [searchParams] = useSearchParams();
  const location = useLocation();

  const token = searchParams.get('token');
  const verifiedByApi = searchParams.get('verified') === '1';
  const errorParam = searchParams.get('error');

  const hasToken = Boolean(getToken());
  const { data: session } = useLoggedinUserQuery({ skip: !hasToken });

  const {
    isFetching: isVerifying,
    isSuccess: verifiedViaLink,
    isError: verifyFailed,
  } = useVerifyEmailQuery(token ?? '', { skip: !token });

  const [resend, { isLoading: isResending }] = useResendVerificationMutation();
  const [cooldown, setCooldown] = useState(0);

  // A one-second tick that counts the cooldown down. Keyed on the value itself so the
  // timer is recreated each second rather than left running after it reaches zero.
  useEffect(() => {
    if (cooldown <= 0) return;
    const id = setTimeout(() => setCooldown((seconds) => Math.max(0, seconds - 1)), 1000);
    return () => clearTimeout(id);
  }, [cooldown]);

  // Drop the remembered address once the email is confirmed — there is nothing left to
  // check an inbox for, and the next signup on this tab should not inherit this one.
  useEffect(() => {
    if (verifiedViaLink || verifiedByApi) {
      clearVerificationEmail();
    }
  }, [verifiedViaLink, verifiedByApi]);

  const stateEmail = (location.state as { email?: string } | null)?.email;
  const email =
    stateEmail || readVerificationEmail() || session?.data?.business?.email || null;

  const handleResend = async () => {
    try {
      const result = await resend().unwrap();
      setCooldown(RESEND_COOLDOWN_SECONDS);
      toast.success(result?.message ?? 'Verification email sent');
    } catch (error) {
      const status = (error as { status?: number } | undefined)?.status;
      toast.error(
        status === 401
          ? 'Please sign in to resend the verification email.'
          : 'We could not send the email. Please try again.'
      );
    }
  };

  const continueTarget = hasToken ? '/dashboard' : '/login';
  const continueLabel = hasToken ? 'Continue to dashboard' : 'Sign in';

  // The link was opened, and the token was rejected: used, expired, or never real.
  if (token && verifyFailed) {
    return (
      <AuthLayout
        title='Link expired'
        description='This verification link is invalid, has already been used, or has expired.'
      >
        <div className='space-y-6'>
          <MailWarning className='h-10 w-10 text-amber-500' />
          <p className='text-sm text-muted-foreground'>
            Verification links last 24 hours. Request a fresh one and we will email it
            again.
          </p>
          <ResendButton
            hasSession={hasToken}
            isResending={isResending}
            cooldown={cooldown}
            onResend={handleResend}
          />
          <Button asChild variant='ghost' className='w-full'>
            <Link to={continueTarget}>{continueLabel}</Link>
          </Button>
        </div>
      </AuthLayout>
    );
  }

  // Still talking to the API, or the token is present and the answer has not landed.
  if (token && isVerifying && !verifiedViaLink) {
    return (
      <AuthLayout title='Verifying your email' description='One moment while we confirm your link.'>
        <div className='flex justify-center py-6'>
          <Spinner className='size-8' />
        </div>
      </AuthLayout>
    );
  }

  // Success, from either the link itself or the API's own redirect.
  if (verifiedViaLink || verifiedByApi) {
    return (
      <AuthLayout
        title='Email verified'
        description='Your business is active. You are all set.'
      >
        <div className='space-y-6'>
          <CheckCircle2 className='h-10 w-10 text-emerald-500' />
          <Button asChild className='w-full'>
            <Link to={continueTarget}>{continueLabel}</Link>
          </Button>
        </div>
      </AuthLayout>
    );
  }

  // The API bounced the browser back with a message (bad or missing token).
  if (errorParam) {
    return (
      <AuthLayout title='Verification failed' description={errorParam}>
        <Button asChild variant='ghost' className='w-full'>
          <Link to={continueTarget}>{continueLabel}</Link>
        </Button>
      </AuthLayout>
    );
  }

  // No token, no verdict: the check-inbox screen after onboarding.
  return (
    <AuthLayout
      title='Check your inbox'
      description={
        email
          ? `We sent a verification link to ${email}.`
          : 'We sent you a verification link.'
      }
    >
      <div className='space-y-6'>
        <MailCheck className='h-10 w-10 text-primary' />
        <p className='text-sm text-muted-foreground'>
          Click the link to activate your business and secure your account. It expires in
          24 hours. Can&apos;t find it? Check your spam folder.
        </p>
        <ResendButton
          hasSession={hasToken}
          isResending={isResending}
          cooldown={cooldown}
          onResend={handleResend}
        />
        {/* Soft gate: the dashboard is reachable without verifying, so this is a
            shortcut past the reminder, not a way to skip it. */}
        <Button asChild variant='ghost' className='w-full'>
          <Link to={continueTarget}>{continueLabel}</Link>
        </Button>
      </div>
    </AuthLayout>
  );
};

interface ResendButtonProps {
  hasSession: boolean;
  isResending: boolean;
  cooldown: number;
  onResend: () => void;
}

const ResendButton = ({ hasSession, isResending, cooldown, onResend }: ResendButtonProps) => {
  if (!hasSession) {
    return (
      <p className='text-sm text-muted-foreground'>
        <Link to='/login' className='font-semibold text-primary hover:underline'>
          Sign in
        </Link>{' '}
        to send another verification email.
      </p>
    );
  }

  return (
    <Button
      type='button'
      variant='outline'
      className='w-full'
      disabled={isResending || cooldown > 0}
      onClick={onResend}
    >
      <RefreshCw className='h-4 w-4' />
      {cooldown > 0 ? `Resend in ${cooldown}s` : isResending ? 'Sending…' : 'Resend email'}
    </Button>
  );
};
