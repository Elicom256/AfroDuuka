import { useState, useMemo } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Link } from 'react-router-dom';
import { Tags } from 'lucide-react';
import { Chart as ChartJS, CategoryScale, LinearScale, BarElement, ArcElement, Title, Tooltip, Legend } from 'chart.js';
import {
  useGetExpensesQuery,
  useDeleteExpenseMutation,
  useGetMonthlyExpenseSummaryQuery,
  useGetExpenseTotalsByCategoryQuery,
} from '@/app/store/features/business/admin/expenseQuery';
import { useGetExpenseCategoriesQuery } from '@/app/store/features/business/admin/expenseCategoriesQuery';
import { useBranchesQuery } from '@/app/store/features/business/branches/branchesQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { AddExpense } from '../components/expenses/AddExpense';
import { EditExpense } from '../components/expenses/EditExpense';
import { ExpenseStatsCards } from '../components/expenses/ExpenseStatsCards';
import { ExpenseFilters } from '../components/expenses/ExpenseFilters';
import { ExpenseTable } from '../components/expenses/ExpenseTable';
import { ExpenseCharts } from '../components/expenses/ExpenseCharts';
import { ApproveExpenseDialog } from '../components/expenses/ApproveExpenseDialog';

ChartJS.register(CategoryScale, LinearScale, BarElement, ArcElement, Title, Tooltip, Legend);

export const AdminExpensesPage = () => {
  const [page, setPage] = useState(1);
  const [filters, setFilters] = useState<Record<string, string>>({});
  const [search, setSearch] = useState('');
  const [editOpen, setEditOpen] = useState(false);
  const [approveOpen, setApproveOpen] = useState(false);
  const [selected, setSelected] = useState<any>(null);

  const queryParams = useMemo(() => {
    const params: Record<string, any> = { page };
    if (filters.expense_category_id) params.expense_category_id = filters.expense_category_id;
    if (filters.business_branch_id) params.business_branch_id = filters.business_branch_id;
    if (filters.status) params.status = filters.status;
    if (filters.date_from) params.date_from = filters.date_from;
    if (filters.date_to) params.date_to = filters.date_to;
    if (search) params.search = search;
    return params;
  }, [page, filters, search]);

  const { data, isLoading, error } = useGetExpensesQuery(queryParams);
  const { data: categoriesData } = useGetExpenseCategoriesQuery();
  const { data: branchesData } = useBranchesQuery();
  const { data: monthlyData } = useGetMonthlyExpenseSummaryQuery({});
  const { data: categoryTotalsData } = useGetExpenseTotalsByCategoryQuery({});
  const [deleteExpense] = useDeleteExpenseMutation();

  const expenses = data?.expenses?.data ?? [];
  const currentPage = data?.expenses?.current_page ?? 1;
  const totalPages = data?.expenses?.last_page ?? 1;
  const totalAmount = data?.total_amount ?? 0;
  const categories = categoriesData?.expense_categories?.data ?? [];
  const branches = branchesData?.branches ?? [];
  const monthlySummary = monthlyData?.monthly_summary ?? [];
  const totalsByCategory = categoryTotalsData?.totals_by_category ?? [];
  console.log('expenses==>', data ?? error);
  const pendingCount = expenses.filter((e: any) => e.status === 'pending').length;
  const approvedCount = expenses.filter((e: any) => e.status === 'approved').length;

  const handleFilterChange = (key: string, value: string) => {
    setFilters((prev) => ({ ...prev, [key]: value }));
    setPage(1);
  };

  const clearFilters = () => {
    setFilters({});
    setSearch('');
    setPage(1);
  };

  const handleEdit = (item: any) => {
    setSelected(item);
    setEditOpen(true);
  };

  const handleApprove = (item: any) => {
    setSelected(item);
    setApproveOpen(true);
  };

  const handleDelete = async (id: number) => {
    if (!confirm('Are you sure?')) return;
    try {
      await deleteExpense(id).unwrap();
    } catch {
      /* handled by RTK */
    }
  };

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <Card className='rounded-3xl border border-border/70 bg-card/80 shadow-sm'>
        <CardHeader className='flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between'>
          <div className='space-y-2'>
            <CardTitle className='text-2xl'>Expenses</CardTitle>
            <CardDescription className='max-w-2xl'>Track and manage business expenses.</CardDescription>
          </div>
          <AddExpense categories={categories} branches={branches} />
        </CardHeader>
        <CardContent className='flex flex-wrap items-center gap-3 border-t border-border/60 pt-4'>
          <Link
            to='/admin/expense-categories'
            className='inline-flex items-center gap-2 text-sm font-medium text-primary hover:underline'
          >
            <Tags className='h-4 w-4' />
            Manage expense categories
          </Link>
        </CardContent>
      </Card>

      <ExpenseStatsCards totalAmount={totalAmount} pendingCount={pendingCount} approvedCount={approvedCount} />

      <ExpenseFilters
        categories={categories}
        filters={filters}
        search={search}
        onFilterChange={handleFilterChange}
        onSearchChange={(v) => {
          setSearch(v);
          setPage(1);
        }}
        onClear={clearFilters}
      />

      <ExpenseTable
        expenses={expenses}
        currentPage={currentPage}
        totalPages={totalPages}
        onPageChange={setPage}
        onApprove={handleApprove}
        onEdit={handleEdit}
        onDelete={handleDelete}
      />

      <ExpenseCharts monthlySummary={monthlySummary} totalsByCategory={totalsByCategory} />

      <EditExpense
        open={editOpen}
        onOpenChange={(o) => {
          setEditOpen(o);
          if (!o) setSelected(null);
        }}
        expense={selected}
        categories={categories}
      />

      <ApproveExpenseDialog
        open={approveOpen}
        onOpenChange={(o) => {
          setApproveOpen(o);
          if (!o) setSelected(null);
        }}
        expense={selected}
      />
    </div>
  );
};
