import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Link } from 'react-router-dom';
import { FileText, Receipt } from 'lucide-react';
import { format } from 'date-fns';
import { useCurrency } from '@/app/hooks/useCurrency';
import {
  amountSign,
  amountTone,
  counterpartyName,
  isOutflow,
  personName,
  sourceDocument,
  type FinanceTransactionRecord,
  typeVariant,
} from './financeTransaction';
import { TransactionDirectionControl, TransactionTypeBadge } from './financeTransactionComponents';

const DetailRow = ({ label, value }: { label: string; value: React.ReactNode }) => (
  <div className='flex flex-col gap-1 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-6'>
    <span className='text-sm text-muted-foreground'>{label}</span>
    <span className='text-sm font-medium sm:text-right'>{value}</span>
  </div>
);

const orDash = (value: string | null | undefined) => value || '—';

export const FinanceTransactionDetail = ({ transaction }: { transaction: FinanceTransactionRecord }) => {
  const { currency } = useCurrency();
  const outflow = isOutflow(transaction);
  const document = sourceDocument(transaction);

  return (
    <div className='space-y-6'>
      <Card className='rounded-3xl border border-border/70 bg-card/80 shadow-sm'>
        <CardHeader className='flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between'>
          <div className='space-y-2'>
            <CardTitle className='text-2xl'>Transaction Detail</CardTitle>
            <div className='flex flex-wrap items-center gap-2'>
              <span className='font-mono text-sm text-muted-foreground'>
                {transaction.transaction_code}
              </span>
              <TransactionTypeBadge type={transaction.type} />
              <Badge variant={typeVariant[transaction.type] ?? 'outline'}>{transaction.status}</Badge>
            </div>
          </div>
          <div className='text-left sm:text-right'>
            <p className={`text-3xl font-semibold ${amountTone(outflow)}`}>
              {amountSign(outflow)}
              {currency} {Number(transaction.amount).toLocaleString()}
            </p>
            <p className='text-xs text-muted-foreground'>
              {outflow === null ? 'Direction not recorded' : outflow ? 'Money out' : 'Money in'}
            </p>
          </div>
        </CardHeader>
        {/*
          Only an unsigned adjustment offers this, because the server refuses to clear a
          direction that is already recorded. The page states above that the direction is
          unknown, and this is where the user answers it.
        */}
        {transaction.type === 'adjustment' && !transaction.direction && (
          <CardContent className='border-t border-border/60'>
            <div className='flex flex-wrap items-center justify-between gap-4'>
              <p className='text-sm text-muted-foreground'>
                This adjustment predates the direction requirement, so it is excluded from the cash
                balance until it is signed.
              </p>
              <TransactionDirectionControl record={transaction} />
            </div>
          </CardContent>
        )}
      </Card>

      <div className='grid gap-6 lg:grid-cols-2'>
        <Card>
          <CardHeader>
            <CardTitle>Transaction</CardTitle>
          </CardHeader>
          <CardContent className='divide-y divide-border/60'>
            <DetailRow label='Description' value={orDash(transaction.description)} />
            <DetailRow label='Category' value={orDash(transaction.category?.replace(/_/g, ' '))} />
            <DetailRow label='Payment method' value={orDash(transaction.payment_method)} />
            <DetailRow label='Reference' value={orDash(transaction.reference)} />
            <DetailRow
              label='Date'
              value={
                transaction.transaction_date
                  ? format(new Date(transaction.transaction_date), 'dd MMM yyyy')
                  : '—'
              }
            />
            <DetailRow label='Currency' value={transaction.currency || currency} />
            <DetailRow
              label='Direction'
              value={
                transaction.direction === 'credit'
                  ? 'Money in'
                  : transaction.direction === 'debit'
                    ? 'Money out'
                    : '—'
              }
            />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Recorded by</CardTitle>
          </CardHeader>
          <CardContent className='divide-y divide-border/60'>
            <DetailRow label='Recorded by' value={orDash(personName(transaction.created_by))} />
            <DetailRow label='Branch' value={orDash(transaction.branch?.name)} />
            <DetailRow label='Customer' value={orDash(counterpartyName(transaction.customer))} />
            <DetailRow label='Supplier' value={orDash(counterpartyName(transaction.supplier))} />
            <Separator className='my-2' />
            {/*
              Only the relation this row actually points at is listed. A sale cash-flow has a
              sale and nothing else, so a fixed grid would render mostly empty rows.
            */}
            <DetailRow
              label='Source document'
              value={
                document ? (
                  <Button variant='outline' size='sm' asChild>
                    <Link to={document.to}>
                      <FileText className='h-4 w-4' />
                      {document.label} #{document.id}
                    </Link>
                  </Button>
                ) : (
                  <span className='inline-flex items-center gap-2 text-muted-foreground'>
                    <Receipt className='h-4 w-4' />
                    None
                  </span>
                )
              }
            />
          </CardContent>
        </Card>
      </div>

      {transaction.notes && (
        <Card>
          <CardHeader>
            <CardTitle>Notes</CardTitle>
          </CardHeader>
          <CardContent>
            <p className='whitespace-pre-wrap text-sm text-muted-foreground'>{transaction.notes}</p>
          </CardContent>
        </Card>
      )}
    </div>
  );
};