import { DollarSign, TrendingUp, Users2 } from 'lucide-react';
import { ManagerPageShell, SectionCard } from './components/manager-page-shell';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { useBranchCustomersQuery } from '@/app/store/features/branch';
import { resolveList } from './components/manager-page-utils';

export const ManagerCustomersPage = () => {
  const { data, isLoading } = useBranchCustomersQuery();
  const customers = resolveList(data, 'customers');

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <ManagerPageShell title='Customers' description='View customers attached to your branch.'>
        <div className='grid gap-4 md:grid-cols-3'>
          <SectionCard title='Total customers' value={customers.length} icon={<Users2 className='h-5 w-5' />} />
          <SectionCard
            title='Recent signups'
            value={customers.filter((customer: any) => customer.is_new).length}
            icon={<TrendingUp className='h-5 w-5' />}
          />
          <SectionCard
            title='Returning buyers'
            value={customers.filter((customer: any) => customer.returning)?.length ?? 0}
            icon={<DollarSign className='h-5 w-5' />}
          />
        </div>
        <div className='space-y-3'>
          {customers.length === 0 ? (
            <p className='text-sm text-muted-foreground'>No customers are available for this branch yet.</p>
          ) : (
            customers.slice(0, 6).map((customer: any) => (
              <div
                key={customer.id ?? customer.email}
                className='rounded-3xl border border-border/70 bg-background p-4'
              >
                <p className='font-semibold'>{customer.name ?? customer.username ?? 'Unknown customer'}</p>
                <p className='text-sm text-muted-foreground'>{customer.phone ?? customer.email ?? 'No contact info'}</p>
              </div>
            ))
          )}
        </div>
      </ManagerPageShell>
    </div>
  );
};
