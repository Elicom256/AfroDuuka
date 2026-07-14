import { PageLoadingState } from '@/utils/PageLoadingState';
import { useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { Card, CardAction, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { EditProduct } from './EditProduct';
import { PriceHistoryTab } from './PriceHistoryTab';
import { ArrowLeftCircle, Trash2, Info, Clock } from 'lucide-react';
import {
  useProductQuery,
  useDeleteProductMutation,
} from '@/app/store/features/branch/products/branchProductsQuery';
import { toast } from 'sonner';
import { useCurrency } from '@/app/hooks/useCurrency';

type TabId = 'details' | 'price-history';

export const Product = () => {
  const { currency } = useCurrency();
  const { id } = useParams();
  const { data, isLoading, error } = useProductQuery(id as string, { skip: !id });
  const [deleteProd, { isLoading: isDeleting }] = useDeleteProductMutation();
  const navigate = useNavigate();

  const [editOpen, setEditOpen] = useState(false);
  const [activeTab, setActiveTab] = useState<TabId>('details');

  if (isLoading) return <PageLoadingState />;
  if (error) return <div>Error loading product</div>;
  if (!data) return <div>Product not found</div>;

  const product = data?.product;

  const handleDelete = async (id: string) => {
    try {
      const res = await deleteProd(id).unwrap();
      if (res) {
        toast.success(res.message);
      }
      return navigate('../products');
    } catch (error) {
      toast.error('Failed to delete product');
    }
  };
  if (isDeleting) {
    return <PageLoadingState />;
  }

  /* ---- Tab definition ---- */
  const tabs: { id: TabId; label: string; icon: React.ReactNode }[] = [
    { id: 'details', label: 'Details', icon: <Info className='h-4 w-4' /> },
    { id: 'price-history', label: 'Price History', icon: <Clock className='h-4 w-4' /> },
  ];

  return (
    <div className=''>
      {product ? (
        <div className='container mx-auto p-6'>
          {/* Back + Header */}
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
              <Trash2
                size={20}
                className='text-red-400 cursor-pointer'
                onClick={() => handleDelete(product.id)}
              />
            </div>
          </div>

          {/* Tab navigation */}
          <div className='flex gap-1 mb-6 border-b'>
            {tabs.map((tab) => (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id)}
                className={`flex items-center gap-2 px-4 py-2 text-sm font-medium transition-colors border-b-2 -mb-px ${
                  activeTab === tab.id
                    ? 'border-blue-500 text-blue-600'
                    : 'border-transparent text-muted-foreground hover:text-foreground'
                }`}
              >
                {tab.icon}
                {tab.label}
              </button>
            ))}
          </div>

          {/* Tab content */}
          {activeTab === 'details' && (
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
                    <p className=''>{currency} {product.price}</p>
                  </div>

                  <div className='flex items-center gap-2'>
                    <label className='text-sm font-medium text-gray-500'>Cost Price</label>
                    <p className=''>{currency} {product.cost_price}</p>
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
          )}

          {activeTab === 'price-history' && <PriceHistoryTab productId={product.id} />}

          <EditProduct open={editOpen} onOpenChange={setEditOpen} product={product} />
        </div>
      ) : (
        <p>No product found</p>
      )}
    </div>
  );
};
