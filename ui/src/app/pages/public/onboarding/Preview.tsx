import { Building2, MapPin, Pencil, User as UserIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Badge } from '@/components/ui/badge';
import type { AccountData, BusinessData, BranchData } from './types';

export interface ResolvedNames {
  country: string;
  category: string;
}

interface PreviewProps {
  account: AccountData;
  business: BusinessData;
  branches: BranchData[];
  /**
   * The country and category as text.
   *
   * Passed separately rather than substituted into `business` the way the previous
   * version did it. Overwriting country_id with the country's name left the draft
   * holding a string where the API wants an id, so editing anything on this step and
   * saving afterwards sent "Uganda" as a foreign key.
   */
  names: ResolvedNames;
  showAccount: boolean;
  onEdit: (step: number) => void;
  onSave: () => void;
  isSaving: boolean;
}

const SummaryRow: React.FC<{ label: string; value: string }> = ({ label, value }) => (
  <div className='grid grid-cols-[minmax(0,9rem)_1fr] gap-3 py-2'>
    <dt className='text-muted-foreground'>{label}</dt>
    <dd className='min-w-0 break-words font-medium'>{value || '—'}</dd>
  </div>
);

/**
 * Step 4 — nothing is written until this is confirmed.
 *
 * Every section can be sent back for editing, which is the point of the step: the
 * owner has not created an account yet, so a typo noticed here is free to fix and one
 * noticed afterwards is not.
 */
export const Preview: React.FC<PreviewProps> = ({
  account,
  business,
  branches,
  names,
  showAccount,
  onEdit,
  onSave,
  isSaving,
}) => {
  return (
    <div className='space-y-6'>
      {showAccount && (
        <Card>
          <CardHeader className='flex-row items-center justify-between space-y-0 pb-4'>
            <CardTitle className='flex items-center gap-2 text-sm font-medium'>
              <UserIcon className='h-4 w-4' aria-hidden />
              Your account
            </CardTitle>
            <Button type='button' variant='ghost' size='sm' onClick={() => onEdit(1)}>
              <Pencil className='mr-1 h-3 w-3' aria-hidden />
              Edit
            </Button>
          </CardHeader>
          <CardContent>
            <dl className='divide-y'>
              <SummaryRow label='Name' value={`${account.firstname} ${account.lastname}`.trim()} />
              <SummaryRow label='Email' value={account.email} />
              <SummaryRow label='Phone' value={account.phone} />
            </dl>
          </CardContent>
        </Card>
      )}

      <Card>
        <CardHeader className='flex-row items-center justify-between space-y-0 pb-4'>
          <CardTitle className='flex items-center gap-2 text-sm font-medium'>
            <Building2 className='h-4 w-4' aria-hidden />
            Business
          </CardTitle>
          <Button type='button' variant='ghost' size='sm' onClick={() => onEdit(2)}>
            <Pencil className='mr-1 h-3 w-3' aria-hidden />
            Edit
          </Button>
        </CardHeader>
        <CardContent>
          <dl className='divide-y'>
            <SummaryRow label='Name' value={business.name} />
            <SummaryRow label='Category' value={names.category} />
            <SummaryRow label='Country' value={names.country} />
            <SummaryRow label='Email' value={business.email} />
            <SummaryRow label='Phone' value={business.phone} />
            <SummaryRow label='Address' value={business.address} />
          </dl>
        </CardContent>
      </Card>

      <Card>
        <CardHeader className='flex-row items-center justify-between space-y-0 pb-4'>
          <CardTitle className='flex items-center gap-2 text-sm font-medium'>
            <MapPin className='h-4 w-4' aria-hidden />
            Branches
            <Badge variant='secondary'>{branches.length}</Badge>
          </CardTitle>
          <Button type='button' variant='ghost' size='sm' onClick={() => onEdit(3)}>
            <Pencil className='mr-1 h-3 w-3' aria-hidden />
            Edit
          </Button>
        </CardHeader>
        <CardContent className='space-y-3'>
          {branches.map((branch, index) => (
            <div key={index} className='rounded-lg border border-border/60 p-3'>
              <p className='text-sm font-medium'>{branch.name}</p>
              <Separator className='my-2' />
              <p className='text-xs text-muted-foreground'>{branch.address}</p>
              {branch.phone && <p className='text-xs text-muted-foreground'>{branch.phone}</p>}
            </div>
          ))}
        </CardContent>
      </Card>

      <div className='space-y-3'>
        <Button type='button' onClick={onSave} disabled={isSaving} className='w-full'>
          {isSaving ? 'Creating your business…' : 'Save and go to dashboard'}
        </Button>
        <Button
          type='button'
          variant='ghost'
          onClick={() => onEdit(3)}
          disabled={isSaving}
          className='w-full'
        >
          Back to branches
        </Button>
      </div>
    </div>
  );
};