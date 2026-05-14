import { useAddProductMutation } from '@/app/store/features/business/products/productsQuery';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { AddProduct } from '../components/products/AddProduct';
import { ProductTable } from '../components/products/ProductTable';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { Link } from 'react-router-dom';

export const ManagerProductsPage = () => {
  const [addProduct, { isLoading }] = useAddProductMutation();

  if (isLoading) return <PageLoadingState />;
  return (
    <Card className='rounded-3xl border border-border/70 bg-card p-6'>
      <CardHeader>
        <CardTitle>Products</CardTitle>
        <CardDescription>Manage your products here.</CardDescription>
        <div className='flex gap-2'>
          <AddProduct addProduct={addProduct} />
        </div>
      </CardHeader>
      <CardContent>
        <ProductTable />
      </CardContent>
      <CardFooter className='flex justify-between'>
        <Link to='/manager/product-categories' className='hover:underline text-blue-400'>
          Manage product categories
        </Link>
        <div className='grid grid-cols-2 text-xs'>
          <div className='flex items-center gap-2'>
            <span className=''>CID: </span>
            <span className='text-gray-300'>Category Id</span>
          </div>
          <div className='flex items-center gap-2'>
            <span className=''>SKU: </span>
            <span className='text-gray-300'>Stock Keeping Unit</span>
          </div>
          <div className='flex items-center gap-2'>
            <span className=''>CP: </span>
            <span className='text-gray-300'>Cost Price</span>
          </div>
          <div className='flex items-center gap-2'>
            <span className=''>RL: </span>
            <span className='text-gray-300'>Re-order/Restock Level</span>
          </div>
        </div>
      </CardFooter>
    </Card>
  );
};
