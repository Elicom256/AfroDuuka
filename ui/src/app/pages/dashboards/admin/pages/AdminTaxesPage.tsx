import {
  useGetAdminTaxePaymentsQuery,
  useGetAdminTaxesQuery,
  useDeleteAdminTaxPaymentMutation,
  useUpdateAdminTaxPaymentMutation,
} from '@/app/store/features/business/admin/taxesQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { TaxPaymentsPanel } from '../components/taxes/TaxPaymentsPanel';
import { SummaryCard } from '../components/taxes/SummaryCard';
import { toast } from 'sonner';

export const AdminTaxesPage = () => {
  const { data: paymentData, isLoading: paymentsLoading } = useGetAdminTaxePaymentsQuery();
  const { data: taxTypesData, isLoading: taxTypesLoading } = useGetAdminTaxesQuery();
  const [updateTaxPayment] = useUpdateAdminTaxPaymentMutation();
  const [deleteTaxPayment] = useDeleteAdminTaxPaymentMutation();

  const isLoading = paymentsLoading || taxTypesLoading;

  if (isLoading) return <PageLoadingState />;

  const totalTax = paymentData?.totalTax ?? 0;
  const outstanding = paymentData?.outstanding ?? 0;
  // const openFilings = paymentData?.openFilings ?? 0;
  const taxes = paymentData?.paidTaxes?.data ?? [];
  const businessTaxes = taxTypesData?.business_taxes?.data ?? [];
  const totalTaxObligations = businessTaxes.reduce((sum: number, tax: any) => sum + Number(tax.amount ?? 0), 0);

  const headers = ['Amount', 'Paid Amount', 'Balance', 'Branch', 'Due Date', 'Payment Date', 'Status'];
  const summaryCards = [
    {
      title: 'Total tax liability',
      description: 'All tax entries for the current business.',
      value: `UGX ${Number(totalTax).toLocaleString()}`,
    },
    {
      title: 'Outstanding tax',
      description: 'Open tax balances awaiting payment.',
      value: `UGX ${Number(outstanding).toLocaleString()}`,
    },
    {
      title: 'Active obligations',
      description: 'Tracked tax types owed by the business.',
      value: businessTaxes.length,
    },
  ];

  const handleUpdateTaxPaymentStatus = async (id: string | number, status: string) => {
    console.log('id==>', status);
    if (!id) return;
    try {
      const res = await updateTaxPayment({ id, body: { status } }).unwrap();
      console.log('updated status==>', res);
      if (res) {
        toast.success(res.message || 'Updated!');
      }
      return res;
    } catch (error) {
      console.error('tax payment status update failed', error);
    }
  };

  const handleDeleteTaxPayment = async (id: string) => {
    try {
      const res = await deleteTaxPayment(id).unwrap();
      console.log('res delete==>', res, 'id==>', id);
      if (res) {
        toast.success(res.message ?? 'deleted!');
      }
    } catch (error) {
      console.error('delete tax payment failed', error);
    }
  };

  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-3xl font-bold tracking-tight'>Tax Management</h1>
        <p className='text-muted-foreground'>
          Track tax obligations, payment statuses, and filings with quick actions.
        </p>
      </div>

      <div className='grid gap-4 md:grid-cols-3'>
        {summaryCards.map((card) => (
          <SummaryCard key={card.title} title={card.title} description={card.description} value={card.value} />
        ))}
      </div>
      {/* lg:grid-cols-[1.4fr_1fr] */}
      <div className='grid gap-6 lg:grid-cols-4'>
        <div className='space-y-6 col-span-3'>
          <TaxPaymentsPanel
            taxes={taxes}
            headers={headers}
            handleUpdateTaxPaymentStatus={handleUpdateTaxPaymentStatus}
            onDeleteTaxPayment={handleDeleteTaxPayment}
          />
        </div>

        <div className='space-y-4'>
          <div className='rounded-3xl border border-border/70 bg-card p-6'>
            <h2 className='text-xl font-semibold'>Business tax obligations</h2>
            <p className='text-sm text-muted-foreground'>
              Types of taxes your business must pay and their current obligations.
            </p>

            <div className='mt-6 grid gap-4'>
              <div className='rounded-3xl border border-border/70 bg-muted p-4'>
                <p className='text-sm uppercase tracking-[0.2em] text-muted-foreground'>Tracked tax types</p>
                <p className='mt-2 text-3xl font-semibold'>{businessTaxes.length}</p>
              </div>
              <div className='rounded-3xl border border-border/70 bg-muted p-4'>
                <p className='text-sm uppercase tracking-[0.2em] text-muted-foreground'>Total obligations</p>
                <p className='mt-2 text-3xl font-semibold'>UGX {Number(totalTaxObligations).toLocaleString()}</p>
              </div>
              <div className='rounded-3xl border border-border/70 bg-muted p-4'>
                <p className='text-sm uppercase tracking-[0.2em] text-muted-foreground'>Latest obligation</p>
                <p className='mt-2 text-lg font-semibold'>{businessTaxes[0]?.name ?? 'No obligations yet'}</p>
              </div>
            </div>
          </div>

          <div className='rounded-3xl border border-border/70 bg-card p-6'>
            <h3 className='text-lg font-semibold'>Recent obligation types</h3>
            <p className='text-sm text-muted-foreground'>
              Quick access to obligated taxes and the obligation dashboard.
            </p>
            <div className='mt-4 space-y-3'>
              {businessTaxes.slice(0, 5).map((tax: any) => (
                <div key={tax.id} className='rounded-2xl border border-border/70 bg-muted p-4'>
                  <p className='font-medium'>{tax.name ?? `Tax ${tax.id}`}</p>
                  <p className='text-sm text-muted-foreground'>{tax.type ?? 'Type unknown'}</p>
                </div>
              ))}
              {businessTaxes.length === 0 && (
                <p className='text-sm text-muted-foreground'>No obligated tax types available.</p>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
