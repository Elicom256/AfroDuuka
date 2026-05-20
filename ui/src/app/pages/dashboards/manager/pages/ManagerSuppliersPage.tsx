import { Truck, FileText, Gift } from 'lucide-react';
import { ManagerPageShell, SectionCard } from './components/manager-page-shell';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { useBranchSuppliersQuery } from '@/app/store/features/branch';
import { resolveList } from './components/manager-page-utils';

export const ManagerSuppliersPage = () => {
  const { data, isLoading } = useBranchSuppliersQuery();
  const suppliers = resolveList(data, 'suppliers');

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <ManagerPageShell title='Suppliers' description='Track your branch supplier relationships and contact details.'>
        <div className='grid gap-4 md:grid-cols-3'>
          <SectionCard title='Total suppliers' value={suppliers.length} icon={<Truck className='h-5 w-5' />} />
          <SectionCard
            title='Open orders'
            value={suppliers.filter((supplier: any) => supplier.pending_orders > 0).length}
            icon={<FileText className='h-5 w-5' />}
          />
          <SectionCard
            title='Preferred suppliers'
            value={suppliers.filter((supplier: any) => supplier.is_preferred).length}
            icon={<Gift className='h-5 w-5' />}
          />
        </div>
        <div className='space-y-3'>
          {suppliers.length === 0 ? (
            <p className='text-sm text-muted-foreground'>No suppliers are connected to this branch yet.</p>
          ) : (
            suppliers.slice(0, 6).map((supplier: any) => (
              <div key={supplier.id ?? supplier.name} className='rounded-3xl border border-border/70 bg-background p-4'>
                <p className='font-semibold'>{supplier.name ?? 'Unnamed supplier'}</p>
                <p className='text-sm text-muted-foreground'>
                  {supplier.product_category ?? supplier.product ?? 'No product category'}
                </p>
              </div>
            ))
          )}
        </div>
      </ManagerPageShell>
    </div>
  );
};
