import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTaxInvoicesQuery, useSubmitTaxInvoiceToUraMutation, useDeleteTaxInvoiceMutation } from '@/app/store/features/business/admin/taxInvoicesQuery';
import { FileText, Upload, Trash2, CheckCircle, Clock } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { toast } from 'sonner';

export const AdminTaxInvoicesPage = () => {
  const { data, isLoading } = useTaxInvoicesQuery();
  const [submitToUra] = useSubmitTaxInvoiceToUraMutation();
  const [deleteInvoice] = useDeleteTaxInvoiceMutation();
  const invoices = data?.data || [];

  const handleSubmit = async (id: string) => {
    try { await submitToUra(id).unwrap(); toast.success('Submitted to URA'); }
    catch { toast.error('Failed to submit'); }
  };

  const handleDelete = async (id: string) => {
    try { await deleteInvoice(id).unwrap(); toast.success('Invoice deleted'); }
    catch { toast.error('Failed to delete'); }
  };

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-3xl font-bold tracking-tight flex items-center gap-3'>
          <FileText className='h-8 w-8' /> Tax Invoices
        </h1>
        <p className='text-muted-foreground'>URA-compliant e-invoices for sales (EFRIS)</p>
      </div>
      <Card>
        <CardHeader><CardTitle>All Invoices</CardTitle></CardHeader>
        <CardContent>
          {invoices.length === 0 ? (
            <p className='text-muted-foreground text-sm text-center py-8'>No tax invoices yet.</p>
          ) : (
            <div className='space-y-2'>
              {invoices.map((inv: any) => (
                <div key={inv.id} className='flex items-center justify-between p-3 bg-muted/50 rounded-lg'>
                  <div className='flex items-center gap-3'>
                    <span className='font-medium'>{inv.invoice_number}</span>
                    <span className='text-sm text-muted-foreground'>UGX {Number(inv.total_amount).toLocaleString()}</span>
                    {inv.submitted_to_ura ? (
                      <Badge variant='default'><CheckCircle className='h-3 w-3 mr-1' /> Submitted</Badge>
                    ) : (
                      <Badge variant='secondary'><Clock className='h-3 w-3 mr-1' /> Draft</Badge>
                    )}
                  </div>
                  <div className='flex gap-1'>
                    {!inv.submitted_to_ura && (
                      <Button size='sm' variant='outline' onClick={() => handleSubmit(inv.id)}>
                        <Upload className='h-3 w-3 mr-1' /> Submit to URA
                      </Button>
                    )}
                    <Button variant='ghost' size='icon' onClick={() => handleDelete(inv.id)}><Trash2 className='h-4 w-4 text-destructive' /></Button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
};
