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
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Plus } from 'lucide-react';
import {
  useAddTaxRateMutation,
  useTaxCategoriesQuery,
} from '@/app/store/features/business/tax/taxQuery';

type FormState = {
  tax_category_id: string;
  name: string;
  rate_percent: string;
  jurisdiction_zone: string;
  is_active: boolean;
};

const emptyForm: FormState = {
  tax_category_id: '',
  name: '',
  rate_percent: '',
  jurisdiction_zone: '',
  is_active: true,
};

export const AddTaxRate = () => {
  const [open, setOpen] = useState(false);
  const [addRate] = useAddTaxRateMutation();
  const { data: categoriesData } = useTaxCategoriesQuery();
  const [formData, setFormData] = useState<FormState>(emptyForm);

  const resetForm = () => setFormData(emptyForm);
  const categories = (categoriesData?.categories ?? []).filter((c: any) => c.is_active);

  const handleSubmit = async (e: React.SyntheticEvent) => {
    e.preventDefault();
    try {
      const res = await addRate({
        tax_category_id: Number(formData.tax_category_id),
        name: formData.name,
        rate: (Number(formData.rate_percent) || 0) / 100,
        jurisdiction_zone: formData.jurisdiction_zone || undefined,
        is_active: formData.is_active,
      }).unwrap();
      toast.success(res?.message || 'Tax rate created');
      setOpen(false);
      resetForm();
    } catch {
      toast.error('Failed to create tax rate');
    }
  };

  return (
    <Dialog open={open} onOpenChange={(o) => { setOpen(o); if (!o) resetForm(); }}>
      <DialogTrigger asChild>
        <Button variant='outline' size='sm'>
          <Plus className='mr-2 h-4 w-4' />
          Add Tax Rate
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Add Tax Rate</DialogTitle>
          <DialogDescription>Define a percentage rate under a tax category. Use percent, e.g. 18 for VAT 18%.</DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit}>
          <div className='grid gap-4 py-4'>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='tax-category' className='text-right'>Tax Category</Label>
              <Select value={formData.tax_category_id} onValueChange={(v) => setFormData((p) => ({ ...p, tax_category_id: v }))}>
                <SelectTrigger id='tax-category' className='col-span-3'>
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
              <Label htmlFor='rate-name' className='text-right'>Name</Label>
              <Input id='rate-name' value={formData.name} onChange={(e) => setFormData((p) => ({ ...p, name: e.target.value }))} className='col-span-3' required />
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='rate-percent' className='text-right'>Rate (%)</Label>
              <Input id='rate-percent' type='number' min='0' max='100' step='0.01' value={formData.rate_percent} onChange={(e) => setFormData((p) => ({ ...p, rate_percent: e.target.value }))} className='col-span-3' required />
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='jurisdiction' className='text-right'>Jurisdiction</Label>
              <Input id='jurisdiction' value={formData.jurisdiction_zone} onChange={(e) => setFormData((p) => ({ ...p, jurisdiction_zone: e.target.value }))} className='col-span-3' placeholder='e.g. Uganda' />
            </div>
          </div>
          <DialogFooter>
            <Button type='submit'>Create</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};