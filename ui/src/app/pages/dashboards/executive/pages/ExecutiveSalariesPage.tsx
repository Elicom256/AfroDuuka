import { useState } from 'react';
import { toast } from 'sonner';
import { useSalariesQuery, useDeleteSalaryMutation } from '@/app/store/features/business/executive/salaryQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { SalaryPanel } from '../components/salary/SalaryPanel';
import { SalaryForm } from '../components/salary/SalaryForm';

export const ExecutiveSalariesPage = () => {
  const { data, isLoading } = useSalariesQuery();
  const [deleteSalary] = useDeleteSalaryMutation();
  const [editItem, setEditItem] = useState<any>(null);
  const [formOpen, setFormOpen] = useState(false);

  const monthlyPayroll = data?.monthly_payroll ?? 0;
  const activeCount = data?.active_count ?? 0;
  const salaries = data?.salaries?.data ?? [];

  if (isLoading) return <PageLoadingState />;

  const handleEdit = (item: any) => {
    setEditItem(item);
    setFormOpen(true);
  };

  const handleFormClose = (open: boolean) => {
    setFormOpen(open);
    if (!open) setEditItem(null);
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
        <h1 className='text-3xl font-bold tracking-tight'>Salaries</h1>
        <p className='text-muted-foreground'>Set what each role is paid. Everyone holding that role is paid it.</p>
      </div>

      <SalaryPanel
        salaries={salaries}
        monthlyPayroll={monthlyPayroll}
        activeCount={activeCount}
        onEditRow={handleEdit}
        onDeleteRow={handleDelete}
      />

      {/* Keyed on the row so switching which salary is being edited remounts the form
          with that row's values. See SalaryForm's state seeding. */}
      <SalaryForm
        key={editItem?.id ?? 'new'}
        editItem={editItem}
        open={formOpen}
        onOpenChange={handleFormClose}
      />
    </div>
  );
};
