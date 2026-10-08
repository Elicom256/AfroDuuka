import { useParams, Link } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { ArrowLeft } from 'lucide-react';
import { useGetFinanceTransactionQuery } from '@/app/store/features/finance/financeQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { QueryErrorState } from '@/app/components/QueryErrorState';
import { FinanceTransactionDetail } from '../components/finance/FinanceTransactionDetail';

export const ExecutiveFinanceTransactionPage = () => {
  const { id } = useParams<{ id: string }>();
  const { data, isLoading, isFetching, isError, refetch } = useGetFinanceTransactionQuery(id!);

  if (isLoading) return <PageLoadingState />;

  if (isError && !data) {
    return (
      <div className='space-y-6'>
        <Button variant='ghost' size='icon' asChild>
          <Link to='/dashboard/finance/transactions'>
            <ArrowLeft className='h-5 w-5' />
          </Link>
        </Button>
        <QueryErrorState
          title='Unable to load this transaction'
          description='The transaction could not be retrieved. It may have been removed, or it may belong to another branch.'
          onRetry={refetch}
          retrying={isFetching}
        />
      </div>
    );
  }

  const transaction = data?.data;

  if (!transaction) return <p>Transaction not found.</p>;

  return (
    <div className='space-y-6'>
      <div className='flex items-center gap-4'>
        <Button variant='ghost' size='icon' asChild>
          <Link to='/dashboard/finance/transactions'>
            <ArrowLeft className='h-5 w-5' />
          </Link>
        </Button>
        <h1 className='text-2xl font-semibold'>Transaction Detail</h1>
      </div>
      <FinanceTransactionDetail transaction={transaction} />
    </div>
  );
};