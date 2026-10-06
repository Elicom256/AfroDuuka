import { PageLoadingState } from '@/utils/PageLoadingState';
import { useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { Card, CardAction, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { EditProduct } from './EditProduct';
import { AdjustStock } from './AdjustStock';
import { ProductImageManager } from '@/app/components/ProductImageManager';
import { ArrowLeftCircle } from 'lucide-react';
import { useProductQuery } from '@/app/store/features/branch/products/branchProductsQuery';
import { useCurrency } from '@/app/hooks/useCurrency';
import { useRolePermissions } from '@/lib/useRolePermissions';
import { QueryErrorState } from '@/app/components/QueryErrorState';

export const Product = () => {
  const { currency } = useCurrency();
  const { id } = useParams();
  const { data, isLoading, isFetching, error, refetch } = useProductQuery(id as string, { skip: !id });
  const { canManageCatalog } = useRolePermissions();
  const [editOpen, setEditOpen] = useState(false);
  if (isLoading) return <PageLoadingState />;
  if (error) {
    return (
      <QueryErrorState
        title='Unable to load product'
        description='Product details could not be retrieved.'
        onRetry={refetch}
        retrying={isFetching}
      />
    );
  }
  if (!data?.product) return <div className='p-6 text-muted-foreground'>Product not found.</div>;

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
        <Button onClick={() => setEditOpen(true)}>{canManageCatalog ? 'Edit Product' : 'Adjust Stock'}</Button>
      </div>

      <div className='grid grid-cols-1 md:grid-cols-3 gap-6 mt-3'>
        <div className='md:col-span-1'>
          <ProductImageManager productId={product.id} />
        </div>
        <div className='md:col-span-2'>
          <Card>
            <CardHeader>
              <CardAction>
                <Badge variant={product.status === true ? 'default' : 'secondary'}>{product.status}</Badge>
              </CardAction>
              <CardTitle>{product.name}</CardTitle>
            </CardHeader>
            <CardContent className='space-y-4'>
              <div className='grid grid-cols-2 gap-4'>
                <div className='flex items-center gap-2'>
                  <label className='text-sm font-medium text-muted-foreground'>SKU</label>
                  <p className=''>{product.sku}</p>
                </div>

                <div className='flex items-center gap-2'>
                  <label className='text-sm font-medium text-muted-foreground'>Barcode</label>
                  <p className=''>{product.barcode || 'N/A'}</p>
                </div>

                <div className='flex items-center gap-2'>
                  <label className='text-sm font-medium text-muted-foreground'>Selling Price</label>
                  <p className=''>
                    {currency} {product.selling_price}
                  </p>
                </div>

                <div className='flex items-center gap-2'>
                  <label className='text-sm font-medium text-muted-foreground'>Cost Price</label>
                  <p className=''>
                    {currency} {product.cost_price}
                  </p>
                </div>

                <div className='flex items-center gap-2'>
                  <label className='text-sm font-medium text-muted-foreground'>Quantity</label>
                  <p className=''>{product.quantity}</p>
                </div>

                <div className='flex items-center gap-2'>
                  <label className='text-sm font-medium text-muted-foreground'>Re-order Level</label>
                  <p className=''>{product.reorder_level ?? '-'}</p>
                </div>

                <div className='flex items-center gap-2'>
                  <label className='text-sm font-medium text-muted-foreground'>Product Type ID</label>
                  <p className=''>{product.product_category_id ?? '-'}</p>
                </div>
              </div>
              <div className='flex items-center gap-2'>
                <label className='text-sm font-medium text-muted-foreground'>Description</label>
                <p className=''>{product.description || 'No description'}</p>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      {canManageCatalog ? (
        <EditProduct open={editOpen} onOpenChange={setEditOpen} product={product} />
      ) : (
        <AdjustStock open={editOpen} onOpenChange={setEditOpen} product={product} />
      )}
    </div>
  );
};
