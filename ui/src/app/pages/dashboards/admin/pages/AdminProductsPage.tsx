import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Package2, Tags } from 'lucide-react';
import { Link } from 'react-router-dom';
import { useState } from 'react';
import { AddProduct } from '../components/products/AddProduct';
import { AddProductCategory } from '../components/products/AddProductCategory';
import { ProductTable } from '../components/products/ProductTable';
import { useAddProductMutation } from '@/app/store/features/branch/products/branchProductsQuery';
import { useBranchesQuery } from '@/app/store/features/business/branches/branchesQuery';

export const AdminProductsPage = () => {
  const [addProduct] = useAddProductMutation();
  const { data: branchesData } = useBranchesQuery();
  const [selectedBranchId, setSelectedBranchId] = useState<string>('');
  const branches = branchesData?.branches ?? branchesData ?? [];

  return (
    <div className='space-y-6'>
      <Card className='rounded-3xl border border-border/70 bg-card/80 shadow-sm'>
        <CardHeader className='flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between'>
          <div className='space-y-2'>
            <div className='flex items-center gap-2'>
              <div className='rounded-full bg-primary/10 p-2 text-primary'>
                <Package2 className='h-4 w-4' />
              </div>
              <Badge variant='secondary'>Products</Badge>
            </div>
            <CardTitle className='text-2xl'>Manage inventory products and categories</CardTitle>
            <CardDescription className='max-w-2xl'>
              Create branch products, link them to categories, and keep pricing and stock levels organised in one place.
            </CardDescription>
          </div>
          <div className='flex flex-wrap items-center gap-2'>
            <Select value={selectedBranchId} onValueChange={setSelectedBranchId}>
              <SelectTrigger className='w-48'>
                <SelectValue placeholder='All branches' />
              </SelectTrigger>
              <SelectContent>
                {branches.map((branch: any) => (
                  <SelectItem key={branch.id} value={String(branch.id)}>
                    {branch.name}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <AddProduct addProduct={addProduct} />
            <AddProductCategory />
          </div>
        </CardHeader>
        <CardContent className='flex flex-wrap items-center gap-3 border-t border-border/60 pt-4'>
          <Link
            to='/admin/product-categories'
            className='inline-flex items-center gap-2 text-sm font-medium text-primary hover:underline'
          >
            <Tags className='h-4 w-4' />
            Manage product categories
          </Link>
        </CardContent>
      </Card>

      <ProductTable />
    </div>
  );
};
