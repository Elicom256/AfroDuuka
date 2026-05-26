import { PageLoadingState } from '@/utils/PageLoadingState';
import { useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { Card, CardAction, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { EditProduct } from './EditProduct';
import { ArrowLeftCircle, Trash2 } from 'lucide-react';
import {
  useBranchProductQuery,
  useDeleteBranchProductMutation,
} from '@/app/store/features/branch/products/branchProductsQuery';
import { toast } from 'sonner';

export const Product = () => {
  const { id } = useParams();
  const { data, isLoading, error } = useBranchProductQuery(id as string, { skip: !id });
  const [deleteProd, { isLoading: isDeleting }] = useDeleteBranchProductMutation();
  const navigate = useNavigate();
  // console.log('product==>', data);

  const [editOpen, setEditOpen] = useState(false);

  if (isLoading) return <PageLoadingState />;
  if (error) return <div>Error loading product</div>;
  if (!data) return <div>Product not found</div>;

  const product = data?.product;
  // console.log('product==>', product);
  const handleDelete = async (id: string) => {
    try {
      const res = await deleteProd(id).unwrap();
      console.log('Deleted ==>', res);
      if (res) {
        toast.success(res.message);
      }
      return navigate('../products');
    } catch (error) {
      console.log('Error on del==>', error);
      toast.error('Failed to delete product');
    }
  };
  if (isDeleting) {
    return <PageLoadingState />;
  }
  return (
    <div className=''>
      {product ? (
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
              <span>Product Id: {product?.id}</span>
            </div>

            <div className='flex items-center gap-4'>
              <Button onClick={() => setEditOpen(true)}>Edit Product</Button>
              {<Trash2 size={20} className='text-red-400 cursor-pointer' onClick={() => handleDelete(product.id)} />}
            </div>
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
                  <p className=''>{product.product_id ?? '-'}</p>
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
      ) : (
        <p>No product found</p>
      )}
    </div>
  );
};
