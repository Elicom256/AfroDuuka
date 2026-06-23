import { useEffect, useState } from 'react';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { format } from 'date-fns';
import { CalendarIcon } from 'lucide-react';
import { cn } from '@/lib/utils';
import {
  useStoreEmployeeSalaryMutation,
  useUpdateEmployeeSalaryMutation,
} from '@/app/store/features/business/admin/employeeSalaryQuery';
import { useGetWorkersInfoQuery } from '@/app/store/features/business/workers/workersQuery';
import { toast } from 'sonner';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { useCurrency } from '@/app/hooks/useCurrency';

type EmployeeSalaryFormProps = {
  editItem?: any;
  trigger?: React.ReactNode;
  open?: boolean;
  onOpenChange?: (open: boolean) => void;
};

export const EmployeeSalaryForm = ({
  editItem,
  trigger,
  open: controlledOpen,
  onOpenChange,
}: EmployeeSalaryFormProps) => {
  const [internalOpen, setInternalOpen] = useState(false);
  const open = controlledOpen ?? internalOpen;
  const setOpen = onOpenChange ?? setInternalOpen;

  const [storeSalary, { isLoading: isStoring, error }] = useStoreEmployeeSalaryMutation();
  const [updateSalary, { isLoading: isUpdating }] = useUpdateEmployeeSalaryMutation();
  const { data: workersData, isLoading: workersLoading } = useGetWorkersInfoQuery();
  const workers = workersData?.workers ?? [];

  const { currency: businessCurrency } = useCurrency();
  const isEdit = Boolean(editItem);

  const [formData, setFormData] = useState({
    worker_id: '',
    amount: '',
    currency: businessCurrency,
    effective_date: format(new Date(), 'yyyy-MM-dd'),
    end_date: '',
    status: 'active',
  });

  useEffect(() => {
    if (open && editItem) {
      setFormData({
        worker_id: editItem.worker_id?.toString() ?? '',
        amount: editItem.amount?.toString() ?? '',
        currency: editItem.currency ?? businessCurrency,
        effective_date: editItem.effective_date ?? format(new Date(), 'yyyy-MM-dd'),
        end_date: editItem.end_date ?? '',
        status: editItem.status ?? 'active',
      });
    } else if (open && !editItem) {
      // Reset for new entry
      setFormData({
        worker_id: '',
        amount: '',
        currency: businessCurrency,
        effective_date: format(new Date(), 'yyyy-MM-dd'),
        end_date: '',
        status: 'active',
      });
    }
  }, [open, editItem, businessCurrency]);
  const updateForm = (key: string, value: any) => {
    setFormData((prev) => ({ ...prev, [key]: value }));
  };

  const resetForm = () => {
    setFormData({
      worker_id: '',
      amount: '',
      currency: businessCurrency,
      effective_date: format(new Date(), 'yyyy-MM-dd'),
      end_date: '',
      status: 'active',
    });
  };

  const handleSubmit = async (e: React.SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault();

    const workerId = formData.worker_id;
    const amount = Number(formData.amount);

    if ((!workerId && !isEdit) || (!formData.effective_date && !isEdit)) {
      toast.error('Please fill in all required fields');
      return;
    }

    try {
      const payload: any = {
        worker_id: workerId,
        amount,
        currency: formData.currency,
        effective_date: formData.effective_date,
        status: formData.status,
      };
      if (formData.end_date) payload.end_date = formData.end_date;

      if (isEdit) {
        const res = await updateSalary({ id: editItem.id, body: payload }).unwrap();
        console.log('create res==>', res);
        toast.success(res.message);
      } else {
        const res = await storeSalary(payload).unwrap();
        console.log('update res==>', res);
        toast.success(res.message);
      }

      setOpen(false);
      resetForm();
    } catch (error) {
      console.error('Salary save error:', error);
    }
  };

  if (workersLoading || isStoring || isUpdating) return <PageLoadingState />;

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{trigger || <Button>{isEdit ? 'Edit Salary' : 'Add Salary'}</Button>}</DialogTrigger>

      <DialogContent className='sm:max-w-lg max-h-[85vh] overflow-y-scroll'>
        <DialogHeader>
          <DialogTitle>{isEdit ? 'Edit Employee Salary' : 'Add Employee Salary'}</DialogTitle>
          <DialogDescription>
            {isEdit ? 'Update the salary details for the selected employee.' : 'Set a monthly salary for an employee.'}
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit} className='space-y-6 py-4'>
          <div className='grid grid-cols-1 lg:grid-cols-2 gap-6'>
            <div className={isEdit ? 'hidden' : 'space-y-2'}>
              <Label htmlFor='worker_id'>
                Employee <span className='text-red-500'>*</span>
              </Label>
              <Select
                value={formData.worker_id}
                onValueChange={(value) => updateForm('worker_id', value)}
                disabled={isEdit}
              >
                <SelectTrigger>
                  <SelectValue placeholder='Select employee' />
                </SelectTrigger>
                <SelectContent>
                  {workers?.map((emp: any) => (
                    <SelectItem key={emp.id} value={emp.id.toString()}>
                      {`${emp.user?.firstname ?? ''} ${emp.user?.lastname ?? ''}`.trim() || emp.user?.email}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className='space-y-2'>
              <Label htmlFor='amount'>
                Amount <span className='text-red-500'>*</span>
              </Label>
              <Input
                id='amount'
                type='number'
                step='0.01'
                placeholder='1500000.00'
                value={formData.amount}
                onChange={(e) => updateForm('amount', e.target.value)}
                required
              />
            </div>

            <div className='space-y-2'>
              <Label>Currency</Label>
              <Select value={formData.currency} onValueChange={(value) => updateForm('currency', value)}>
                <SelectTrigger>
                  <SelectValue placeholder='Select currency' />
                </SelectTrigger>
                <SelectContent>
                  {![ 'UGX', 'USD', 'EUR', 'KES', 'TZS' ].includes(businessCurrency) && (
                    <SelectItem value={businessCurrency}>{businessCurrency}</SelectItem>
                  )}
                  <SelectItem value='UGX'>UGX</SelectItem>
                  <SelectItem value='USD'>USD</SelectItem>
                  <SelectItem value='EUR'>EUR</SelectItem>
                  <SelectItem value='KES'>KES</SelectItem>
                  <SelectItem value='TZS'>TZS</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className='space-y-2'>
              <Label>Status</Label>
              <Select value={formData.status} onValueChange={(value) => updateForm('status', value)}>
                <SelectTrigger>
                  <SelectValue placeholder='Select status' />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value='active'>Active</SelectItem>
                  <SelectItem value='inactive'>Inactive</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className='space-y-2'>
              <Label>
                Effective Date <span className='text-red-500'>*</span>
              </Label>
              <Popover>
                <PopoverTrigger asChild>
                  <Button
                    variant='outline'
                    className={cn(
                      'w-full justify-start text-left font-normal',
                      !formData.effective_date && 'text-muted-foreground',
                    )}
                  >
                    <CalendarIcon className='mr-2 h-4 w-4' />
                    {formData.effective_date ? format(new Date(formData.effective_date), 'PPP') : 'Pick a date'}
                  </Button>
                </PopoverTrigger>
                <PopoverContent className='w-auto p-0'>
                  <Calendar
                    mode='single'
                    selected={formData.effective_date ? new Date(formData.effective_date) : undefined}
                    onSelect={(date) => updateForm('effective_date', date ? format(date, 'yyyy-MM-dd') : '')}
                  />
                </PopoverContent>
              </Popover>
            </div>

            <div className='space-y-2'>
              <Label>End Date (optional)</Label>
              <Popover>
                <PopoverTrigger asChild>
                  <Button
                    variant='outline'
                    className={cn(
                      'w-full justify-start text-left font-normal',
                      !formData.end_date && 'text-muted-foreground',
                    )}
                  >
                    <CalendarIcon className='mr-2 h-4 w-4' />
                    {formData.end_date ? format(new Date(formData.end_date), 'PPP') : 'Pick a date'}
                  </Button>
                </PopoverTrigger>
                <PopoverContent className='w-auto p-0'>
                  <Calendar
                    mode='single'
                    selected={formData.end_date ? new Date(formData.end_date) : undefined}
                    onSelect={(date) => updateForm('end_date', date ? format(date, 'yyyy-MM-dd') : '')}
                  />
                </PopoverContent>
              </Popover>
            </div>
          </div>

          <DialogFooter>
            <Button
              type='button'
              variant='outline'
              onClick={() => {
                setOpen(false);
                resetForm();
              }}
              disabled={isStoring || isUpdating}
            >
              Cancel
            </Button>
            <Button type='submit' disabled={isStoring || isUpdating}>
              {isStoring || isUpdating ? 'Saving...' : isEdit ? 'Update Salary' : 'Add Salary'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
