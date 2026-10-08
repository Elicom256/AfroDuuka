import { Button } from '@/components/ui/button';
import { ArrowDownLeft, ArrowUpRight } from 'lucide-react';
import { toast } from 'sonner';
import {
  type CashFlowDirection,
  useSetCashFlowDirectionMutation,
} from '@/app/store/features/business/executive/cashFlowQuery';
import { typeColors, type FinanceTransactionRecord } from './financeTransaction';

export const TransactionTypeBadge = ({ type }: { type: string }) => (
  <span
    className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${typeColors[type] ?? 'bg-gray-100 text-muted-foreground'}`}
  >
    {type.replace('_', ' ')}
  </span>
);

const DIRECTION_OPTIONS: { value: CashFlowDirection; label: string; Icon: typeof ArrowDownLeft }[] = [
  { value: 'credit', label: 'Money In', Icon: ArrowDownLeft },
  { value: 'debit', label: 'Money Out', Icon: ArrowUpRight },
];

/**
 * Record a direction on an adjustment that has none.
 *
 * Only rendered for an unsigned adjustment. The server refuses to clear a direction that is
 * already recorded, so this is a repair control rather than an edit one.
 */
export const TransactionDirectionControl = ({
  record,
}: {
  record: Pick<FinanceTransactionRecord, 'id' | 'transaction_code' | 'direction'>;
}) => {
  const [setDirection, { isLoading }] = useSetCashFlowDirectionMutation();

  return (
    <div className='flex items-center gap-1'>
      {DIRECTION_OPTIONS.map(({ value, label, Icon }) => (
        <Button
          key={value}
          variant='ghost'
          size='icon'
          disabled={isLoading}
          title={`Mark as ${label.toLowerCase()}`}
          aria-label={`Mark ${record.transaction_code} as ${label.toLowerCase()}`}
          onClick={async () => {
            try {
              const res = await setDirection({ id: record.id, direction: value }).unwrap();
              toast.success(res?.message || `Marked as ${label.toLowerCase()}`);
            } catch {
              toast.error('Could not record the direction');
            }
          }}
        >
          <Icon className='h-4 w-4' />
        </Button>
      ))}
    </div>
  );
};