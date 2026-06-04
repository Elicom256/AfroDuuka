import { useGetAdminEmployeeRemunerationQuery } from '@/app/store/features/business/admin/employeeRemunerationQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { RemunerationPanel } from '../components/remuneration/RemunerationPanel';

export const AdminEmployeeRemunerationPage = () => {
  const { data, isLoading } = useGetAdminEmployeeRemunerationQuery();
  // console.log('payrol data==>', data);
  const totalPaid = data?.totalPaid;
  const employeeCount = data?.employeeCount;
  const pending = data?.pending;
  if (isLoading) return <PageLoadingState />;

  const payroll = data?.employee_remunerations?.data ?? [];

  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-3xl font-bold tracking-tight'>Employee Remuneration</h1>
        <p className='text-muted-foreground'>Review payroll disbursements and employee payout status.</p>
      </div>
      <RemunerationPanel payroll={payroll} employeeCount={employeeCount} totalPaid={totalPaid} pending={pending} />
    </div>
  );
};
