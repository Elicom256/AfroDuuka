import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { format } from 'date-fns';
import { useCurrency } from '@/app/hooks/useCurrency';
import { personName } from '@/app/utils/userName';

const statusColors: Record<string, string> = {
  draft: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
  in_progress: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
  completed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
  approved: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400',
  cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
};

/**
 * The performer and the approver are User rows, not ids.
 *
 * Eager loading performedBy() serialises into `performed_by`, which is also the
 * foreign-key column, so the loaded user replaces the raw id. And a User has no `name`
 * column -- a name is `firstname` and `lastname` -- so reading `.name` off it always
 * gave undefined and the page rendered a dash for whoever actually did the audit.
 */
type Props = {
  audit: {
    performed_by?: { firstname?: string | null; lastname?: string | null } | null;
    approved_by?: { firstname?: string | null; lastname?: string | null } | null;
    [key: string]: any;
  };
};

export const FinancialAuditDetail = ({ audit }: Props) => {
  const { currencySymbol } = useCurrency();
  return (
    <div className='space-y-6'>
      <Card className='rounded-3xl border border-border/70 bg-card/80 shadow-sm'>
        <CardHeader>
          <div className='flex items-center justify-between'>
            <div>
              <CardTitle className='text-2xl'>{audit.audit_number}</CardTitle>
              <p className='text-sm text-muted-foreground mt-1'>
                {audit.branch?.name} &middot; {format(new Date(audit.audit_date), 'PP')}
              </p>
            </div>
            <Badge className={statusColors[audit.status]} variant='outline'>
              {audit.status?.replace('_', ' ')}
            </Badge>
          </div>
        </CardHeader>
        <CardContent>
          <div className='grid grid-cols-2 md:grid-cols-4 gap-4'>
            <div>
              <p className='text-sm text-muted-foreground'>Expected Balance</p>
              <p className='font-medium text-lg'>{currencySymbol}{Number(audit.expected_balance).toLocaleString()}</p>
            </div>
            <div>
              <p className='text-sm text-muted-foreground'>Actual Balance</p>
              <p className='font-medium text-lg'>{currencySymbol}{Number(audit.actual_balance).toLocaleString()}</p>
            </div>
            <div>
              <p className='text-sm text-muted-foreground'>Difference</p>
              <p className={`font-medium text-lg ${audit.difference > 0 ? 'text-green-600' : audit.difference < 0 ? 'text-red-600' : ''}`}>
                {audit.difference > 0 ? '+' : ''}{currencySymbol}{Number(audit.difference).toLocaleString()}
              </p>
            </div>
            <div>
              <p className='text-sm text-muted-foreground'>Status</p>
              <Badge className={statusColors[audit.status]} variant='outline'>
                {audit.status?.replace('_', ' ')}
              </Badge>
            </div>
          </div>
          <div className='grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 pt-4 border-t'>
            <div>
              <p className='text-sm text-muted-foreground'>Performed By</p>
              <p className='font-medium'>{personName(audit.performed_by) || '-'}</p>
            </div>
            <div>
              <p className='text-sm text-muted-foreground'>Approved By</p>
              <p className='font-medium'>{personName(audit.approved_by) || '-'}</p>
            </div>
            <div>
              <p className='text-sm text-muted-foreground'>Approved At</p>
              <p className='font-medium'>{audit.approved_at ? format(new Date(audit.approved_at), 'PPp') : '-'}</p>
            </div>
          </div>
          {audit.notes && (
            <div className='mt-4 pt-4 border-t'>
              <p className='text-sm text-muted-foreground'>Notes</p>
              <p className='text-sm'>{audit.notes}</p>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
};
