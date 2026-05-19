import { useProductQuery, useUpdateProductMutation } from '@/app/store/features/business/products/productsQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { Card, CardAction, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { EditProduct } from './EditProduct';
import { ArrowLeftCircle } from 'lucide-react';
import { useBranchProductQuery } from '@/app/store/features/branch/products/branchProductsQuery';

export const Product = () => {
  const { id } = useParams();
  console.log('id==>', id);
  const { data, isLoading, error } = useBranchProductQuery(id as string, { skip: !id });
  const [updateProduct] = useUpdateProductMutation();
  const [editOpen, setEditOpen] = useState(false);
  console.log('product==>', data);
  if (isLoading) return <PageLoadingState />;
  if (error) return <div>Error loading product</div>;
  if (!data) return <div>Product not found</div>;

  const product = data?.product;

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
        <Button onClick={() => setEditOpen(true)}>Edit Product</Button>
      </div>

      <Card>
        <CardHeader>
          <CardAction>
            <Badge variant={product.status === true ? 'default' : 'secondary'}>
              {product.status}
            </Badge>
          </CardAction>
          <CardTitle>{product.name}</CardTitle>
        </CardHeader>
        <CardContent className='space-y-4'>
          <div className='grid grid-cols-2 gap-4'>

            <div className='flex items-center gap-2'>
              <label className='text-sm font-medium text-gray-500'>SKU</label>
              <p className=''>{product.product.sku}</p>
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
              <p className=''>{product.reorder_level ?? "-"}</p>
            </div>

            <div className='flex items-center gap-2'>
              <label className='text-sm font-medium text-gray-500'>Product Type ID</label>
              <p className=''>{product.product_id ?? "-"}</p>
            </div>
          </div>
          <div className='flex items-center gap-2'>
            <label className='text-sm font-medium text-gray-500'>Description</label>
            <p className=''>{product.description || 'No description'}</p>
          </div>
        </CardContent>
      </Card>

      <EditProduct open={editOpen} onOpenChange={setEditOpen} product={product} updateProduct={updateProduct} />
    </div>
  );
};
