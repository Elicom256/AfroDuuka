import { useEffect, useMemo, useRef, useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { toast } from 'sonner';
import {
  useLoginMutation,
  useLoggedinUserQuery,
  useRegisterMutation,
} from '@/app/store/features/auth/authQuery';
import {
  useCreateBusinessMutation,
  useGetPublicBusinessCategoriesQuery,
} from '@/app/store/features/business/setup/onboardingQuery';
import { useCountriesQuery } from '@/app/store/features/countries/countriesQuery';
import { getToken, setToken } from '@/lib/session';
import { Stepper, type StepperStep } from './onboarding/Stepper';
import { AccountSetup } from './onboarding/AccountSetup';
import { BusinessSetup } from './onboarding/BusinessSetup';
import { BranchSetup } from './onboarding/BranchSetup';
import { Preview } from './onboarding/Preview';
import { clearDraft, readDraft, writeDraft } from './onboarding/draft';
import { validateStep, type OnboardingDraft, type StepErrors } from './onboarding/types';

const STEPS: StepperStep[] = [
  { label: 'Account' },
  { label: 'Business' },
  { label: 'Branches' },
  { label: 'Preview' },
];

type StepKey = 'account' | 'business' | 'branches';

/** Read the first field error the API returned, in the shape both error shapes take. */
const firstFieldError = (error: unknown): string | undefined => {
  const errors = (error as { data?: { errors?: Record<string, string[] | string> } })?.data?.errors;

  if (!errors) return undefined;

  const first = Object.values(errors)[0];

  return Array.isArray(first) ? first[0] : (first as string | undefined);
};

export const Onboarding: React.FC = () => {
  const [draft, setDraft] = useState<OnboardingDraft>(readDraft);
  const [currentStep, setCurrentStep] = useState(1);
  const [furthestStep, setFurthestStep] = useState(1);
  const [errors, setErrors] = useState<StepErrors>({});

  const { data: session } = useLoggedinUserQuery();
  const [register, { isLoading: isRegistering }] = useRegisterMutation();
  const [login, { isLoading: isLoggingIn }] = useLoginMutation();
  const [createBusiness, { isLoading: isCreating }] = useCreateBusinessMutation();
  const { data: countriesData } = useCountriesQuery();
  const { data: categories } = useGetPublicBusinessCategoriesQuery();

  // The signed-in user decides whether step 1 exists at all: someone who signed up and
  // walked away has an account but no business, and RequireBusiness is holding every
  // tenant route at 403 until they finish. Asking them for their email and password
  // again on that screen would be nonsense.
  const isSignedIn = Boolean(session?.data?.id);
  const firstStep = isSignedIn ? 2 : 1;

  useEffect(() => {
    if (isSignedIn && currentStep === 1) {
      setCurrentStep(2);
      setFurthestStep(2);
    }
  }, [isSignedIn, currentStep]);

  useEffect(() => {
    writeDraft(draft);
  }, [draft]);

  const countries = useMemo(() => countriesData?.data ?? [], [countriesData]);
  const names = useMemo(
    () => ({
      country: countries.find((c) => String(c.id) === draft.business.country_id)?.name ?? '',
      category: categories?.find((c) => String(c.id) === draft.business.business_category_id)?.name ?? '',
    }),
    [countries, categories, draft.business.country_id, draft.business.business_category_id]
  );

  const isSaving = isRegistering || isLoggingIn || isCreating;
  const submittedRef = useRef(false);

  const validate = (step: StepKey): boolean => {
    const found = validateStep(step, draft);
    setErrors(found);

    return Object.keys(found).length === 0;
  };

  /**
   * Move forward one step, refusing to leave the current one invalid.
   *
   * Validating on leave rather than on submit is what makes the sections isolated: the
   * owner is told what is missing while the form they are looking at is still on
   * screen, instead of at the bottom of the preview three steps later.
   */
  const goNext = () => {
    const step = STEPS[currentStep - 1].label.toLowerCase() as StepKey;

    if (!validate(step)) return;

    const next = Math.min(currentStep + 1, STEPS.length);
    setCurrentStep(next);
    setFurthestStep((furthest) => Math.max(furthest, next));
    setErrors({});
  };

  const goBack = () => {
    setCurrentStep((step) => Math.max(step - 1, firstStep));
    setErrors({});
  };

  const goTo = (step: number) => {
    if (step < firstStep || step > furthestStep || step === currentStep) return;
    setCurrentStep(step);
    setErrors({});
  };

  const patch = (next: Partial<OnboardingDraft>) => setDraft((current) => ({ ...current, ...next }));

  const handleSave = async () => {
    if (submittedRef.current) return;

    for (const step of ['account', 'business', 'branches'] as StepKey[]) {
      if (step === 'account' && isSignedIn) continue;
      if (!validate(step)) {
        // Land on the step that is wrong rather than on the preview.
        setCurrentStep(STEPS.findIndex((s) => s.label.toLowerCase() === step) + 1);
        return;
      }
    }

    submittedRef.current = true;

    try {
      // 1. The account, unless this browser already has one. Signup deliberately does
      //    not return a token, so the login below is what turns the password the owner
      //    just typed into a session — which has to happen before the business write,
      //    because that write is authenticated.
      if (!isSignedIn && !draft.accountCreated) {
        await register({
          firstname: draft.account.firstname.trim(),
          lastname: draft.account.lastname.trim(),
          email: draft.account.email.trim(),
          phone: draft.account.phone.trim(),
          password: draft.account.password,
        }).unwrap();

        patch({ accountCreated: true });
      }

      if (!getToken()) {
        const result = await login({
          email: draft.account.email.trim(),
          password: draft.account.password,
        }).unwrap();

        setToken(result.data.token);
      }

      // 2. The business and its branches in one request. A second request per branch
      //    used to leave the tenant half-created whenever one of them was rejected.
      await createBusiness({
        name: draft.business.name.trim(),
        business_category_id: Number(draft.business.business_category_id),
        country_id: Number(draft.business.country_id),
        ...(draft.business.email.trim() ? { email: draft.business.email.trim() } : {}),
        ...(draft.business.phone.trim() ? { phone: draft.business.phone.trim() } : {}),
        ...(draft.business.address.trim() ? { address: draft.business.address.trim() } : {}),
        branches: draft.branches
          .filter((branch) => branch.name.trim() !== '')
          .map((branch) => ({
            name: branch.name.trim(),
            ...(branch.address.trim() ? { address: branch.address.trim() } : {}),
            ...(branch.phone.trim() ? { phone: branch.phone.trim() } : {}),
          })),
      }).unwrap();

      clearDraft();
      toast.success('Welcome to DuukaFlow! Your 30-day free trial has started.');

      // A full navigation, not a client-side one: the role tree is chosen from the
      // freshly-created tenant and every cached query in the store was fetched without
      // it. This is the same reason logout reloads the page.
      return (window.location.href = '/dashboard');
    } catch (error) {
      submittedRef.current = false;

      const message = firstFieldError(error);

      if (message) {
        toast.error(message);
        return;
      }

      // The account exists but the business does not. Say so plainly rather than
      // letting the owner press Save again into a duplicate-email 422.
      if (draft.accountCreated) {
        toast.error(
          (error as { data?: { message?: string } })?.data?.message ??
            'Your account was created but the business could not be saved. Press Save to try again.'
        );
        return;
      }

      toast.error((error as { data?: { message?: string } })?.data?.message || 'Something went wrong');
    }
  };

  return (
    <div className='grid min-h-[calc(100vh-8rem)] place-items-center bg-background px-4 py-10'>
      <Card className='w-full max-w-2xl border border-border/70 bg-card/95 shadow-xl'>
        <CardHeader className='space-y-1 px-6 pt-6'>
          <CardTitle className='text-2xl font-semibold'>Set up your business</CardTitle>
          <CardDescription className='text-sm text-muted-foreground'>
            {isSignedIn
              ? 'Finish setting up your business to start using DuukaFlow.'
              : 'Three short steps, then your workspace is ready.'}
          </CardDescription>
        </CardHeader>

        <CardContent className='px-6 pb-6 pt-2'>
          <div className='mb-8'>
            <Stepper
              currentStep={currentStep}
              steps={STEPS}
              furthestStep={furthestStep}
              onStepClick={goTo}
            />
          </div>

          {currentStep === 1 && (
            <AccountSetup
              data={draft.account}
              errors={errors}
              onChange={(account) => patch({ account })}
              onNext={goNext}
            />
          )}

          {currentStep === 2 && (
            <BusinessSetup
              data={draft.business}
              errors={errors}
              onChange={(business) => patch({ business })}
              onNext={goNext}
              onBack={goBack}
            />
          )}

          {currentStep === 3 && (
            <BranchSetup
              branches={draft.branches}
              errors={errors}
              onChange={(branches) => patch({ branches })}
              onNext={goNext}
              onBack={goBack}
            />
          )}

          {currentStep === 4 && (
            <Preview
              account={draft.account}
              business={draft.business}
              branches={draft.branches}
              names={names}
              showAccount={!isSignedIn}
              onEdit={goTo}
              onSave={handleSave}
              isSaving={isSaving}
            />
          )}
        </CardContent>
      </Card>
    </div>
  );
};