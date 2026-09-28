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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Pencil } from 'lucide-react';
import {
  useTaxCategoriesQuery,
  useUpdateTaxRateMutation,
} from '@/app/store/features/business/tax/taxQuery';

type EditTaxRateProps = {
  rate: any;
};

type FormState = {
  tax_category_id: string;
  name: string;
  rate_percent: string;
  jurisdiction_zone: string;
  is_active: boolean;
};

export const EditTaxRate = ({ rate }: EditTaxRateProps) => {
  const [open, setOpen] = useState(false);
  const [updateRate] = useUpdateTaxRateMutation();
  const { data: categoriesData } = useTaxCategoriesQuery();
  const [formData, setFormData] = useState<FormState>({
    tax_category_id: '',
    name: '',
    rate_percent: '',
    jurisdiction_zone: '',
    is_active: true,
  });

  const categories = (categoriesData?.categories ?? []).filter((c: any) => c.is_active);

  const handleEdit = () => {
    setFormData({
      tax_category_id: String(rate.tax_category_id ?? ''),
      name: rate.name ?? '',
      rate_percent: String((Number(rate.rate) || 0) * 100),
      jurisdiction_zone: rate.jurisdiction_zone ?? '',
      is_active: rate.is_active ?? true,
    });
    setOpen(true);
  };

  const handleUpdate = async (e: React.SyntheticEvent) => {
    e.preventDefault();
    try {
      const res = await updateRate({
        id: rate.id,
        body: {
          tax_category_id: Number(formData.tax_category_id),
          name: formData.name,
          rate: (Number(formData.rate_percent) || 0) / 100,
          jurisdiction_zone: formData.jurisdiction_zone || undefined,
          is_active: formData.is_active,
        },
      }).unwrap();
      toast.success(res?.message || 'Tax rate updated');
      setOpen(false);
    } catch {
      toast.error('Failed to update tax rate');
    }
  };

  return (
    <>
      <Button variant='ghost' size='icon' onClick={handleEdit}>
        <Pencil className='h-4 w-4' />
      </Button>
      <Dialog open={open} onOpenChange={setOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Edit Tax Rate</DialogTitle>
            <DialogDescription>Update the tax rate details.</DialogDescription>
          </DialogHeader>
          <form onSubmit={handleUpdate}>
            <div className='grid gap-4 py-4'>
              <div className='grid grid-cols-4 items-center gap-4'>
                <Label className='text-right'>Tax Category</Label>
                <Select value={formData.tax_category_id} onValueChange={(v) => setFormData((p) => ({ ...p, tax_category_id: v }))}>
                  <SelectTrigger className='col-span-3'>
                    <SelectValue placeholder='Select category' />
                  </SelectTrigger>
                  <SelectContent>
                    {categories.map((category: any) => (
                      <SelectItem key={category.id} value={String(category.id)}>{category.name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div className='grid grid-cols-4 items-center gap-4'>
                <Label htmlFor='edit-rate-name' className='text-right'>Name</Label>
                <Input id='edit-rate-name' value={formData.name} onChange={(e) => setFormData((p) => ({ ...p, name: e.target.value }))} className='col-span-3' required />
              </div>
              <div className='grid grid-cols-4 items-center gap-4'>
                <Label htmlFor='edit-rate-percent' className='text-right'>Rate (%)</Label>
                <Input id='edit-rate-percent' type='number' min='0' max='100' step='0.01' value={formData.rate_percent} onChange={(e) => setFormData((p) => ({ ...p, rate_percent: e.target.value }))} className='col-span-3' required />
              </div>
              <div className='grid grid-cols-4 items-center gap-4'>
                <Label htmlFor='edit-jurisdiction' className='text-right'>Jurisdiction</Label>
                <Input id='edit-jurisdiction' value={formData.jurisdiction_zone} onChange={(e) => setFormData((p) => ({ ...p, jurisdiction_zone: e.target.value }))} className='col-span-3' />
              </div>
            </div>
            <DialogFooter>
              <Button type='submit'>Update</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </>
  );
};