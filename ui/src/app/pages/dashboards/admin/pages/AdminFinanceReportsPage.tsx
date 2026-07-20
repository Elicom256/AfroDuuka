import { useState } from 'react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { RevenueChart } from '../components/finance/RevenueChart';
import { ExpenseBreakdown } from '../components/finance/ExpenseBreakdown';
import { IncomeSummaryTable } from '../components/finance/IncomeSummaryTable';
import {
  useGetRevenueReportQuery,
  useGetExpenseReportQuery,
  useGetIncomeSummaryQuery,
} from '@/app/store/features/finance/financeQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';

export const AdminFinanceReportsPage = () => {
  const today = new Date();
  const thirtyDaysAgo = new Date(today);
  thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);

  const [fromDate, setFromDate] = useState(thirtyDaysAgo.toISOString().slice(0, 10));
  const [toDate, setToDate] = useState(today.toISOString().slice(0, 10));
  const [selectedYear, setSelectedYear] = useState(String(today.getFullYear()));

  const {
    data: revenueData,
    isLoading: revenueLoading,
    isError: revenueError,
  } = useGetRevenueReportQuery({ start_date: fromDate, end_date: toDate });

  const {
    data: expenseData,
    isLoading: expenseLoading,
    isError: expenseError,
  } = useGetExpenseReportQuery({ start_date: fromDate, end_date: toDate });

  const {
    data: incomeData,
    isLoading: incomeLoading,
    isError: incomeError,
  } = useGetIncomeSummaryQuery({ year: selectedYear });

  if (revenueLoading || expenseLoading || incomeLoading) return <PageLoadingState />;

  const revenueChartData = revenueData?.data ?? [];
  const expenseBreakdownData = expenseData?.data ?? [];
  const incomeSummaryData = incomeData?.data ?? [];

  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-3xl font-bold tracking-tight'>Financial Reports</h1>
        <p className='text-muted-foreground'>Analyze revenue, expenses, and profitability</p>
      </div>

      <div className='flex flex-col sm:flex-row gap-4'>
        <div className='flex items-center gap-2'>
          <label className='text-sm text-muted-foreground whitespace-nowrap'>From:</label>
          <input
            type='date'
            value={fromDate}
            onChange={(e) => setFromDate(e.target.value)}
            className='rounded-md border border-input bg-background px-3 py-1.5 text-sm'
          />
        </div>
        <div className='flex items-center gap-2'>
          <label className='text-sm text-muted-foreground whitespace-nowrap'>To:</label>
          <input
            type='date'
            value={toDate}
            onChange={(e) => setToDate(e.target.value)}
            className='rounded-md border border-input bg-background px-3 py-1.5 text-sm'
          />
        </div>
      </div>

      <Tabs defaultValue='revenue'>
        <TabsList>
          <TabsTrigger value='revenue'>Revenue Report</TabsTrigger>
          <TabsTrigger value='expense'>Expense Report</TabsTrigger>
          <TabsTrigger value='income'>Income Summary</TabsTrigger>
        </TabsList>
        <TabsContent value='revenue' className='pt-4'>
          {revenueError ? (
            <p className='text-red-500'>Failed to load revenue data.</p>
          ) : (
            <RevenueChart data={revenueChartData} />
          )}
        </TabsContent>
        <TabsContent value='expense' className='pt-4'>
          {expenseError ? (
            <p className='text-red-500'>Failed to load expense data.</p>
          ) : (
            <ExpenseBreakdown data={expenseBreakdownData} />
          )}
        </TabsContent>
        <TabsContent value='income' className='pt-4'>
          {incomeError ? (
            <p className='text-red-500'>Failed to load income summary data.</p>
          ) : (
            <IncomeSummaryTable data={incomeSummaryData} year={selectedYear} onYearChange={setSelectedYear} />
          )}
        </TabsContent>
      </Tabs>
    </div>
  );
};
