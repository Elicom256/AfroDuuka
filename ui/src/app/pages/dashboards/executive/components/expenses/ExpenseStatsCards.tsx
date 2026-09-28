import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useCurrency } from '@/app/hooks/useCurrency';

type ExpenseStatsCardsProps = {
  totalAmount: number;
  pendingCount: number;
  approvedCount: number;
};

export const ExpenseStatsCards = ({ totalAmount, pendingCount, approvedCount }: ExpenseStatsCardsProps) => {
  const { currency } = useCurrency();

  return (
    <div className='grid gap-4 md:grid-cols-3'>
      <Card>
        <CardHeader className='pb-2'>
          <CardTitle className='text-sm font-medium text-muted-foreground'>Total Expenses</CardTitle>
        </CardHeader>
        <CardContent>
          <p className='text-3xl font-semibold'>{currency} {Number(totalAmount).toLocaleString()}</p>
        </CardContent>
      </Card>
      <Card>
        <CardHeader className='pb-2'>
          <CardTitle className='text-sm font-medium text-muted-foreground'>Pending</CardTitle>
        </CardHeader>
        <CardContent>
          <p className='text-3xl font-semibold text-yellow-600'>{pendingCount}</p>
        </CardContent>
      </Card>
      <Card>
        <CardHeader className='pb-2'>
          <CardTitle className='text-sm font-medium text-muted-foreground'>Approved</CardTitle>
        </CardHeader>
        <CardContent>
          <p className='text-3xl font-semibold text-green-600'>{approvedCount}</p>
        </CardContent>
      </Card>
    </div>
  );
};
