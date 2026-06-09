// 'use client';

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

interface Employee {
  id: number;
  user: {
    name: string;
  };
  //   name: string;
  // Add other fields as needed
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
  onSubmit?: (data: PaymentFormData) => Promise<void> | void;
  trigger?: React.ReactNode;
}

const paymentTypes = ['salary', 'wage', 'bonus', 'commission', 'allowance', 'deduction', 'advance'] as const;

const statuses = ['pending', 'paid'] as const;

export const RecordEmployeePayment = ({ employees, onSubmit, trigger }: RecordEmployeePaymentProps) => {
  const [open, setOpen] = useState(false);
  const [formData, setFormData] = useState<Partial<PaymentFormData>>({
    amount: '',
    type: 'salary',
    payment_date: format(new Date(), 'yyyy-MM-dd'),
    status: 'pending',
  });
  const [isSubmitting, setIsSubmitting] = useState(false);

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
        type: formData.type as string,
        payment_date: formData.payment_date as string,
        reference: formData.reference ?? null,
        status: formData.status ?? null,
        description: formData.description ?? undefined,
      };

      await onSubmit?.(payload);
      setOpen(false);
      // Reset form
      setFormData({
        amount: '',
        type: 'salary',
        payment_date: format(new Date(), 'yyyy-MM-dd'),
        status: 'pending',
      });
    } catch (error) {
      console.error('Failed to record payment:', error);
    } finally {
      setIsSubmitting(false);
    }
  };

  const updateForm = (key: keyof PaymentFormData, value: any) => {
    setFormData((prev) => ({ ...prev, [key]: value }));
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{trigger || <Button>Record Employee Payment</Button>}</DialogTrigger>
      <DialogContent className='sm:max-w-[500px]'>
        <DialogHeader>
          <DialogTitle>Record Employee Payment</DialogTitle>
          <DialogDescription>Enter the payment details for the employee.</DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit} className='space-y-6 py-4'>
          {/* Employee Selector */}
          <div className='space-y-2'>
            <Label htmlFor='worker_id'>
              Employee <span className='text-red-500'>*</span>
            </Label>
            <Select
              value={formData.worker_id?.toString()}
              onValueChange={(value) => updateForm('worker_id', parseInt(value))}
            >
              <SelectTrigger>
                <SelectValue placeholder='Select employee' />
              </SelectTrigger>
              <SelectContent>
                {employees?.map((emp) => (
                  <SelectItem key={emp.id} value={emp.id.toString()}>
                    {emp.user.name}
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
              value={formData.amount || ''}
              onChange={(e) => updateForm('amount', parseFloat(e.target.value))}
              required
            />
          </div>

          {/* Payment Type */}
          <div className='space-y-2'>
            <Label htmlFor='type'>
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
                  //   initialFocus
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
              value={formData.reference || ''}
              onChange={(e) => updateForm('reference', e.target.value || null)}
            />
          </div>

          {/* Status */}
          <div className='space-y-2'>
            <Label htmlFor='status'>Status</Label>
            <Select value={formData.status || ''} onValueChange={(value) => updateForm('status', value || null)}>
              <SelectTrigger>
                <SelectValue placeholder='Select status (optional)' />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value=''>None</SelectItem>
                {statuses.map((status) => (
                  <SelectItem key={status} value={status}>
                    {status}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          {/* Description */}
          <div className='space-y-2'>
            <Label htmlFor='description'>Description</Label>
            <Textarea
              id='description'
              placeholder='Monthly salary payment for June 2026'
              value={formData.description || ''}
              onChange={(e) => updateForm('description', e.target.value)}
              rows={3}
            />
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
