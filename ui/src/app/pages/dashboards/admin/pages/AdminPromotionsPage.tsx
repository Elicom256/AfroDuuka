import { useBranchPromotionsQuery } from '@/app/store/features/branch';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { Gift } from 'lucide-react';

export const AdminPromotionsPage = () => {
  const { data, isLoading } = useBranchPromotionsQuery();
  const promotions = data?.data ?? [];

  if (isLoading) return <PageLoadingState />;

  return (
    <Card className='border-border/60'>
      <CardHeader>
        <CardTitle className='text-lg flex items-center gap-2'>
          <Gift className='h-5 w-5' />
          Promotions
        </CardTitle>
      </CardHeader>
      <CardContent>
        {promotions.length === 0 ? (
          <p className='text-sm text-muted-foreground'>No promotions yet.</p>
        ) : (
          <div className='space-y-3'>
            {promotions.map((promo: any) => (
              <div key={promo.id} className='rounded-3xl border border-border/70 bg-muted p-4'>
                <div className='flex items-center justify-between'>
                  <p className='font-semibold'>{promo.title}</p>
                  <Badge variant={promo.status === 'active' ? 'default' : 'secondary'}>{promo.status}</Badge>
                </div>
                <p className='text-sm text-muted-foreground mt-1'>{promo.description}</p>
                <div className='flex gap-4 mt-2 text-xs text-muted-foreground'>
                  <span>{promo.discount_type === 'percentage' ? `${promo.discount_value}%` : `${promo.discount_value}`}</span>
                  <span>{promo.start_date} → {promo.end_date}</span>
                </div>
              </div>
            ))}
          </div>
        )}
      </CardContent>
    </Card>
  );
};
