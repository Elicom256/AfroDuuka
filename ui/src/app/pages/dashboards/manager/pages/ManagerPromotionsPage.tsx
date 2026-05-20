import { Gift, CalendarCheck } from 'lucide-react';
import { ManagerPageShell, SectionCard } from './components/manager-page-shell';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { useBranchPromotionsQuery } from '@/app/store/features/branch';
import { resolveList } from './components/manager-page-utils';

export const ManagerPromotionsPage = () => {
  const { data, isLoading } = useBranchPromotionsQuery();
  const promotions = resolveList(data, 'promotions');

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <ManagerPageShell title='Promotions' description='Manage branch marketing and promotional offers.'>
        <div className='grid gap-4 md:grid-cols-3'>
          <SectionCard
            title='Active promotions'
            value={promotions.filter((item: any) => item.active).length}
            icon={<Gift className='h-5 w-5' />}
          />
          <SectionCard title='Total promotions' value={promotions.length} icon={<Gift className='h-5 w-5' />} />
          <SectionCard
            title='Expiring soon'
            value={promotions.filter((item: any) => item.expires_in && item.expires_in <= 7).length}
            icon={<CalendarCheck className='h-5 w-5' />}
          />
        </div>
        <div className='space-y-3'>
          {promotions.length === 0 ? (
            <p className='text-sm text-muted-foreground'>
              No promotional campaigns are configured for this branch yet.
            </p>
          ) : (
            promotions.slice(0, 6).map((promo: any) => (
              <div key={promo.id ?? promo.title} className='rounded-3xl border border-border/70 bg-background p-4'>
                <p className='font-semibold'>{promo.title ?? 'Promotion'}</p>
                <p className='text-sm text-muted-foreground'>
                  {promo.expires_in ? `Expires in ${promo.expires_in} days` : (promo.expires ?? '')}
                </p>
              </div>
            ))
          )}
        </div>
      </ManagerPageShell>
    </div>
  );
};
