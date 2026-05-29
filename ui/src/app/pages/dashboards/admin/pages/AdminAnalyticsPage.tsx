import { SalesAnalytics } from '../components/analytics/SalesAnalytics';
import { PurchasesAnalytics } from '../components/analytics/PurchasesAnalytics';
import { InventoryAnalytics } from '../components/analytics/InventoryAnalytics';
import { CustomersAnalytics } from '../components/analytics/CustomersAnalytics';
import { SuppliersAnalytics } from '../components/analytics/SuppliersAnalytics';
import { CashFlowAnalytics } from '../components/analytics/CashFlowAnalytics';

export const AdminAnalyticsPage = () => {
  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-3xl font-bold tracking-tight'>Analytics</h1>
        <p className='text-muted-foreground'>View business trends and performance metrics</p>
      </div>

      <div className='grid gap-6 lg:grid-cols-2'>
        <SalesAnalytics />
        <PurchasesAnalytics />
        <InventoryAnalytics />
        <CustomersAnalytics />
        <SuppliersAnalytics />
        {/* 1. CashFlowAnalytics (Very Important) */}
        <CashFlowAnalytics />
        {/* 2. TopProductsWidget */}
        {/* 3. PerformanceMetrics */}
      </div>
    </div>
  );
};
