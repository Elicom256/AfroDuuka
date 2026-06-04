import { useGetAdminTaxePaymentsQuery, useGetAdminTaxesQuery } from '@/app/store/features/business/admin/taxesQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { TaxesPanel } from '../components/taxes/TaxesPanel';
import { SummaryCard } from '../components/taxes/SummaryCard';

export const AdminTaxesPage = () => {
  // const { data, isLoading } = useGetAdminTaxesQuery();
  const { data, isLoading } = useGetAdminTaxePaymentsQuery();

  if (isLoading) return <PageLoadingState />;
  const totalTax = data?.totalTax;
  const outstanding = data?.outstanding;
  const taxes = data?.paidTaxes.data ?? [];
  const openFilings = 0;
  // console.log('taxes=>', data);
  const headers = ['Amount', 'Paid Amount', 'Balance', 'Branch', 'Due Date', 'Payment Date', 'Status'];
  const summaryCards = [
    {
      title: 'Total tax liability',
      description: 'All tax entries for the current business.',
      value: totalTax.toLocaleString(),
    },
    {
      title: 'Outstanding tax',
      description: 'Open tax balances waiting to be paid.',
      value: outstanding.toLocaleString(),
    },
    {
      title: 'Open filings',
      description: 'Pending and paid tax items.',
      value: openFilings ?? 0,
    },
  ];

  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-3xl font-bold tracking-tight'>Tax Management</h1>
        <p className='text-muted-foreground'>Track tax obligations, payment statuses, and filings.</p>
      </div>
      <div className='grid gap-4 md:grid-cols-3'>
        {summaryCards.map((card) => (
          <SummaryCard key={card.title} title={card.title} description={card.description} value={card.value} />
        ))}
      </div>
        <TaxesPanel
          taxes={taxes}
          headers={headers}
          totalTax={totalTax}
          outstanding={outstanding}
          openFilings={openFilings}
        />
    </div>
  );
};
