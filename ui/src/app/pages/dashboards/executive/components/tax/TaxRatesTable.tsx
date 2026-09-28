import { useState } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Trash2 } from 'lucide-react';
import {
  useDeleteTaxRateMutation,
  useTaxCategoriesQuery,
  useTaxRatesQuery,
  useUpdateTaxRateMutation,
} from '@/app/store/features/business/tax/taxQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { AddTaxRate } from './AddTaxRate';
import { EditTaxRate } from './EditTaxRate';

export const TaxRatesTable = () => {
  const { data, isLoading } = useTaxRatesQuery();
  const { data: categoriesData } = useTaxCategoriesQuery();
  const [deleteRate] = useDeleteTaxRateMutation();
  const [toggleRate] = useUpdateTaxRateMutation();
  const [categoryFilter, setCategoryFilter] = useState('all');

  const categories = categoriesData?.categories ?? [];

  const handleDelete = async (id: number) => {
    if (!confirm('Are you sure you want to delete this tax rate?')) return;
    try {
      const res = await deleteRate(id).unwrap();
      toast.success(res?.message || 'Tax rate deleted');
    } catch {
      toast.error('Failed to delete tax rate');
    }
  };

  const handleToggle = async (rate: any) => {
    try {
      const res = await toggleRate({ id: rate.id, body: { is_active: !rate.is_active } }).unwrap();
      toast.success(res?.message || `Tax rate ${rate.is_active ? 'deactivated' : 'activated'}`);
    } catch {
      toast.error('Failed to update tax rate status');
    }
  };

  if (isLoading) return <PageLoadingState />;
  const rates = (data?.rates ?? []).filter(
    (rate: any) => categoryFilter === 'all' || String(rate.tax_category_id) === categoryFilter,
  );

  return (
    <Card>
      <CardHeader className='flex flex-row items-center justify-between'>
        <CardTitle>Tax Rates</CardTitle>
        <div className='flex items-center gap-2'>
          <Select value={categoryFilter} onValueChange={setCategoryFilter}>
            <SelectTrigger className='w-52'>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value='all'>All categories</SelectItem>
              {categories.map((category: any) => (
                <SelectItem key={category.id} value={String(category.id)}>{category.name}</SelectItem>
              ))}
            </SelectContent>
          </Select>
          <AddTaxRate />
        </div>
      </CardHeader>
      <CardContent className='p-0'>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Category</TableHead>
              <TableHead>Name</TableHead>
              <TableHead>Rate</TableHead>
              <TableHead>Jurisdiction</TableHead>
              <TableHead>Status</TableHead>
              <TableHead className='w-36'>Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {rates.length === 0 ? (
              <TableRow>
                <TableCell colSpan={6} className='py-10 text-center text-muted-foreground'>No tax rates found.</TableCell>
              </TableRow>
            ) : (
              rates.map((rate: any) => {
                const category = categories.find((c: any) => c.id === rate.tax_category_id);
                return (
                  <TableRow key={rate.id}>
                    <TableCell className='font-medium'>{category?.name ?? `#${rate.tax_category_id}`}</TableCell>
                    <TableCell>{rate.name}</TableCell>
                    <TableCell>{((Number(rate.rate) || 0) * 100).toFixed(2)}%</TableCell>
                    <TableCell className='text-muted-foreground'>{rate.jurisdiction_zone || '—'}</TableCell>
                    <TableCell>
                      <Badge variant={rate.is_active ? 'default' : 'secondary'}>
                        {rate.is_active ? 'Active' : 'Inactive'}
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <div className='flex gap-2'>
                        <Button variant='ghost' size='sm' onClick={() => handleToggle(rate)}>
                          {rate.is_active ? 'Deactivate' : 'Activate'}
                        </Button>
                        <EditTaxRate rate={rate} />
                        <Button variant='ghost' size='icon' onClick={() => handleDelete(rate.id)}>
                          <Trash2 className='h-4 w-4' />
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                );
              })
            )}
          </TableBody>
        </Table>
      </CardContent>
    </Card>
  );
};