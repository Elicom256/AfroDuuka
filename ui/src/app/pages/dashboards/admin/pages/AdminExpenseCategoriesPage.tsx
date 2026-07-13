import {
  useGetExpenseCategoriesQuery,
} from '@/app/store/features/business/admin/expenseCategoriesQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { AddExpenseCategory } from '../components/expenses/AddExpenseCategory';
import { ExpenseCategoryTable } from '../components/expenses/ExpenseCategoryTable';

export const AdminExpenseCategoriesPage = () => {
  const { data, isLoading } = useGetExpenseCategoriesQuery();
  const categories = data?.expense_categories?.data ?? [];

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <div className='flex items-center justify-between'>
        <div>
          <h1 className='text-3xl font-bold tracking-tight'>Expense Categories</h1>
          <p className='text-muted-foreground'>Manage categories for expense classification.</p>
        </div>
        <AddExpenseCategory />
      </div>
      <ExpenseCategoryTable categories={categories} />
    </div>
  );
};
