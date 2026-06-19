import { useDeleteProductMutation, useProductsQuery } from '@/app/store/features/business/products/productsQuery';
import { ArrowLeftCircle, Trash2 } from 'lucide-react';
import { Link, useNavigate } from 'react-router-dom';
import { Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { toast } from 'sonner';
import { AddProductCategory } from './AddProductCategory';
import { EditBusinessProduct } from './EditBusinessProduct';
import { AddBusinessProduct } from './AddBusinessProduct';

export const BusinessProducts = () => {
  const { data, isLoading } = useProductsQuery();
  const navigate = useNavigate();
  const [remove, { isLoading: deleting }] = useDeleteProductMutation();
  const products = data?.products;
  console.log('Products in bs==>', data);
  const handleDelete = async (id: number) => {
    try {
      const res = await remove(id).unwrap();
      console.log('Deleted==>', res);
      toast.success(res.message || 'Category deleted successfully');
      if (res) {
        return navigate('../products');
      }
    } catch (error) {
      toast.error('Failed to delete category');
    }
  };

  if (isLoading) return <PageLoadingState />;

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
          <h1 className='text-2xl font-bold'>Product Types</h1>
          <p className='text-muted-foreground'>Manage your product types</p>
        </div>
        <AddBusinessProduct />
      </div>
      <div className='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4'>
        {products.map((product: any) => (
          <Card key={product.id} className='hover:shadow-md transition-shadow'>
            <CardHeader onClick={() => navigate(`/admin/business-products/${product.id}`)}>
              <CardAction className='rounded-full bg-white/20 px-2'>CatID: {product.category_id}</CardAction>
              <CardTitle className='text-lg'>{product.name}</CardTitle>
              <CardDescription>{product.description || 'No description'}</CardDescription>
            </CardHeader>
            <CardContent>
              {/* <div className="">
                <span>Cat ID</span>
                <span>{product.category_id}</span>
              </div> */}
              <div className='flex gap-2'>
                <EditBusinessProduct product={product} />
                <Button
                  variant='outline'
                  size='sm'
                  className='text-red-400 hover:text-red-600'
                  onClick={() => handleDelete(product.id)}
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
      {products.length === 0 && (
        <div className='text-center py-8'>
          <p className='text-muted-foreground'>No types found. Add some types to get started.</p>
        </div>
      )}
    </div>
  );
};
