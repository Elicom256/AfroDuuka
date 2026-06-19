import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { AddProduct } from '../components/products/AddProduct';
import { ProductTable } from '../components/products/ProductTable';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { Link } from 'react-router-dom';
import { useAddBranchProductMutation } from '@/app/store/features/branch/products/branchProductsQuery';

export const AdminProductsPage = () => {
  const [addProduct, { isLoading }] = useAddBranchProductMutation();

  if (isLoading) return <PageLoadingState />;
  return (
    <Card className='rounded-3xl border border-border/70 bg-card p-6'>
      <CardHeader>
        <CardTitle>Products</CardTitle>
        <CardDescription>Manage your products here.</CardDescription>
        <div className='flex gap-2'>
          <AddProduct addProduct={addProduct} />
          {/* <AddProductCategory /> */}
        </div>
      </CardHeader>
      <CardContent>
        <ProductTable />
      </CardContent>
      <CardFooter className='flex justify-between'>
        <div className='flex items-center gap-4'>
          <Link to='/admin/business-products/' className='hover:underline text-blue-400'>
            Manage Products Types
          </Link>
          <Link to='/admin/product-categories' className='hover:underline text-blue-400'>
            Manage product Categories
          </Link>
        </div>
        <div className='grid grid-cols-2 text-xs'>
          {/* <span className='text'>Note</span> */}
          <div className='flex items-center gap-2'>
            <span className=''>CID: </span>
            <span className='text-gray-300'>Category Id</span>
          </div>
          {/* SKU */}
          <div className='flex items-center gap-2'>
            <span className=''>SKU: </span>
            <span className='text-gray-300'>Stock Keeping Unit</span>
          </div>
          {/* cost price */}
          <div className='flex items-center gap-2'>
            <span className=''>CP: </span>
            <span className='text-gray-300'>Cost Price</span>
          </div>
          {/* RL */}
          <div className='flex items-center gap-2'>
            <span className=''>RL: </span>
            <span className='text-gray-300'>Re-order/Restock Level</span>
          </div>

          {/* MP */}
          <div className='flex items-center gap-2'>
            <span className=''>MP</span>
            <span className='text-gray-300'>Markup Percentage</span>
          </div>
        </div>
      </CardFooter>
    </Card>
  );
};
