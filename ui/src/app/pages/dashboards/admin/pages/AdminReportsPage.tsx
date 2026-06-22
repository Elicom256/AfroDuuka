import { BarChart3, Package, TrendingUp } from 'lucide-react';
import {
  BranchPerformanceReport,
  StockSummaryReport,
  LowStockReport,
  OutOfStockReport,
  DeadStockReport,
  InventoryValuationReport,
  SalesByProductReport,
  StockMovementReport,
} from '../components/reports';

export const AdminReportsPage = () => {
  return (
    <div className='space-y-8'>
      <div>
        <h1 className='text-3xl font-bold tracking-tight flex items-center gap-3'>
          <BarChart3 className='h-8 w-8' />
          Business Reports
        </h1>
        <p className='text-muted-foreground mt-1'>
          Performance insights and inventory analytics
        </p>
      </div>

      {/* Branch Performance */}
      <section>
        <div className='flex items-center gap-2 mb-4'>
          <TrendingUp className='h-5 w-5 text-primary' />
          <h2 className='text-lg font-semibold'>Branch Performance</h2>
        </div>
        <BranchPerformanceReport />
      </section>

      {/* Inventory Overview */}
      <section>
        <div className='flex items-center gap-2 mb-4'>
          <Package className='h-5 w-5 text-primary' />
          <h2 className='text-lg font-semibold'>Inventory Overview</h2>
        </div>
        <div className='grid grid-cols-1 md:grid-cols-2 gap-6'>
          <StockSummaryReport />
          <InventoryValuationReport />
        </div>
      </section>

      {/* Stock Health */}
      <section>
        <div className='flex items-center gap-2 mb-4'>
          <Package className='h-5 w-5 text-amber-500' />
          <h2 className='text-lg font-semibold'>Stock Health</h2>
        </div>
        <div className='grid grid-cols-1 md:grid-cols-3 gap-6'>
          <LowStockReport />
          <OutOfStockReport />
          <DeadStockReport />
        </div>
      </section>

      {/* Movement & Sales */}
      <section>
        <div className='flex items-center gap-2 mb-4'>
          <TrendingUp className='h-5 w-5 text-primary' />
          <h2 className='text-lg font-semibold'>Movement & Sales</h2>
        </div>
        <div className='grid grid-cols-1 md:grid-cols-2 gap-6'>
          <StockMovementReport />
          <SalesByProductReport />
        </div>
      </section>
    </div>
  );
};

export default AdminReportsPage;
