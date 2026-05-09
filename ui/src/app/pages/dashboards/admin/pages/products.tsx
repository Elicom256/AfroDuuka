import { useAddProductMutation, useProductsQuery } from '@/app/store/features/business/products/productsQuery';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { AddProduct } from '../components/products/AddProduct';
import { AddProductCategory } from '../components/products/AddProductCategory';
import { ProductTable } from '../components/products/ProductTable';
import { PageLoadingState } from '@/utils/PageLoadingState';

export const AdminProductsPage = () => {
  const { data } = useProductsQuery();
  const [addProduct, { isLoading }] = useAddProductMutation();

  if (isLoading) return <PageLoadingState />;
  return (
    <Card className='rounded-3xl border border-border/70 bg-card p-6'>
      <CardHeader>
        <CardTitle>Products</CardTitle>
        <CardDescription>Manage your products here.</CardDescription>
        <div className='flex gap-2'>
          <AddProduct addProduct={addProduct} />
          <AddProductCategory />
        </div>
      </CardHeader>
      <CardContent>
        <ProductTable />
      </CardContent>
    </Card>
  );
};
