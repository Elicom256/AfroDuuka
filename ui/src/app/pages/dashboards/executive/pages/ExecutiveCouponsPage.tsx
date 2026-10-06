import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { useCouponsQuery, useCreateCouponMutation, useDeleteCouponMutation } from '@/app/store/features/coupons/couponsQuery';
import { Tag, Plus, Trash2 } from 'lucide-react';
import { toast } from 'sonner';
import { ConfirmDeleteButton } from '@/components/ConfirmDeleteButton';

export const ExecutiveCouponsPage = () => {
  const { data, isLoading } = useCouponsQuery();
  const [createCoupon] = useCreateCouponMutation();
  const [deleteCoupon] = useDeleteCouponMutation();
  const coupons = data?.data ?? [];
  const [open, setOpen] = useState(false);
  const [form, setForm] = useState({ discount_type: 'percentage', discount_value: '', valid_from: '', valid_until: '', description: '' });

  const handleCreate = async () => {
    try {
      await createCoupon(form).unwrap();
      toast.success('Coupon created');
      setOpen(false);
      setForm({ discount_type: 'percentage', discount_value: '', valid_from: '', valid_until: '', description: '' });
    } catch (err: any) {
      toast.error(err?.data?.message || 'Failed to create coupon');
    }
  };
  if (isLoading) return <PageLoadingState />;

  return (
    <Card className='border-border/60'>
      <CardHeader className='flex flex-row items-center justify-between'>
        <CardTitle className='text-lg flex items-center gap-2'>
          <Tag className='h-5 w-5' />
          Coupons
        </CardTitle>
        <Dialog open={open} onOpenChange={setOpen}>
          <DialogTrigger asChild>
            <Button size='sm'><Plus className='h-4 w-4 mr-1' /> Add Coupon</Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader><DialogTitle>Create Coupon</DialogTitle></DialogHeader>
            <div className='space-y-4'>
              <div>
                <Label htmlFor='description'>Description</Label>
                <Input id='description' value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} />
              </div>
              <div>
                <Label htmlFor='discount-type'>Discount Type</Label>
                <Select value={form.discount_type} onValueChange={(v) => setForm({ ...form, discount_type: v })}>
                  <SelectTrigger id='discount-type'><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value='percentage'>Percentage</SelectItem>
                    <SelectItem value='fixed'>Fixed</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div>
                <Label htmlFor='discount-value'>Discount Value</Label>
                <Input id='discount-value' type='number' value={form.discount_value} onChange={(e) => setForm({ ...form, discount_value: e.target.value })} />
              </div>
              <div className='grid grid-cols-2 gap-4'>
                <div>
                  <Label htmlFor='valid-from'>Valid From</Label>
                  <Input id='valid-from' type='date' value={form.valid_from} onChange={(e) => setForm({ ...form, valid_from: e.target.value })} />
                </div>
                <div>
                  <Label htmlFor='valid-until'>Valid Until</Label>
                  <Input id='valid-until' type='date' value={form.valid_until} onChange={(e) => setForm({ ...form, valid_until: e.target.value })} />
                </div>
              </div>
              <Button className='w-full' onClick={handleCreate}>Create Coupon</Button>
            </div>
          </DialogContent>
        </Dialog>
      </CardHeader>
      <CardContent>
        {coupons.length === 0 ? (
          <p className='text-sm text-muted-foreground'>No coupons yet.</p>
        ) : (
          <div className='space-y-3'>
            {coupons.map((coupon: any) => (
              <div key={coupon.id} className='rounded-3xl border border-border/70 bg-muted p-4 flex items-center justify-between'>
                <div>
                  <p className='font-mono font-semibold'>{coupon.code}</p>
                  <p className='text-sm text-muted-foreground'>{coupon.description}</p>
                  <div className='flex gap-3 mt-1 text-xs text-muted-foreground'>
                    <span>{coupon.discount_type === 'percentage' ? `${coupon.discount_value}%` : `${coupon.discount_value}`}</span>
                    <span>{coupon.valid_from} → {coupon.valid_until}</span>
                  </div>
                </div>
                <div className='flex items-center gap-2'>
                  <Badge variant={coupon.status === 'active' ? 'default' : 'secondary'}>{coupon.status}</Badge>
                  <ConfirmDeleteButton
                    onConfirm={() =>
                      deleteCoupon(coupon.id)
                        .unwrap()
                        .then(() => toast.success('Coupon deleted'))
                    }
                    title={`Delete ${coupon.code || 'this coupon'}?`}
                    description='Customers holding it will stop being able to redeem it.'
                    trigger={
                      <Button variant='ghost' size='icon' aria-label='Delete coupon'>
                        <Trash2 className='h-4 w-4' />
                      </Button>
                    }
                  />
                </div>
              </div>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  );
};
