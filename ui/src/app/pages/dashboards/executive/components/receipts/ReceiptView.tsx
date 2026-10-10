import { useEffect, useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Receipt as ReceiptIcon } from 'lucide-react';
import { format } from 'date-fns';
import { useCurrency } from '@/app/hooks/useCurrency';
import * as QRCode from 'qrcode';

export const ReceiptView = ({ receipt }: { receipt: any }) => {
  const { currency } = useCurrency();

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

  // Business identity from the receipt's branch
  const business = receipt.businessBranch?.business;
  const businessName = business?.name ?? '';
  const businessLogo = business?.logo
    ? `${import.meta.env.VITE_BASE_URL}/storage/logo/${business.logo}`
    : null;

  const [qrCodeUrl, setQrCodeUrl] = useState('');

  useEffect(() => {
    const platformUrl = 'https://duukaflow.com';
    QRCode.toDataURL(platformUrl, { width: 150, margin: 1 })
      .then(setQrCodeUrl)
      .catch(() => setQrCodeUrl(''));
  }, []);

  return (
    <Card className='rounded-3xl border border-border/70 bg-card p-6'>
      <CardHeader>
        <div className='flex items-center justify-between'>
          <div className='flex items-center gap-2'>
            <ReceiptIcon className='h-5 w-5' />
            <CardTitle>Receipt {receipt.receipt_number}</CardTitle>
          </div>
          <Badge variant={statusVariant(receipt.status)}>{receipt.status}</Badge>
          {businessName && (
            <div className='text-sm text-muted-foreground'>{businessName}</div>
          )}
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

        <div className='mt-4'>
          <h3 className='font-semibold mb-2'>Business</h3>
          <div className='grid gap-2'>
            {businessLogo && (
              <img
                src={businessLogo}
                alt={businessName}
                className='h-6 w-6 object-cover rounded'
              />
            )}
            {businessName && (
              <span className='text-sm'>{businessName}</span>
            )}
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

        <div>
          <h3 className='font-semibold mb-4'>Totals</h3>
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

        {qrCodeUrl && (
          <>
            <Separator />
            <div className='flex flex-col items-center gap-2'>
              <img src={qrCodeUrl} alt='Platform QR Code' className='h-32 w-32' />
              <p className='text-xs text-muted-foreground'>Scan to visit our platform</p>
            </div>
          </>
        )}
      </CardContent>
    </Card>
  );
};