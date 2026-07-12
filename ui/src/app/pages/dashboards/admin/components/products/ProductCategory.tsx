import { useProductCategoryQuery } from '@/app/store/features/business/products/productsQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { Link, useParams } from 'react-router-dom';
import { Card, CardAction, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { ArrowLeftCircle } from 'lucide-react';

export const ProductCategory = () => {
  const { id } = useParams();
  const { data, isLoading, error } = useProductCategoryQuery(id as string, { skip: !id });

  if (isLoading) return <PageLoadingState />;
  if (error) return <div>Error loading product category</div>;
  if (!data) return <div>Product category not found</div>;

  const category = data?.category ?? data;

  return (
    <div className='container mx-auto p-6'>
      <div className='mb-4'>
        <Link to='../products' className='flex items-center gap-2 text-blue-400 hover:underline'>
          <ArrowLeftCircle />
          <span>Back to Products</span>
        </Link>
      </div>
      <div className='flex flex-col mb-6'>
        <h1 className='text-3xl font-bold'>Product Category Details</h1>
        <span>Category Id: {category.id}</span>
      </div>

      <Card>
        <CardHeader>
          <CardAction>
            <Badge variant={category.status === 'active' || category.status === true ? 'default' : 'secondary'}>
              {category.status === 'active' || category.status === true ? 'Active' : 'Inactive'}
            </Badge>
          </CardAction>
          <CardTitle>{category.name}</CardTitle>
        </CardHeader>
        <CardContent className='space-y-4'>
          <div className='flex items-center gap-2'>
            <label className='text-sm font-medium text-gray-500'>Description</label>
            <p>{category.description || 'No description'}</p>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};
