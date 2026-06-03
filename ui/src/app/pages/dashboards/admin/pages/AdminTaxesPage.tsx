import { useGetAdminTaxesQuery } from '@/app/store/features/business/admin/taxesQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { TaxesPanel } from '../components/taxes/TaxesPanel';

export const AdminTaxesPage = () => {
  const { data, isLoading } = useGetAdminTaxesQuery();

  if (isLoading) return <PageLoadingState />;

  const taxes = Array.isArray(data) ? data : (data?.taxes ?? data?.records ?? []);

  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-3xl font-bold tracking-tight'>Tax Management</h1>
        <p className='text-muted-foreground'>Track tax obligations, payment statuses, and filings.</p>
      </div>
      <TaxesPanel taxes={taxes} />
    </div>
  );
};
