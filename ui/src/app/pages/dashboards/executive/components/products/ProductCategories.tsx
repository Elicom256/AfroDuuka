import {
  useDeleteProductCategoryMutation,
  useProductCategoriesQuery,
} from '@/app/store/features/business/products/productsQuery';
import { ArrowLeftCircle, Search, Trash2, X } from 'lucide-react';
import { Link } from 'react-router-dom';
import { useMemo, useState } from 'react';
import { Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { toast } from 'sonner';
import { AddProductCategory } from './AddProductCategory';
import { EditProductCategory } from './EditProductCategory';
import { QueryErrorState } from '@/app/components/QueryErrorState';
import { ConfirmDeleteButton } from '@/components/ConfirmDeleteButton';

export const ProductCategories = () => {
  const { data, isLoading, isFetching, error, refetch } = useProductCategoriesQuery();
  const [remove, { isLoading: deleting }] = useDeleteProductCategoryMutation();
  const [search, setSearch] = useState('');

  // All hooks run before the isLoading early return below: a conditional return above a
  // hook is what produces "Rendered more hooks than during the previous render" the first
  // time a refetch resolves without the PageLoadingState.
  const categories = useMemo(() => data?.categories || [], [data]);

  // Filters the fetched list in memory, so it is not debounced the way an API-backed
  // search is. Matches on the three fields the card itself renders, so what you can
  // search is exactly what you can see.
  const filtered = useMemo(() => {
    const needle = search.trim().toLowerCase();
    if (!needle) return categories;

    return categories.filter(
      (category: any) =>
        category.name?.toLowerCase().includes(needle) ||
        category.description?.toLowerCase().includes(needle) ||
        String(category.id).includes(needle),
    );
  }, [categories, search]);

  const handleDelete = async (id: number) => {
    try {
      const res = await remove(id).unwrap();
      toast.success(res.message ?? 'Category deleted successfully');
    } catch {
      toast.error('Failed to delete category');
    }
  };

  if (isLoading) return <PageLoadingState />;

  if (error) {
    return (
      <div className='p-6'>
        <QueryErrorState
          title='Unable to load categories'
          description='Product categories could not be retrieved from the server.'
          onRetry={refetch}
          retrying={isFetching}
        />
      </div>
    );
  }

  return (
    <div className='p-6'>
      <div className='mb-4'>
        <Link to='../products' className='flex items-center gap-2 text-blue-400 hover:underline'>
          <ArrowLeftCircle />
          <span>Back to Products</span>
        </Link>
      </div>
      <div className='mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between'>
        <div>
          <h1 className='text-2xl font-bold'>Product Categories</h1>
          <p className='text-muted-foreground'>Manage your product categories</p>
        </div>
        <div className='flex flex-wrap items-center gap-2'>
          <div className='relative'>
            <Search className='absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground' />
            <Input id='search-categories'
              placeholder='Search categories...'
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className='pl-9'
            />
          </div>
          {search && (
            <Button variant='ghost' size='sm' onClick={() => setSearch('')}>
              <X className='h-4 w-4' />
              Clear
            </Button>
          )}
          <AddProductCategory />
        </div>
      </div>
      <div className='grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4'>
        {filtered.map((category: any) => (
          <Card key={category.id} className='hover:shadow-md transition-shadow'>
            <CardHeader>
              <CardAction className='rounded-full bg-white/20 px-2'>ID: {category.id}</CardAction>
              <CardTitle className='text-lg'>{category.name}</CardTitle>
              <CardDescription>{category.description || 'No description'}</CardDescription>
            </CardHeader>
            <CardContent>
              <div className='flex gap-2'>
                <EditProductCategory category={category} />
                <ConfirmDeleteButton
                  onConfirm={() => handleDelete(category.id)}
                  title='Delete this category?'
                  description='Products already in it are not deleted.'
                  trigger={
                    <Button
                                      variant='outline'
                                      size='sm'
                                      className='text-red-400 hover:text-red-600'
                 
                                      disabled={deleting}>
                                      <Trash2 className='w-4 h-4 mr-2' />
                                      Delete
                                    </Button>
                  }
                />
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
      {filtered.length === 0 && (
        <div className='text-center py-8'>
          <p className='text-muted-foreground'>
            {categories.length === 0
              ? 'No categories found. Add some categories to get started.'
              : 'No categories match your search.'}
          </p>
        </div>
      )}
    </div>
  );
};