import type { OnboardingDraft } from './types';
import { initialDraft } from './types';

const STORAGE_KEY = 'duukaflow:onboarding-draft';

/**
 * Keep the draft in sessionStorage so a refresh does not throw away the work.
 *
 * Onboarding asks for an account, a business and at least one branch — a few minutes
 * of typing on a phone, on the connection plan.md is written for. Losing it to an
 * accidental refresh is the kind of thing ends a signup. sessionStorage rather than
 * localStorage because this is deliberately not a resumable draft across visits: the
 * account half of it includes a password, and nothing about it should outlive the tab.
 *
 * A parse failure is treated as "no draft" rather than thrown: a corrupt value from an
 * older build must not leave the owner stuck on a blank form with no way forward.
 */
export const readDraft = (): OnboardingDraft => {
  try {
    const raw = sessionStorage.getItem(STORAGE_KEY);

    if (!raw) return initialDraft;

    const parsed = JSON.parse(raw) as Partial<OnboardingDraft>;

    return {
      account: { ...initialDraft.account, ...(parsed.account ?? {}) },
      business: { ...initialDraft.business, ...(parsed.business ?? {}) },
      branches:
        Array.isArray(parsed.branches) && parsed.branches.length > 0 ? parsed.branches : initialDraft.branches,
      accountCreated: parsed.accountCreated === true,
    };
  } catch {
    return initialDraft;
  }
};

export const writeDraft = (draft: OnboardingDraft): void => {
  try {
    // The password is never written back. If the tab is closed mid-onboarding the owner
    // is asked for it again, which is the correct trade against leaving a credential in
    // web storage for the life of the tab.
    sessionStorage.setItem(
      STORAGE_KEY,
      JSON.stringify({ ...draft, account: { ...draft.account, password: '', confirmPassword: '' } })
    );
  } catch {
    // A full or disabled storage quota is not worth interrupting the signup for.
  }
};

export const clearDraft = (): void => {
  try {
    sessionStorage.removeItem(STORAGE_KEY);
  } catch {
    // Nothing to do: the draft is per-tab and disappears with it either way.
  }
};