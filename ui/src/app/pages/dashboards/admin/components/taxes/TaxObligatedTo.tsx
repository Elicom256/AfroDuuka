import { PageLoadingState } from '@/utils/PageLoadingState';
import { useGetAdminTaxQuery } from '@/app/store/features/business/admin/taxesQuery';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ArrowLeft } from 'lucide-react';
import { useParams, Link } from 'react-router-dom';
import { useCurrency } from '@/app/hooks/useCurrency';

export const TaxObligatedTo = () => {
  const { currency } = useCurrency();
  const { id } = useParams<{ id: string }>();
  const { data, isLoading, error } = useGetAdminTaxQuery(id ?? '', { skip: !id });

  if (isLoading) return <PageLoadingState />;
  if (error) return <div className='p-6 text-sm text-destructive'>Failed to load tax details.</div>;
  if (!data?.business_tax) return <div className='p-6 text-sm text-muted-foreground'>Tax not found.</div>;

  const tax = data.business_tax;
  const rateDisplay = tax.type === 'percentage' ? `${tax.rate}%` : `${currency} ${Number(tax.rate).toLocaleString()}`;
  const createdAt = tax.created_at ? new Date(tax.created_at).toLocaleString() : 'N/A';
  const updatedAt = tax.updated_at ? new Date(tax.updated_at).toLocaleString() : 'N/A';

  return (
    <div className='container mx-auto p-6'>
      <div className='mb-6 flex items-center justify-between gap-4'>
        <div>
          <h1 className='text-3xl font-bold'>Tax obligation details</h1>
          <p className='text-sm text-muted-foreground'>Review the liability for this assigned business tax.</p>
        </div>
        <Link to='/admin/obligated-taxes'>
          <Button variant='outline' size='sm'>
            <ArrowLeft className='mr-2 h-4 w-4' />
            Back to taxes
          </Button>
        </Link>
      </div>

      <Card className='overflow-hidden'>
        <CardHeader>
          <div>
            <CardTitle>{tax.name || 'Business Tax'}</CardTitle>
            <CardDescription>{tax.description || 'No description provided.'}</CardDescription>
          </div>
          <Badge variant={tax.status === 'active' ? 'default' : 'secondary'}>{tax.status}</Badge>
        </CardHeader>

        <CardContent className='space-y-6'>
          <div className='grid gap-4 sm:grid-cols-2'>
            <div className='rounded-xl border border-border/70 bg-muted p-4'>
              <p className='text-sm text-muted-foreground'>Tax Type</p>
              <p className='mt-2 text-lg font-semibold'>{tax.type}</p>
            </div>
            <div className='rounded-xl border border-border/70 bg-muted p-4'>
              <p className='text-sm text-muted-foreground'>Rate</p>
              <p className='mt-2 text-lg font-semibold'>{rateDisplay}</p>
            </div>
            <div className='rounded-xl border border-border/70 bg-muted p-4'>
              <p className='text-sm text-muted-foreground'>Business</p>
              <p className='mt-2 text-lg font-semibold'>{tax.business?.name ?? `Business ${tax.business_id}`}</p>
            </div>
            <div className='rounded-xl border border-border/70 bg-muted p-4'>
              <p className='text-sm text-muted-foreground'>Created</p>
              <p className='mt-2 text-lg font-semibold'>{createdAt}</p>
            </div>
          </div>

          <div className='grid gap-4 sm:grid-cols-2'>
            <div className='rounded-xl border border-border/70 bg-muted p-4'>
              <p className='text-sm text-muted-foreground'>Last updated</p>
              <p className='mt-2 text-lg font-semibold'>{updatedAt}</p>
            </div>
            <div className='rounded-xl border border-border/70 bg-muted p-4'>
              <p className='text-sm text-muted-foreground'>Tax ID</p>
              <p className='mt-2 text-lg font-semibold'>{tax.id}</p>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};
