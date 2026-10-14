import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { MailWarning, RefreshCw, X } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { useLoggedinUserQuery } from '@/app/store/features/auth/authQuery';
import { useResendVerificationMutation } from '@/app/store/features/auth/verificationQuery';
import { RESEND_COOLDOWN_SECONDS } from '@/lib/verification';

/**
 * Dismissal is per-tab, not per-account and not forever.
 *
 * The nudge is meant to be soft: an owner who is mid-task can clear it out of the way,
 * but it is not a preference toggle they set once — a fresh session brings it back so
 * an unverified tenant does not quietly stop being reminded. sessionStorage is exactly
 * that lifetime.
 */
const DISMISS_KEY = 'duukaflow:verify-banner-dismissed';

const readDismissed = (): boolean => {
  try {
    return sessionStorage.getItem(DISMISS_KEY) === '1';
  } catch {
    return false;
  }
};

/**
 * The dashboard half of the soft gate.
 *
 * Shown on every dashboard page while the caller's business has no `email_verified_at`.
 * It only offers to resend or to open `/verify-email` — it never blocks a page, because
 * locking a working tenant out over an email that may have bounced would cost more than
 * the unverified state does. `data.email_verified` is the top-level flag on `me`.
 */
export const VerifyEmailBanner = () => {
  const { data } = useLoggedinUserQuery();
  const [resend, { isLoading }] = useResendVerificationMutation();
  const [cooldown, setCooldown] = useState(0);
  const [dismissed, setDismissed] = useState(readDismissed);

  useEffect(() => {
    if (cooldown <= 0) return;
    const id = setTimeout(() => setCooldown((seconds) => Math.max(0, seconds - 1)), 1000);
    return () => clearTimeout(id);
  }, [cooldown]);

  // Anything other than an explicit false — still loading, verified, or no business at
  // all — means there is nothing to nudge about. The business check matters because
  // `me` reports `email_verified: false` for a caller with no business (a platform
  // admin, or an owner still mid-onboarding), and neither should be told to verify.
  const hasBusiness = Boolean(data?.data?.business);
  if (dismissed || !hasBusiness || data?.email_verified !== false) return null;

  const handleDismiss = () => {
    try {
      sessionStorage.setItem(DISMISS_KEY, '1');
    } catch {
      // Storage unavailable: dismissal is cosmetic, so failing to persist it is fine.
    }
    setDismissed(true);
  };

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

  return (
    <div className='flex flex-wrap items-center gap-x-3 gap-y-2 border-b border-amber-300 bg-amber-50 px-4 py-2 text-sm text-amber-900 sm:px-6 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-200'>
      <MailWarning className='h-4 w-4 shrink-0' />
      <p className='min-w-0 flex-1'>Your email isn&apos;t verified yet. Verify it to secure your account.</p>
      <div className='flex shrink-0 items-center gap-1'>
        <Button
          type='button'
          variant='outline'
          size='sm'
          className='h-8 gap-1.5 border-amber-300 bg-amber-100 text-amber-900 hover:bg-amber-200 dark:border-amber-700 dark:bg-amber-900/40 dark:text-amber-100 dark:hover:bg-amber-900/70'
          disabled={isLoading || cooldown > 0}
          onClick={handleResend}
        >
          <RefreshCw className='h-3.5 w-3.5' />
          {cooldown > 0 ? `Resend in ${cooldown}s` : isLoading ? 'Sending…' : 'Resend'}
        </Button>
        <Button
          asChild
          variant='ghost'
          size='sm'
          className='h-8 text-amber-900 hover:bg-amber-100 dark:text-amber-100 dark:hover:bg-amber-900/40'
        >
          <Link to='/verify-email'>Verify now</Link>
        </Button>
        <Button
          type='button'
          variant='ghost'
          size='icon'
          className='h-7 w-7 text-amber-900 hover:bg-amber-100 dark:text-amber-100 dark:hover:bg-amber-900/40'
          aria-label='Dismiss verification reminder'
          onClick={handleDismiss}
        >
          <X className='h-4 w-4' />
        </Button>
      </div>
    </div>
  );
};
