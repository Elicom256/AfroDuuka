import {
  useDeleteProductCategoryMutation,
  useProductCategoriesQuery,
} from '@/app/store/features/business/products/productsQuery';
import { ArrowLeftCircle, Trash2 } from 'lucide-react';
import { Link } from 'react-router-dom';
import { Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { toast } from 'sonner';
import { AddProductCategory } from './AddProductCategory';
import { EditProductCategory } from './EditProductCategory';

export const ProductCategories = () => {
  const { data, isLoading } = useProductCategoriesQuery();
  const [remove, { isLoading: deleting }] = useDeleteProductCategoryMutation();

  const handleDelete = async (id: number) => {
    try {
      const res = await remove(id).unwrap();
      console.log('Deleted==>', res);
      toast.success('Category deleted successfully');
    } catch (error) {
      toast.error('Failed to delete category');
    }
  };

  if (isLoading) return <PageLoadingState />;

  const categories = data?.categories || [];

  return (
    <div className='p-6'>
      <div className='mb-4'>
        <Link to='../products' className='flex items-center gap-2 text-blue-400 hover:underline'>
          <ArrowLeftCircle />
          <span>Back to Products</span>
        </Link>
      </div>
      <div className='mb-6 flex justify-between items-center'>
        <div>
          <h1 className='text-2xl font-bold'>Product Categories</h1>
          <p className='text-muted-foreground'>Manage your product categories</p>
        </div>
        <AddProductCategory />
      </div>
      <div className='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4'>
        {categories.map((category: any) => (
          <Card key={category.id} className='hover:shadow-md transition-shadow'>
            <CardHeader>
              <CardAction className='rounded-full bg-white/20 px-2'>ID: {category.id}</CardAction>
              <CardTitle className='text-lg'>{category.name}</CardTitle>
              <CardDescription>{category.description || 'No description'}</CardDescription>
            </CardHeader>
            <CardContent>
              <div className='flex gap-2'>
                <EditProductCategory category={category} />
                <Button
                  variant='outline'
                  size='sm'
                  className='text-red-400 hover:text-red-600'
                  onClick={() => handleDelete(category.id)}
                  disabled={deleting}
                >
                  <Trash2 className='h-4 w-4 mr-2' />
                  Delete
                </Button>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
      {categories.length === 0 && (
        <div className='text-center py-8'>
          <p className='text-muted-foreground'>No categories found. Add some categories to get started.</p>
        </div>
      )}
    </div>
  );
};
