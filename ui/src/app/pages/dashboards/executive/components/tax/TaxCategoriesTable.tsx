import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Trash2 } from 'lucide-react';
import {
  useDeleteTaxCategoryMutation,
  useTaxCategoriesQuery,
  useUpdateTaxCategoryMutation,
} from '@/app/store/features/business/tax/taxQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { AddTaxCategory } from './AddTaxCategory';
import { EditTaxCategory } from './EditTaxCategory';

export const TaxCategoriesTable = () => {
  const { data, isLoading } = useTaxCategoriesQuery();
  const [deleteCategory] = useDeleteTaxCategoryMutation();
  const [toggleCategory] = useUpdateTaxCategoryMutation();

  const handleDelete = async (id: number) => {
    if (!confirm('Deleting a referenced category is blocked. Prefer deactivating it. Continue?')) return;
    try {
      const res = await deleteCategory(id).unwrap();
      toast.success(res?.message || 'Tax category deleted');
    } catch (err: any) {
      toast.error(err?.data?.message || 'Failed to delete tax category');
    }
  };

  const handleToggle = async (category: any) => {
    try {
      const res = await toggleCategory({ id: category.id, body: { is_active: !category.is_active } }).unwrap();
      toast.success(res?.message || `Tax category ${category.is_active ? 'deactivated' : 'activated'}`);
    } catch {
      toast.error('Failed to update tax category status');
    }
  };

  if (isLoading) return <PageLoadingState />;
  const categories = data?.categories ?? [];

  return (
    <Card>
      <CardHeader className='flex flex-row items-center justify-between'>
        <CardTitle>Tax Categories</CardTitle>
        <AddTaxCategory />
      </CardHeader>
      <CardContent className='p-0'>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Name</TableHead>
              <TableHead>Description</TableHead>
              <TableHead>Rates</TableHead>
              <TableHead>Status</TableHead>
              <TableHead className='w-36'>Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {categories.length === 0 ? (
              <TableRow>
                <TableCell colSpan={5} className='py-10 text-center text-muted-foreground'>No tax categories found.</TableCell>
              </TableRow>
            ) : (
              categories.map((category: any) => (
                <TableRow key={category.id}>
                  <TableCell className='font-medium'>{category.name}</TableCell>
                  <TableCell className='text-muted-foreground'>{category.description || '—'}</TableCell>
                  <TableCell>{(category.rates ?? []).length}</TableCell>
                  <TableCell>
                    <Badge variant={category.is_active ? 'default' : 'secondary'}>
                      {category.is_active ? 'Active' : 'Inactive'}
                    </Badge>
                  </TableCell>
                  <TableCell>
                    <div className='flex gap-2'>
                      <Button variant='ghost' size='sm' onClick={() => handleToggle(category)} title='Toggle active'>
                        {category.is_active ? 'Deactivate' : 'Activate'}
                      </Button>
                      <EditTaxCategory category={category} />
                      <Button variant='ghost' size='icon' onClick={() => handleDelete(category.id)}>
                        <Trash2 className='h-4 w-4' />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </CardContent>
    </Card>
  );
};