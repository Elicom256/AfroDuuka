'use client';

import React, { useState, useEffect } from 'react';
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
import {
  useGetAdminTaxesQuery,
  useRecordAdminTaxPaymentMutation,
} from '@/app/store/features/business/admin/taxesQuery';
import { useGetPaymentSettingsQuery } from '@/app/store/features/business/settings/payment';

interface Tax {
  id: number;
  name: string;
  rate: string;
  type: string;
  description?: string;
}

interface TaxPaymentFormProps {
  trigger?: React.ReactNode;
  onSubmit?: (data: Record<string, any>) => Promise<void> | void;
}

export const TaxPaymentForm = ({ trigger, onSubmit }: TaxPaymentFormProps) => {
  const [open, setOpen] = useState(false);

  const { data, isLoading: isTaxesLoading } = useGetAdminTaxesQuery();
  const { data: paymentMethods } = useGetPaymentSettingsQuery();
  const methods = paymentMethods?.methods ?? [];
  const taxes = data?.business_taxes?.data ?? [];

  const [recordTaxPayment, { isLoading: isSubmitting }] = useRecordAdminTaxPaymentMutation();

  const [formData, setFormData] = useState({
    business_tax_id: '',
    amount: '', // Taxable base amount
    tax_period: '',
    tax_year: '',
    tax_quarter: '',
    due_date: format(new Date(), 'yyyy-MM-dd'),
    payment_date: format(new Date(), 'yyyy-MM-dd'),
    paid_amount: '',
    status: 'paid',
    reference_number: '',
    payment_method_id: '',
    notes: '',
  });

  // Auto-generate tax_period when year or quarter changes
  useEffect(() => {
    if (formData.tax_year && formData.tax_quarter) {
      setFormData((prev) => ({
        ...prev,
        tax_period: `${prev.tax_year} ${prev.tax_quarter}`,
      }));
    }
  }, [formData.tax_year, formData.tax_quarter]);

  const handleSubmit = async (e: React.SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault();

    if (!formData.business_tax_id || !formData.amount || !formData.tax_period) {
      alert('Please fill all required fields');
      return;
    }

    const payload: any = {
      business_tax_id: parseInt(formData.business_tax_id, 10),
      amount: parseFloat(formData.amount),
      tax_period: formData.tax_period,
      due_date: formData.due_date,
      payment_date: formData.payment_date,
      paid_amount: formData.paid_amount ? parseFloat(formData.paid_amount) : undefined,
      status: formData.status,
      reference_number: formData.reference_number,
      payment_method_id: formData.payment_method_id || undefined,
      notes: formData.notes,
    };
    console.log('payload==>', payload);
    try {
      if (onSubmit) {
        await onSubmit(payload);
      } else {
        const res = await recordTaxPayment(payload).unwrap();
        console.log('tax payment res==>', res);
      }

      setOpen(false);
      resetForm();
    } catch (error: any) {
      console.error(error);
      alert(error?.data?.message || 'Failed to record tax payment');
    }
  };

  const resetForm = () => {
    setFormData({
      business_tax_id: '',
      amount: '',
      tax_period: '',
      tax_year: '',
      tax_quarter: '',
      due_date: format(new Date(), 'yyyy-MM-dd'),
      payment_date: format(new Date(), 'yyyy-MM-dd'),
      paid_amount: '',
      status: 'paid',
      reference_number: '',
      payment_method_id: '',
      notes: '',
    });
  };

  const updateForm = (key: string, value: any) => {
    setFormData((prev) => ({ ...prev, [key]: value }));
  };

  const currentYear = new Date().getFullYear();
  const years = Array.from({ length: 11 }, (_, i) => (currentYear - 5 + i).toString());
  const quarters = ['Q1', 'Q2', 'Q3', 'Q4'];
  const paymentStatuses = ['unpaid', 'partial', 'paid', 'overdue', 'waived', 'refunded'];

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{trigger || <Button>Record Tax Payment</Button>}</DialogTrigger>

      <DialogContent className='sm:max-w-225 max-h-[85vh] overflow-y-scroll'>
        <DialogHeader>
          <DialogTitle>Record Tax Payment</DialogTitle>
          <DialogDescription>
            Select tax type and enter the base amount. The actual tax amount will be calculated on the backend.
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit} className='space-y-6 py-4 '>
          <div className='grid grid-cols-1 lg:grid-cols-2 gap-6'>
            {/* Tax Type */}
            <div className='space-y-2'>
              <Label htmlFor='business_tax_id'>
                Tax Type <span className='text-red-500'>*</span>
              </Label>
              <Select
                value={formData.business_tax_id}
                onValueChange={(value) => updateForm('business_tax_id', value)}
                disabled={isTaxesLoading}
              >
                <SelectTrigger>
                  <SelectValue placeholder='Select tax type' />
                </SelectTrigger>
                <SelectContent>
                  {taxes.map((tax: Tax) => (
                    <SelectItem key={tax.id} value={tax.id.toString()}>
                      {tax.name} ({tax.rate}% {tax.type})
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
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {paymentStatuses.map((status) => (
                    <SelectItem key={status} value={status}>
                      {status.charAt(0).toUpperCase() + status.slice(1)}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            {/* Base Amount */}
            <div className='space-y-2'>
              <Label htmlFor='amount'>
                Base Amount (Taxable Amount) <span className='text-red-500'>*</span>
              </Label>
              <Input
                id='amount'
                type='number'
                step='0.01'
                placeholder='1250000.00'
                value={formData.amount}
                onChange={(e) => updateForm('amount', e.target.value)}
                required
              />
              <p className='text-xs text-muted-foreground'>
                This is the amount on which the tax rate will be applied by the backend.
              </p>
            </div>

            {/* Tax Period */}
            <div className='space-y-2'>
              <Label>
                Tax Period <span className='text-red-500'>*</span>
              </Label>
              <div className='grid grid-cols-2 gap-3'>
                <Select value={formData.tax_year} onValueChange={(value) => updateForm('tax_year', value)}>
                  <SelectTrigger>
                    <SelectValue placeholder='Year' />
                  </SelectTrigger>
                  <SelectContent>
                    {years.map((year) => (
                      <SelectItem key={year} value={year}>
                        {year}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>

                <Select value={formData.tax_quarter} onValueChange={(value) => updateForm('tax_quarter', value)}>
                  <SelectTrigger>
                    <SelectValue placeholder='Quarter' />
                  </SelectTrigger>
                  <SelectContent>
                    {quarters.map((quarter) => (
                      <SelectItem key={quarter} value={quarter}>
                        {quarter}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              {formData.tax_period && (
                <p className='text-sm text-muted-foreground mt-1'>Selected: {formData.tax_period}</p>
              )}
            </div>

            {/* Due Date */}
            <div className='space-y-2'>
              <Label>
                Due Date <span className='text-red-500'>*</span>
              </Label>
              <Popover>
                <PopoverTrigger asChild>
                  <Button
                    variant='outline'
                    className={cn(
                      'w-full justify-start text-left font-normal',
                      !formData.due_date && 'text-muted-foreground',
                    )}
                  >
                    <CalendarIcon className='mr-2 h-4 w-4' />
                    {formData.due_date ? format(new Date(formData.due_date), 'PPP') : 'Pick a date'}
                  </Button>
                </PopoverTrigger>
                <PopoverContent className='w-auto p-0'>
                  <Calendar
                    mode='single'
                    selected={formData.due_date ? new Date(formData.due_date) : undefined}
                    onSelect={(date) => updateForm('due_date', date ? format(date, 'yyyy-MM-dd') : '')}
                  />
                </PopoverContent>
              </Popover>
            </div>

            {/* Payment Date */}
            <div className='space-y-2'>
              <Label>Payment Date</Label>
              <Popover>
                <PopoverTrigger asChild>
                  <Button variant='outline' className={cn('w-full justify-start text-left font-normal')}>
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

            {/* Paid Amount */}
            <div className='space-y-2'>
              <Label htmlFor='paid_amount'>Paid Amount (Optional)</Label>
              <Input
                id='paid_amount'
                type='number'
                step='0.01'
                placeholder='Leave empty to use calculated tax'
                value={formData.paid_amount}
                onChange={(e) => updateForm('paid_amount', e.target.value)}
              />
            </div>

            {/* Reference Number */}
            <div className='space-y-2'>
              <Label htmlFor='reference_number'>Reference Number</Label>
              <Input
                id='reference_number'
                placeholder='TAX-20250610-001'
                value={formData.reference_number}
                onChange={(e) => updateForm('reference_number', e.target.value)}
              />
            </div>

            {/* Payment Method */}
            <div className='space-y-2'>
              <Label>Payment Method</Label>
              <Select
                value={formData.payment_method_id}
                onValueChange={(value) => updateForm('payment_method_id', value)}
              >
                <SelectTrigger>
                  <SelectValue placeholder='Select payment method' />
                </SelectTrigger>
                <SelectContent>
                  {methods.map((method: any) => (
                    <SelectItem key={method.id} value={method.id.toString()}>
                      {method.method}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            {/* Notes */}
            <div className='space-y-2 lg:col-span-2'>
              <Label htmlFor='notes'>Notes</Label>
              <Textarea
                id='notes'
                placeholder='Additional information...'
                value={formData.notes}
                onChange={(e) => updateForm('notes', e.target.value)}
                rows={3}
              />
            </div>
          </div>

          <DialogFooter>
            <Button type='button' variant='outline' onClick={() => setOpen(false)}>
              Cancel
            </Button>
            <Button type='submit' disabled={isSubmitting}>
              {isSubmitting ? 'Recording...' : 'Record Tax Payment'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
