import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTaxInvoicesQuery, useCreateTaxInvoiceMutation, useSubmitTaxInvoiceToUraMutation, useDeleteTaxInvoiceMutation } from '@/app/store/features/business/admin/taxInvoicesQuery';
import { useSalesQuery } from '@/app/store/features/branch/sales/salesQuery';
import { FileText } from 'lucide-react';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { AddTaxInvoice } from '../components/tax-invoices/AddTaxInvoice';
import { TaxInvoicesTable } from '../components/tax-invoices/TaxInvoicesTable';

export const AdminTaxInvoicesPage = () => {
  const { data, isLoading } = useTaxInvoicesQuery();
  const [createInvoice] = useCreateTaxInvoiceMutation();
  const [submitToUra] = useSubmitTaxInvoiceToUraMutation();
  const [deleteInvoice] = useDeleteTaxInvoiceMutation();
  const { data: salesData } = useSalesQuery();
  const invoices = data?.data || [];
  const sales = salesData?.data || [];

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <div className='flex items-center justify-between'>
        <div>
          <h1 className='text-3xl font-bold tracking-tight flex items-center gap-3'>
            <FileText className='h-8 w-8' /> Tax Invoices
          </h1>
          <p className='text-muted-foreground'>URA-compliant e-invoices for sales (EFRIS)</p>
        </div>
        <AddTaxInvoice createInvoice={createInvoice} sales={sales} />
      </div>
      <Card>
        <CardHeader><CardTitle>All Invoices ({invoices.length})</CardTitle></CardHeader>
        <CardContent>
          {invoices.length === 0 ? (
            <p className='text-muted-foreground text-sm text-center py-8'>No tax invoices yet.</p>
          ) : (
            <TaxInvoicesTable invoices={invoices} onSubmitToUra={submitToUra} onDelete={deleteInvoice} />
          )}
        </CardContent>
      </Card>
    </div>
  );
};
