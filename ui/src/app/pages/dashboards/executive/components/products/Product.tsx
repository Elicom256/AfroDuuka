import { PageLoadingState } from '@/utils/PageLoadingState';
import { useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { EditProduct } from './EditProduct';
import { PriceHistoryTab } from './PriceHistoryTab';
import { ProductImageManager } from '@/app/components/ProductImageManager';
import { ArrowLeftCircle, Trash2, Info, Clock } from 'lucide-react';
import { useProductQuery, useDeleteProductMutation } from '@/app/store/features/branch/products/branchProductsQuery';
import { toast } from 'sonner';
import { useCurrency } from '@/app/hooks/useCurrency';
import { QueryErrorState } from '@/app/components/QueryErrorState';

export const Product = () => {
  const { currency } = useCurrency();
  const { id } = useParams();
  const { data, isLoading, error, refetch } = useProductQuery(id as string, { skip: !id });
  const [deleteProd, { isLoading: isDeleting }] = useDeleteProductMutation();
  const navigate = useNavigate();

  const [editOpen, setEditOpen] = useState(false);

  if (isLoading) return <PageLoadingState />;
  if (error) {
    return (
      <div className='container mx-auto space-y-4 p-6'>
        <QueryErrorState
          title='Unable to load product'
          description='There was a problem fetching this product from the server.'
          onRetry={refetch}
        />
        <Button variant='ghost' asChild>
          <Link to='../products'>Back to products</Link>
        </Button>
      </div>
    );
  }
  if (!data?.product) {
    return (
      <div className='container mx-auto space-y-4 p-6'>
        <Card>
          <CardHeader>
            <CardTitle>Product not found</CardTitle>
            <CardDescription>This product may have been removed or is unavailable in this branch.</CardDescription>
          </CardHeader>
          <CardFooter>
            <Button variant='outline' asChild>
              <Link to='../products'>Back to products</Link>
            </Button>
          </CardFooter>
        </Card>
      </div>
    );
  }

  const product = data?.product;

  const handleDelete = async (id: string) => {
    try {
      const res = await deleteProd(id).unwrap();
      if (res) {
        toast.success(res.message);
      }
      return navigate('../products');
    } catch {
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
              <h1 className='text-3xl font-bold'>{product.name}</h1>
              <span className='text-muted-foreground'>Product Id: {product?.id}</span>
            </div>

            <div className='flex items-center gap-4'>
              <Button onClick={() => setEditOpen(true)}>Edit Product</Button>
              <ConfirmDeleteButton
                onConfirm={() => handleDelete(product.id)}
                title={`Delete ${product?.name || 'this product'}?`}
                description='It is removed from the catalogue. Sales that included it are not deleted.'
                trigger={
                  <Button variant='ghost' size='icon' aria-label='Delete product'>
                    <Trash2 size={20} className='text-red-400' />
                  </Button>
                }
              />
            </div>
          </div>

          <Tabs defaultValue='details'>
            <TabsList>
              <TabsTrigger value='details' className='flex items-center gap-2'>
                <Info className='h-4 w-4' /> Details
              </TabsTrigger>
              <TabsTrigger value='price-history' className='flex items-center gap-2'>
                <Clock className='h-4 w-4' /> Price History
              </TabsTrigger>
            </TabsList>
            <TabsContent value='details'>
              <div className='grid grid-cols-1 md:grid-cols-3 gap-6'>
                <div className='md:col-span-1'>
                  <ProductImageManager productId={product.id} />
                </div>
                <div className='md:col-span-2'>
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
                          <label className='text-sm font-medium text-gray-500'>Selling Price</label>
                          <p className=''>
                            {currency} {product.selling_price}
                          </p>
                        </div>

                        <div className='flex items-center gap-2'>
                          <label className='text-sm font-medium text-gray-500'>Cost Price</label>
                          <p className=''>
                            {currency} {product.cost_price}
                          </p>
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
                          <label className='text-sm font-medium text-gray-500'>Category</label>
                          <p className=''>{product.product_category_id ?? '-'}</p>
                        </div>
                      </div>
                      <Separator />
                      <div className='flex items-center gap-2'>
                        <label className='text-sm font-medium text-gray-500'>Description</label>
                        <p className=''>{product.description || 'No description'}</p>
                      </div>
                    </CardContent>
                  </Card>
                </div>
              </div>
            </TabsContent>
            <TabsContent value='price-history'>
              <PriceHistoryTab productId={product.id} />
            </TabsContent>
          </Tabs>

          <EditProduct open={editOpen} onOpenChange={setEditOpen} product={product} />
        </div>
      ) : (
        <p>No product found</p>
      )}
    </div>
  );
};
