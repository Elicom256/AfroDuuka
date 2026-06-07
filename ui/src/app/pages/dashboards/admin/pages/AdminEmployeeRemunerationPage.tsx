import { useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
  useGetAdminEmployeeRemunerationQuery,
  useUpdateAdminEmployeeRemunerationMutation,
} from '@/app/store/features/business/admin/employeeRemunerationQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { RemunerationPanel } from '../components/remuneration/RemunerationPanel';

export const AdminEmployeeRemunerationPage = () => {
  const { data, isLoading } = useGetAdminEmployeeRemunerationQuery();
  const [updateRemuneration, { isLoading: isUpdating }] = useUpdateAdminEmployeeRemunerationMutation();
  const [selectedPayroll, setSelectedPayroll] = useState<any>(null);
  const [statusValue, setStatusValue] = useState<string>('pending');

  const totalPaid = data?.totalPaid ?? 0;
  const employeeCount = data?.employeeCount ?? 0;
  const pending = data?.pending ?? 0;
  const payroll = data?.employee_remunerations?.data ?? [];

  if (isLoading) return <PageLoadingState />;

  const handleRowEdit = (item: any) => {
    setSelectedPayroll(item);
    setStatusValue(item?.status ?? 'pending');
  };

  const handleSaveStatus = async () => {
    if (!selectedPayroll?.id) return;

    try {
      await toast.promise(updateRemuneration({ id: selectedPayroll.id, body: { status: statusValue } }).unwrap(), {
        loading: 'Updating payroll status...',
        success: 'Payroll status saved.',
        error: 'Failed to update payroll status.',
      });
      setSelectedPayroll(null);
    } catch (error) {
      console.error('remuneration update failed', error);
    }
  };

  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-3xl font-bold tracking-tight'>Employee Remuneration</h1>
        <p className='text-muted-foreground'>Review payroll disbursements and employee payout status.</p>
      </div>

      <RemunerationPanel
        payroll={payroll}
        employeeCount={employeeCount}
        totalPaid={totalPaid}
        pending={pending}
        onEditRow={handleRowEdit}
      />

      <Dialog open={Boolean(selectedPayroll)} onOpenChange={(open) => !open && setSelectedPayroll(null)}>
        <DialogContent className='sm:max-w-lg'>
          <DialogHeader>
            <DialogTitle>Update payroll status</DialogTitle>
            <DialogDescription>Change the payout status for this employee remuneration record.</DialogDescription>
          </DialogHeader>

          <div className='space-y-4 py-2'>
            <div>
              <p className='text-sm text-muted-foreground'>Employee</p>
              <p className='mt-1 text-base font-semibold'>
                {`${selectedPayroll?.worker?.user?.firstname || ''} ${selectedPayroll?.worker?.user?.lastname || ''}`.trim() ||
                  selectedPayroll?.worker?.user?.email ||
                  'Unknown employee'}
              </p>
            </div>

            <div className='space-y-2'>
              <p className='text-sm text-muted-foreground'>Status</p>
              <Select value={statusValue} onValueChange={(value) => setStatusValue(value)}>
                <SelectTrigger>
                  <SelectValue placeholder='Select status' />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value='pending'>Pending</SelectItem>
                  <SelectItem value='paid'>Paid</SelectItem>
                  <SelectItem value='failed'>Failed</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <DialogFooter>
            <Button onClick={handleSaveStatus} disabled={isUpdating}>
              Save status
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
};
