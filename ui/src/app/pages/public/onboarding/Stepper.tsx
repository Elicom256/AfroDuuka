import { Check } from 'lucide-react';
import { Progress } from '@/components/ui/progress';
import { cn } from '@/lib/utils';

export interface StepperStep {
  label: string;
  description?: string;
}

interface StepperProps {
  currentStep: number;
  steps: StepperStep[];
  /**
   * Steps the owner may jump back to. Forward jumps are refused by the page, which
   * validates the steps in between — a preview reached by skipping a step is a preview
   * of nothing.
   */
  onStepClick?: (step: number) => void;
  furthestStep?: number;
}

/**
 * Progress through the onboarding steps.
 *
 * A shadcn Progress bar alone answers "how far along am I" but not "where am I", and
 * on a three-form signup the second question is the one people actually have. So the
 * bar carries the percentage for assistive tech and the numbered chips carry the
 * position, and a chip the owner has already completed is a button back.
 */
export const Stepper: React.FC<StepperProps> = ({ currentStep, steps, onStepClick, furthestStep = 1 }) => {
  const percent = Math.round((currentStep / steps.length) * 100);
  const furthest = Math.max(furthestStep, currentStep);

  return (
    <div className='space-y-4'>
      <div className='flex items-center justify-between text-xs font-medium text-muted-foreground'>
        <span>
          Step {currentStep} of {steps.length}
        </span>
        <span>{percent}% complete</span>
      </div>

      <Progress
        value={percent}
        className='h-1.5'
        aria-label={`Onboarding progress: step ${currentStep} of ${steps.length}`}
      />

      <ol className='flex items-start gap-1'>
        {steps.map((step, index) => {
          const stepNumber = index + 1;
          const isCompleted = stepNumber < currentStep;
          const isCurrent = stepNumber === currentStep;
          const isReachable = stepNumber <= furthest && stepNumber !== currentStep;

          return (
            <li key={step.label} className='flex flex-1 items-start gap-1'>
              <button
                type='button'
                disabled={!isReachable}
                onClick={() => onStepClick?.(stepNumber)}
                aria-current={isCurrent ? 'step' : undefined}
                className={cn(
                  'flex min-w-0 flex-1 flex-col items-center gap-1.5 rounded-md px-1 py-1 text-center transition-colors',
                  isReachable && 'cursor-pointer hover:bg-muted/60',
                  !isReachable && !isCurrent && 'cursor-default'
                )}
              >
                <span
                  className={cn(
                    'flex h-7 w-7 shrink-0 items-center justify-center rounded-full border text-xs font-semibold transition-colors',
                    isCompleted && 'border-primary bg-primary text-primary-foreground',
                    isCurrent && 'border-primary bg-background text-primary',
                    !isCompleted && !isCurrent && 'border-muted bg-background text-muted-foreground'
                  )}
                >
                  {isCompleted ? <Check className='h-3.5 w-3.5' aria-hidden /> : stepNumber}
                </span>
                <span
                  className={cn(
                    'w-full truncate text-xs font-medium',
                    isCurrent ? 'text-foreground' : 'text-muted-foreground'
                  )}
                >
                  {step.label}
                </span>
              </button>
            </li>
          );
        })}
      </ol>
    </div>
  );
};