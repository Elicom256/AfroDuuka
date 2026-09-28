import { useState, useEffect } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { useGetBusinessQuery, useUpdateBusinessMutation, useGetBusinessCategoriesQuery } from '@/app/store/features/business/setup/businessQuery';
import { toast } from 'sonner';
import { Building2, Save } from 'lucide-react';

export const BusinessInfoSettings = () => {
  const { data, isLoading } = useGetBusinessQuery();
  const { data: catsData } = useGetBusinessCategoriesQuery();
  const [updateBusiness, { isLoading: isUpdating }] = useUpdateBusinessMutation();
  const business = data?.data;
  const categories = catsData?.data || [];

  const [form, setForm] = useState({ name: '', email: '', phone: '', address: '', business_category_id: '' });

  useEffect(() => {
    if (business) {
      setForm({
        name: business.name || '',
        email: business.email || '',
        phone: business.phone || '',
        address: business.address || '',
        business_category_id: String(business.business_category_id || ''),
      });
    }
  }, [business]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await updateBusiness(form).unwrap();
      toast.success('Business info updated');
    } catch (err: any) {
      toast.error(err?.data?.message || 'Failed to update');
    }
  };

  if (isLoading) {
    return (
      <Card>
        <CardHeader><CardTitle>Business Information</CardTitle></CardHeader>
        <CardContent className='space-y-4'>
          {[...Array(4)].map((_, i) => <Skeleton key={i} className='h-10 w-full' />)}
        </CardContent>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader>
        <div className='flex items-center gap-3'>
          <Building2 className='h-6 w-6 text-primary' />
          <div>
            <CardTitle>Business Information</CardTitle>
            <CardDescription>Update your business name, contact details, and category</CardDescription>
          </div>
        </div>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit} className='space-y-4 max-w-lg'>
          <div className='space-y-2'>
            <Label>Business Name</Label>
            <Input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} placeholder='Your business name' />
          </div>
          <div className='space-y-2'>
            <Label>Email</Label>
            <Input type='email' value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} placeholder='business@example.com' />
          </div>
          <div className='space-y-2'>
            <Label>Phone</Label>
            <Input value={form.phone} onChange={(e) => setForm({ ...form, phone: e.target.value })} placeholder='0772000000' />
          </div>
          <div className='space-y-2'>
            <Label>Address</Label>
            <Input value={form.address} onChange={(e) => setForm({ ...form, address: e.target.value })} placeholder='Kampala, Uganda' />
          </div>
          <div className='space-y-2'>
            <Label>Category</Label>
            <Select value={form.business_category_id} onValueChange={(v) => setForm({ ...form, business_category_id: v })}>
              <SelectTrigger><SelectValue placeholder='Select category' /></SelectTrigger>
              <SelectContent>
                {categories.map((c: any) => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}
              </SelectContent>
            </Select>
          </div>
          <Button type='submit' disabled={isUpdating}>
            <Save className='h-4 w-4 mr-2' />
            {isUpdating ? 'Saving...' : 'Save Changes'}
          </Button>
        </form>
      </CardContent>
    </Card>
  );
};
