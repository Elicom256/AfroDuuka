import { Building, MapPin, Phone, Plus, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { initialBranchData, type BranchData, type StepErrors } from './types';

interface BranchSetupProps {
  branches: BranchData[];
  errors: StepErrors;
  onChange: (branches: BranchData[]) => void;
  onNext: () => void;
  onBack: () => void;
}

const MAX_BRANCHES = 20;

/**
 * Step 3 — the business's branches.
 *
 * A branch is where sales, stock and stock counts are recorded, so a business with none
 * has nowhere to put its first sale. One is therefore required and the first is
 * pre-filled with "Main Branch" to match the column's default. The list is sent with
 * the business on save rather than created one request at a time, so a duplicate name
 * is caught here instead of half-creating the tenant.
 */
export const BranchSetup: React.FC<BranchSetupProps> = ({ branches, errors, onChange, onNext, onBack }) => {
  const update = (index: number, field: keyof BranchData, value: string) => {
    onChange(branches.map((branch, i) => (i === index ? { ...branch, [field]: value } : branch)));
  };

  const add = () => {
    if (branches.length >= MAX_BRANCHES) return;
    onChange([...branches, { ...initialBranchData, name: '' }]);
  };

  const remove = (index: number) => {
    if (branches.length <= 1) return;
    onChange(branches.filter((_, i) => i !== index));
  };

  const hasErrors = (index: number) =>
    Object.keys(errors).some((key) => key.startsWith(`branches.${index}.`));

  return (
    <form
      noValidate
      onSubmit={(e) => {
        e.preventDefault();
        onNext();
      }}
      className='space-y-6'
    >
      {branches.map((branch, index) => (
        <Card key={index} className={hasErrors(index) ? 'border-destructive/60' : undefined}>
          <CardHeader className='flex-row items-center justify-between gap-2 space-y-0 pb-4'>
            <CardTitle className='text-sm font-medium'>
              {index === 0 ? 'Main branch' : `Branch ${index + 1}`}
            </CardTitle>
            {branches.length > 1 && (
              <Button
                type='button'
                variant='ghost'
                size='sm'
                onClick={() => remove(index)}
                aria-label={`Remove ${branch.name || `branch ${index + 1}`}`}
                className='h-8 w-8 p-0 text-muted-foreground hover:text-destructive'
              >
                <Trash2 className='h-4 w-4' aria-hidden />
              </Button>
            )}
          </CardHeader>

          <CardContent className='space-y-4'>
            <Field data-invalid={Boolean(errors[`branches.${index}.name`])}>
              <FieldLabel htmlFor={`branch_name_${index}`}>
                <Building className='h-4 w-4 text-muted-foreground' aria-hidden />
                Branch name
              </FieldLabel>
              <Input
                id={`branch_name_${index}`}
                value={branch.name}
                onChange={(e) => update(index, 'name', e.target.value)}
                placeholder='Main Branch'
                aria-invalid={Boolean(errors[`branches.${index}.name`])}
              />
              {errors[`branches.${index}.name`] && (
                <FieldError>{errors[`branches.${index}.name`]}</FieldError>
              )}
            </Field>

            <Field data-invalid={Boolean(errors[`branches.${index}.address`])}>
              <FieldLabel htmlFor={`branch_address_${index}`}>
                <MapPin className='h-4 w-4 text-muted-foreground' aria-hidden />
                Address
              </FieldLabel>
              <Input
                id={`branch_address_${index}`}
                value={branch.address}
                onChange={(e) => update(index, 'address', e.target.value)}
                placeholder='Plot 123, Kampala Road'
                aria-invalid={Boolean(errors[`branches.${index}.address`])}
              />
              {errors[`branches.${index}.address`] && (
                <FieldError>{errors[`branches.${index}.address`]}</FieldError>
              )}
            </Field>

            <Field data-invalid={Boolean(errors[`branches.${index}.phone`])}>
              <FieldLabel htmlFor={`branch_phone_${index}`}>
                <Phone className='h-4 w-4 text-muted-foreground' aria-hidden />
                Phone
              </FieldLabel>
              <Input
                id={`branch_phone_${index}`}
                type='tel'
                value={branch.phone}
                onChange={(e) => update(index, 'phone', e.target.value)}
                placeholder='+256 700 000 000'
                aria-invalid={Boolean(errors[`branches.${index}.phone`])}
              />
              {errors[`branches.${index}.phone`] && (
                <FieldError>{errors[`branches.${index}.phone`]}</FieldError>
              )}
            </Field>
          </CardContent>
        </Card>
      ))}

      <Button
        type='button'
        variant='outline'
        onClick={add}
        disabled={branches.length >= MAX_BRANCHES}
        className='w-full border-dashed'
      >
        <Plus className='mr-2 h-4 w-4' aria-hidden />
        Add another branch
      </Button>

      <div className='flex flex-col-reverse gap-3 sm:flex-row'>
        <Button type='button' variant='outline' onClick={onBack} className='sm:flex-1'>
          Back
        </Button>
        <Button type='submit' className='sm:flex-1'>
          Review setup
        </Button>
      </div>
    </form>
  );
};