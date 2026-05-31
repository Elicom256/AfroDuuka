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
    <div className='space-y-4'>
      <h2 className='text-lg font-medium'>Business Reports</h2>

      <div className='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4'>
        <BranchPerformanceReport />
        <StockSummaryReport />
        <LowStockReport />
        <OutOfStockReport />
        <DeadStockReport />
        <InventoryValuationReport />
        <SalesByProductReport />
        <StockMovementReport />
      </div>
    </div>
  );
};

export default AdminReportsPage;
