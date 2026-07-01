import { useState } from 'react';
import { toast } from 'sonner';
import {
  useEmployeeSalariesQuery,
  useDeleteEmployeeSalaryMutation,
} from '@/app/store/features/business/admin/employeeSalaryQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { EmployeeSalaryPanel } from '../components/salary/EmployeeSalaryPanel';


export const AdminEmployeeSalaryPage = () => {
  const { data, isLoading } = useEmployeeSalariesQuery();
  const [deleteSalary] = useDeleteEmployeeSalaryMutation();
  const [, setEditItem] = useState<any>(null);
  const [, setFormOpen] = useState(false);

  
  const totalMonthly = data?.totalMonthly ?? 0;
  const activeCount = data?.activeCount ?? 0;
  const salaries = data?.employee_salaries?.data ?? [];

  if (isLoading) return <PageLoadingState />;

  const handleEdit = (item: any) => {
    setEditItem(item);
    setFormOpen(true);
  };

  const handleDelete = async (id: number) => {
    try {
      await toast.promise(deleteSalary(id).unwrap(), {
        loading: 'Deleting salary...',
        success: 'Salary deleted successfully.',
        error: 'Failed to delete salary.',
      });
    } catch (error) {
      console.error('Salary delete failed', error);
    }
  };

  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-3xl font-bold tracking-tight'>Employee Salaries</h1>
        <p className='text-muted-foreground'>Manage monthly salary records for all employees.</p>
      </div>

      <EmployeeSalaryPanel
        salaries={salaries}
        totalMonthly={totalMonthly}
        activeCount={activeCount}
        onEditRow={handleEdit}
        onDeleteRow={handleDelete}
      />

      {/* <EmployeeSalaryForm
        editItem={editItem}
        open={formOpen}
        onOpenChange={handleFormClose}
      /> */}
    </div>
  );
};
