'use client';

import React, { useState } from 'react';
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
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { format } from 'date-fns';
import { CalendarIcon } from 'lucide-react';
import { cn } from '@/lib/utils';
import { useStoreAdminEmployeeRemunerationMutation } from '@/app/store/features/business/admin/employeeRemunerationQuery';
import { toast } from 'sonner';

interface Employee {
  id: number;
  user: {
    username: string;
    firstname: string;
    lastname: string;
  };
}

interface PaymentFormData {
  worker_id: number;
  amount: number;
  type: string;
  payment_date: string;
  reference?: string | null;
  status?: string | null;
  description?: string;
}

interface RecordEmployeePaymentProps {
  employees: Employee[];
  trigger?: React.ReactNode;
}

const paymentTypes = ['salary', 'wage', 'bonus', 'commission', 'allowance', 'deduction', 'advance'] as const;
const statuses = ['pending', 'paid'] as const;

export const RecordEmployeePayment = ({ employees, trigger }: RecordEmployeePaymentProps) => {
  const [open, setOpen] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [recordRemuneration, { isLoading }] = useStoreAdminEmployeeRemunerationMutation();

  const [formData, setFormData] = useState({
    worker_id: '',
    amount: '',
    type: 'salary',
    payment_date: format(new Date(), 'yyyy-MM-dd'),
    reference: '',
    status: 'pending',
    description: '',
  });

  const handleSubmit = async (e: React.SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault();

    const workerId = Number(formData.worker_id);
    const amount = Number(formData.amount);

    if (!workerId || Number.isNaN(amount) || !formData.type || !formData.payment_date) {
      alert('Please fill in all required fields');
      return;
    }

    setIsSubmitting(true);

    try {
      const payload: PaymentFormData = {
        worker_id: workerId,
        amount,
        type: formData.type,
        payment_date: formData.payment_date,
        reference: formData.reference.trim() || null,
        status: formData.status || null,
        description: formData.description.trim() || undefined,
      };

      const res = await recordRemuneration(payload).unwrap();
      console.log('Remuneration payment==>', res);
      if (res) {
        toast.success(res.message);
              setOpen(false);
      resetForm();

      }
    } catch (error) {
      console.error('Failed to record payment:', error);
      alert('Failed to record payment. Please try again.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const resetForm = () => {
    setFormData({
      worker_id: '',
      amount: '',
      type: 'salary',
      payment_date: format(new Date(), 'yyyy-MM-dd'),
      reference: '',
      status: 'pending',
      description: '',
    });
  };

  const updateForm = (key: string, value: any) => {
    setFormData((prev) => ({ ...prev, [key]: value }));
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{trigger || <Button>Record Employee Payment</Button>}</DialogTrigger>

      <DialogContent className='sm:max-w-225 max-h-[85vh] overflow-y-scroll'>
        <DialogHeader>
          <DialogTitle>Record Employee Payment</DialogTitle>
          <DialogDescription>Enter the payment details for the selected employee.</DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit} className='space-y-6 py-4'>
          <div className='grid grid-cols-1 lg:grid-cols-2 gap-6'>
            {/* Employee */}
            <div className='space-y-2'>
              <Label htmlFor='worker_id'>
                Employee <span className='text-red-500'>*</span>
              </Label>
              <Select value={formData.worker_id} onValueChange={(value) => updateForm('worker_id', value)}>
                <SelectTrigger>
                  <SelectValue placeholder='Select employee' />
                </SelectTrigger>
                <SelectContent>
                  {employees?.map((emp) => (
                    <SelectItem key={emp.id} value={emp.id.toString()}>
                      {`${emp.user.firstname ?? emp.user.username}  ${emp.user.lastname}`}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            {/* Amount */}
            <div className='space-y-2'>
              <Label htmlFor='amount'>
                Amount <span className='text-red-500'>*</span>
              </Label>
              <Input
                id='amount'
                type='number'
                step='0.01'
                placeholder='1200000.00'
                value={formData.amount}
                onChange={(e) => updateForm('amount', e.target.value)}
                required
              />
            </div>

            {/* Payment Type */}
            <div className='space-y-2'>
              <Label>
                Payment Type <span className='text-red-500'>*</span>
              </Label>
              <Select value={formData.type} onValueChange={(value) => updateForm('type', value)}>
                <SelectTrigger>
                  <SelectValue placeholder='Select type' />
                </SelectTrigger>
                <SelectContent>
                  {paymentTypes.map((type) => (
                    <SelectItem key={type} value={type}>
                      {type.charAt(0).toUpperCase() + type.slice(1)}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            {/* Status */}
            <div className='space-y-2'>
              <Label>Status</Label>
              <Select value={formData.status} onValueChange={(value) => updateForm('status', value)}>
                <SelectTrigger>
                  <SelectValue placeholder='Select status' />
                </SelectTrigger>
                <SelectContent>
                  {statuses.map((status) => (
                    <SelectItem key={status} value={status}>
                      {status.charAt(0).toUpperCase() + status.slice(1)}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            {/* Payment Date */}
            <div className='space-y-2'>
              <Label>
                Payment Date <span className='text-red-500'>*</span>
              </Label>
              <Popover>
                <PopoverTrigger asChild>
                  <Button
                    variant='outline'
                    className={cn(
                      'w-full justify-start text-left font-normal',
                      !formData.payment_date && 'text-muted-foreground',
                    )}
                  >
                    <CalendarIcon className='mr-2 h-4 w-4' />
                    {formData.payment_date ? format(new Date(formData.payment_date), 'PPP') : 'Pick a date'}
                  </Button>
                </PopoverTrigger>
                <PopoverContent className='w-auto p-0'>
                  <Calendar
                    mode='single'
                    selected={formData.payment_date ? new Date(formData.payment_date) : undefined}
                    onSelect={(date) => updateForm('payment_date', date ? format(date, 'yyyy-MM-dd') : '')}
                  />
                </PopoverContent>
              </Popover>
            </div>

            {/* Reference */}
            <div className='space-y-2'>
              <Label htmlFor='reference'>Reference (Optional)</Label>
              <Input
                id='reference'
                placeholder='SAL-JUN-2026-001'
                value={formData.reference}
                onChange={(e) => updateForm('reference', e.target.value)}
              />
            </div>

            {/* Description */}
            <div className='space-y-2 lg:col-span-2'>
              <Label htmlFor='description'>Description</Label>
              <Textarea
                id='description'
                placeholder='Monthly salary payment for June 2026'
                value={formData.description}
                onChange={(e) => updateForm('description', e.target.value)}
                rows={3}
              />
            </div>
          </div>

          <DialogFooter>
            <Button type='button' variant='outline' onClick={() => setOpen(false)} disabled={isSubmitting}>
              Cancel
            </Button>
            <Button type='submit' disabled={isSubmitting}>
              {isSubmitting ? 'Recording...' : 'Record Payment'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
