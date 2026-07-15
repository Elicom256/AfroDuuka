import { Link, useParams } from 'react-router-dom';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useReceiptQuery, useDownloadReceiptPdfMutation } from '@/app/store/features/branch/receipts/receiptsQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { ArrowLeft, Download, ExternalLink, Receipt as ReceiptIcon } from 'lucide-react';
import { format } from 'date-fns';
import { useCurrency } from '@/app/hooks/useCurrency';

export const ReceiptDetail = () => {
  const { currency } = useCurrency();
  const { id } = useParams<{ id: string }>();
  const { data: receiptData, isLoading } = useReceiptQuery(String(id), { skip: !id });
  const [downloadReceiptPdf, { error, isLoading: downloading }] = useDownloadReceiptPdfMutation();

  if (isLoading) return <PageLoadingState />;

  const receipt = receiptData?.receipt || receiptData;

  if (!receipt) {
    return (
      <div className='flex items-center justify-center h-64'>
        <p className='text-muted-foreground'>Receipt not found</p>
      </div>
    );
  }

  const handleDownloadPdf = async () => {
    try {
      const result = await downloadReceiptPdf(receipt.id).unwrap();
      const byteCharacters = atob(result.pdf);
      const byteNumbers = new Array(byteCharacters.length);
      for (let i = 0; i < byteCharacters.length; i++) {
        byteNumbers[i] = byteCharacters.charCodeAt(i);
      }
      const byteArray = new Uint8Array(byteNumbers);
      const blob = new Blob([byteArray], { type: 'application/pdf' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = result.filename;
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
    } catch (error) {
      console.log('Failed to download receipt==>', error);
    }
  };

  const handleOpenPdf = async () => {
    try {
      const result = await downloadReceiptPdf(receipt.id).unwrap();
      const byteCharacters = atob(result.pdf);
      const byteNumbers = new Array(byteCharacters.length);
      for (let i = 0; i < byteCharacters.length; i++) {
        byteNumbers[i] = byteCharacters.charCodeAt(i);
      }
      const byteArray = new Uint8Array(byteNumbers);
      const blob = new Blob([byteArray], { type: 'application/pdf' });
      const url = URL.createObjectURL(blob);
      window.open(url, '_blank');
    } catch {
      console.error('Failed to open receipt');
    }
  };

  const statusVariant = (s: string) => {
    switch (s) {
      case 'completed':
        return 'success' as const;
      case 'refunded':
        return 'warning' as const;
      case 'voided':
        return 'destructive' as const;
      default:
        return 'secondary' as const;
    }
  };

  return (
    <div className='space-y-6'>
      <div className='flex items-center justify-between'>
        <Link to='../receipts' className='flex items-center gap-2 text-blue-400 hover:underline'>
          <ArrowLeft className='h-4 w-4' />
          <span>Back to Receipts</span>
        </Link>
        <div className='flex items-center gap-2'>
          <Button variant='outline' size='sm' className='gap-2' onClick={handleOpenPdf}>
            <ExternalLink className='h-4 w-4' />
            Open PDF
          </Button>
          <Button size='sm' className='gap-2' onClick={handleDownloadPdf}>
            <Download className='h-4 w-4' />
            Download PDF
          </Button>
        </div>
      </div>

      <Card className='rounded-3xl border border-border/70 bg-card p-6'>
        <CardHeader>
          <div className='flex items-center justify-between'>
            <div className='flex items-center gap-2'>
              <ReceiptIcon className='h-5 w-5' />
              <CardTitle>Receipt {receipt.receipt_number}</CardTitle>
            </div>
            <Badge variant={statusVariant(receipt.status)}>{receipt.status}</Badge>
          </div>
          <CardDescription>{format(new Date(receipt.created_at), 'PPPP p')}</CardDescription>
        </CardHeader>
        <CardContent className='space-y-6'>
          <div className='grid gap-4 md:grid-cols-2'>
            <div>
              <h3 className='font-semibold mb-2'>Cashier</h3>
              <p className='text-sm text-muted-foreground'>
                {receipt.user ? `${receipt.user.firstname ?? ''} ${receipt.user.lastname ?? ''}` : 'N/A'}
              </p>
            </div>
            <div>
              <h3 className='font-semibold mb-2'>Customer</h3>
              <p className='text-sm text-muted-foreground'>
                {receipt.customer
                  ? (receipt.customer.user?.firstname ??
                    receipt.customer.company_name ??
                    `Customer #${receipt.customer.id}`)
                  : 'Walk-in Customer'}
              </p>
            </div>
            <div>
              <h3 className='font-semibold mb-2'>Payment Method</h3>
              <p className='text-sm text-muted-foreground capitalize'>{receipt.payment_method?.replace(/_/g, ' ')}</p>
            </div>
            <div>
              <h3 className='font-semibold mb-2'>Receipt Number</h3>
              <p className='text-sm text-muted-foreground'>{receipt.receipt_number}</p>
            </div>
          </div>

          <Separator />

          <div>
            <h3 className='font-semibold mb-4'>Products</h3>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Product</TableHead>
                  <TableHead>SKU</TableHead>
                  <TableHead>Quantity</TableHead>
                  <TableHead>Unit Price</TableHead>
                  <TableHead>Discount</TableHead>
                  <TableHead className='text-right'>Line Total</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {receipt.items?.map((item: any) => (
                  <TableRow key={item.id}>
                    <TableCell className='font-medium'>{item.product_name}</TableCell>
                    <TableCell>{item.sku || '-'}</TableCell>
                    <TableCell>{item.quantity}</TableCell>
                    <TableCell>
                      {currency} {Number(item.unit_price).toLocaleString()}
                    </TableCell>
                    <TableCell>
                      {item.discount > 0 ? `${currency} ${Number(item.discount).toLocaleString()}` : '-'}
                    </TableCell>
                    <TableCell className='text-right'>
                      {currency} {Number(item.line_total).toLocaleString()}
                    </TableCell>
                  </TableRow>
                )) || (
                  <TableRow>
                    <TableCell colSpan={6} className='text-center text-muted-foreground'>
                      No items found
                    </TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </div>

          <Separator />

          <div className='space-y-2'>
            <div className='flex justify-between text-sm'>
              <span className='text-muted-foreground'>Subtotal</span>
              <span>
                {currency} {Number(receipt.subtotal).toLocaleString()}
              </span>
            </div>
            {Number(receipt.discount) > 0 && (
              <div className='flex justify-between text-sm'>
                <span className='text-muted-foreground'>Discount</span>
                <span className='text-red-500'>
                  -{currency} {Number(receipt.discount).toLocaleString()}
                </span>
              </div>
            )}
            {Number(receipt.tax) > 0 && (
              <div className='flex justify-between text-sm'>
                <span className='text-muted-foreground'>Tax</span>
                <span>
                  {currency} {Number(receipt.tax).toLocaleString()}
                </span>
              </div>
            )}
            <Separator />
            <div className='flex justify-between text-lg font-bold'>
              <span>Grand Total</span>
              <span>
                {currency} {Number(receipt.total).toLocaleString()}
              </span>
            </div>
            <Separator />
            <div className='flex justify-between text-sm'>
              <span className='text-muted-foreground'>Amount Paid</span>
              <span>
                {currency} {Number(receipt.amount_paid).toLocaleString()}
              </span>
            </div>
            {Number(receipt.change_given) > 0 && (
              <div className='flex justify-between text-sm'>
                <span className='text-muted-foreground'>Change Given</span>
                <span>
                  {currency} {Number(receipt.change_given).toLocaleString()}
                </span>
              </div>
            )}
          </div>

          {receipt.notes && (
            <>
              <Separator />
              <div>
                <h3 className='font-semibold mb-1'>Notes</h3>
                <p className='text-sm text-muted-foreground'>{receipt.notes}</p>
              </div>
            </>
          )}
        </CardContent>
      </Card>
    </div>
  );
};
