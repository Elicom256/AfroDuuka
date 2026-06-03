import { useGetAdminEmployeeRemunerationQuery } from '@/app/store/features/business/admin/employeeRemunerationQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { RemunerationPanel } from '../components/remuneration/RemunerationPanel';

export const AdminEmployeeRemunerationPage = () => {
  const { data, isLoading } = useGetAdminEmployeeRemunerationQuery();

  if (isLoading) return <PageLoadingState />;

  const payroll = Array.isArray(data) ? data : (data?.payroll ?? data?.records ?? []);

  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-3xl font-bold tracking-tight'>Employee Remuneration</h1>
        <p className='text-muted-foreground'>Review payroll disbursements and employee payout status.</p>
      </div>
      <RemunerationPanel payroll={payroll} />
    </div>
  );
};
