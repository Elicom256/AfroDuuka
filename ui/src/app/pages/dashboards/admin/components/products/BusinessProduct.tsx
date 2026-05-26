import { useDeleteProductMutation, useProductQuery } from '@/app/store/features/business/products/productsQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { Card, CardAction, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { EditProduct } from './EditProduct';
import { ArrowLeftCircle, Trash2 } from 'lucide-react';
import { toast } from 'sonner';

export const BusinessProduct = () => {
  const { id } = useParams();
  const { data, isLoading, error } = useProductQuery(id as string, { skip: !id });
  const [editOpen, setEditOpen] = useState(false);
  const navigate = useNavigate();

  const [remove, { isLoading: isDeleting }] = useDeleteProductMutation();

  if (isLoading) return <PageLoadingState />;
  if (error) return <div>Error loading product</div>;
  if (!data) return <div>Product not found</div>;

  const product = data?.product;

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
  if (isDeleting) {
    return <PageLoadingState />;
  }
  return (
    <div className='container mx-auto p-6'>
      <div className='mb-4'>
        <Link to='../products' className='flex items-center gap-2 text-blue-400 hover:underline'>
          <ArrowLeftCircle />
          <span>Back to Products</span>
        </Link>
      </div>
      <div className='flex justify-between items-center mb-6'>
        <div className='flex flex-col'>
          <h1 className='text-3xl font-bold'>Product Details</h1>
          <span>Product Id: {product.id}</span>
        </div>
        <Button
          variant='outline'
          size='sm'
          className='text-red-400 hover:text-red-600'
          onClick={() => handleDelete(product.id)}
          disabled={isDeleting}
        >
          <Trash2 className='h-4 w-4 mr-2' />
          Delete
        </Button>{' '}
      </div>

      <Card>
        <CardHeader>
          <CardAction>
            <Badge variant={product.status === true ? 'default' : 'secondary'}>
              {product.status === true ? 'Active' : 'Innactive'}
            </Badge>
          </CardAction>
          <CardTitle>{product.name}</CardTitle>
        </CardHeader>
        <CardContent className='space-y-4'>
          <div className='grid grid-cols-2 gap-4'>
            <div className='flex items-center gap-2'>
              <label className='text-sm font-medium text-gray-500'>SKU</label>
              <p className=''>{product.sku}</p>
            </div>

            <div className='flex items-center gap-2'>
              <label className='text-sm font-medium text-gray-500'>Barcode</label>
              <p className=''>{product.barcode || 'N/A'}</p>
            </div>

            <div className='flex items-center gap-2'>
              <label className='text-sm font-medium text-gray-500'>Price</label>
              <p className=''>UGX {product.price}</p>
            </div>

            <div className='flex items-center gap-2'>
              <label className='text-sm font-medium text-gray-500'>Cost Price</label>
              <p className=''>UGX {product.cost_price}</p>
            </div>

            <div className='flex items-center gap-2'>
              <label className='text-sm font-medium text-gray-500'>Quantity</label>
              <p className=''>{product.quantity}</p>
            </div>

            <div className='flex items-center gap-2'>
              <label className='text-sm font-medium text-gray-500'>Re-order Level</label>
              <p className=''>{product.reorder_level ?? '-'}</p>
            </div>

            <div className='flex items-center gap-2'>
              <label className='text-sm font-medium text-gray-500'>Category ID</label>
              <p className=''>{product.category_id ?? '-'}</p>
            </div>
          </div>
          <div className='flex items-center gap-2'>
            <label className='text-sm font-medium text-gray-500'>Description</label>
            <p className=''>{product.description || 'No description'}</p>
          </div>
        </CardContent>
      </Card>

      <EditProduct open={editOpen} onOpenChange={setEditOpen} product={product} />
    </div>
  );
};
