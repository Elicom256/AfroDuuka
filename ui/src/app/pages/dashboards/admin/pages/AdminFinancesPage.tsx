import { CashFlowAnalytics } from '../components/analytics/CashFlowAnalytics';

export const AdminFinancesPage = () => {
  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-3xl font-bold tracking-tight'>Admin finances</h1>
        <p className='text-muted-foreground'>
          Review business cash flow, revenue trends, and expense performance in one place.
        </p>
      </div>

      <CashFlowAnalytics />
    </div>
  );
};
