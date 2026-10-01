import { Check } from 'lucide-react';
import { Progress } from '@/components/ui/progress';
import { cn } from '@/lib/utils';

interface StepperProps {
  currentStep: number;
  steps: { label: string }[];
}

export const Stepper: React.FC<StepperProps> = ({ currentStep, steps }) => {
  const progress = (currentStep / steps.length) * 100;

  return (
    <div className='space-y-3'>
      <div className='flex items-center justify-between'>
        {steps.map((step, index) => {
          const stepNumber = index + 1;
          const isCompleted = stepNumber < currentStep;
          const isCurrent = stepNumber === currentStep;

          return (
            <div key={stepNumber} className='flex items-center'>
              <div className='flex flex-col items-center gap-1'>
                <div
                  className={cn(
                    'flex h-8 w-8 items-center justify-center rounded-full border-2 text-sm font-medium transition-colors',
                    isCompleted && 'border-primary bg-primary text-primary-foreground',
                    isCurrent && 'border-primary bg-background text-primary',
                    !isCompleted && !isCurrent && 'border-muted bg-background text-muted-foreground'
                  )}
                >
                  {isCompleted ? <Check className='h-4 w-4' /> : stepNumber}
                </div>
                <span
                  className={cn(
                    'text-xs font-medium whitespace-nowrap',
                    isCurrent ? 'text-foreground' : 'text-muted-foreground'
                  )}
                >
                  {step.label}
                </span>
              </div>
              {stepNumber < steps.length && (
                <div
                  className={cn(
                    'mx-1 h-0.5 flex-1 sm:mx-2',
                    stepNumber < currentStep ? 'bg-primary' : 'bg-muted'
                  )}
                />
              )}
            </div>
          );
        })}
      </div>
      <Progress value={progress} className='h-1' />
    </div>
  );
};
