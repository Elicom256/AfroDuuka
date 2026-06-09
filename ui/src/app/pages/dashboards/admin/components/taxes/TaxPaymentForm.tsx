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
import {
  useGetAdminTaxesQuery,
  useRecordAdminTaxPaymentMutation,
} from '@/app/store/features/business/admin/taxesQuery';

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
  const taxes = data?.business_taxes?.data ?? [];

  const [recordTaxPayment, { isLoading: isSubmitting }] = useRecordAdminTaxPaymentMutation();

  const [formData, setFormData] = useState({
    business_tax_id: '',
    amount: '', // ← This is the taxable base amount
    tax_period: '',
    due_date: format(new Date(), 'yyyy-MM-dd'),
    payment_date: format(new Date(), 'yyyy-MM-dd'),
    paid_amount: '',
    status: 'paid',
    reference_number: '',
    payment_method: 'mpesa',
    notes: '',
  });

  const handleSubmit = async (e: React.SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault();

    if (!formData.business_tax_id || !formData.amount || !formData.tax_period) {
      alert('Please fill required fields');
      return;
    }

    // normalize numbers
    const payload = {
      ...formData,
      business_tax_id: parseInt(formData.business_tax_id as unknown as string, 10),
      amount: parseFloat(formData.amount as unknown as string),
      paid_amount: formData.paid_amount ? parseFloat(formData.paid_amount as unknown as string) : undefined,
    } as Record<string, any>;

    try {
      if (onSubmit) {
        await onSubmit(payload);
      } else {
        await recordTaxPayment(payload).unwrap();
      }

      alert('Tax payment recorded successfully!');
      setOpen(false);

      // Reset form
      setFormData({
        business_tax_id: '',
        amount: '',
        tax_period: '',
        due_date: format(new Date(), 'yyyy-MM-dd'),
        payment_date: format(new Date(), 'yyyy-MM-dd'),
        paid_amount: '',
        status: 'paid',
        reference_number: '',
        payment_method: 'mpesa',
        notes: '',
      });
    } catch (error: any) {
      console.error(error);
      alert(error?.data?.message || 'Failed to record tax payment');
    }
  };

  const updateForm = (key: string, value: any) => {
    setFormData((prev) => ({ ...prev, [key]: value }));
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button>Record Tax Payment</Button>
      </DialogTrigger>

      <DialogContent className='sm:max-w-[520px]'>
        <DialogHeader>
          <DialogTitle>Record Tax Payment</DialogTitle>
          <DialogDescription>
            Select tax type and enter the base amount. The actual tax amount will be calculated on the backend.
          </DialogDescription>
        </DialogHeader>

        <form onSubmit={handleSubmit} className='space-y-6 py-4'>
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

          {/* Base Amount - Important! */}
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
            <Label htmlFor='tax_period'>
              Tax Period <span className='text-red-500'>*</span>
            </Label>
            <Input
              id='tax_period'
              placeholder='2026-Q2 or FY2026'
              value={formData.tax_period}
              onChange={(e) => updateForm('tax_period', e.target.value)}
              required
            />
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
                  selected={new Date(formData.due_date)}
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
                  selected={new Date(formData.payment_date)}
                  onSelect={(date) => updateForm('payment_date', date ? format(date, 'yyyy-MM-dd') : '')}
                />
              </PopoverContent>
            </Popover>
          </div>

          {/* Paid Amount (Optional - if different from calculated tax) */}
          <div className='space-y-2'>
            <Label htmlFor='paid_amount'>Paid Amount (Optional)</Label>
            <Input
              id='paid_amount'
              type='number'
              step='0.01'
              placeholder='Leave empty to use calculated tax amount'
              value={formData.paid_amount}
              onChange={(e) => updateForm('paid_amount', e.target.value)}
            />
          </div>

          {/* Reference Number */}
          <div className='space-y-2'>
            <Label htmlFor='reference_number'>Reference Number</Label>
            <Input
              id='reference_number'
              placeholder='TAX-20260609-001'
              value={formData.reference_number}
              onChange={(e) => updateForm('reference_number', e.target.value)}
            />
          </div>

          {/* Payment Method */}
          <div className='space-y-2'>
            <Label>Payment Method</Label>
            <Select value={formData.payment_method} onValueChange={(value) => updateForm('payment_method', value)}>
              <SelectTrigger>
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value='mpesa'>M-Pesa</SelectItem>
                <SelectItem value='bank_transfer'>Bank Transfer</SelectItem>
                <SelectItem value='cash'>Cash</SelectItem>
                <SelectItem value='cheque'>Cheque</SelectItem>
                <SelectItem value='card'>Card</SelectItem>
                <SelectItem value='other'>Other</SelectItem>
              </SelectContent>
            </Select>
          </div>

          {/* Notes */}
          <div className='space-y-2'>
            <Label htmlFor='notes'>Notes</Label>
            <Textarea
              id='notes'
              placeholder='Additional information...'
              value={formData.notes}
              onChange={(e) => updateForm('notes', e.target.value)}
              rows={3}
            />
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
