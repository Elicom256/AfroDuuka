import { Download, Printer } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useReceiptQuery } from '@/app/store/features/branch/receipts/receiptsQuery';
import { ReceiptView } from '@/app/pages/dashboards/executive/components/receipts/ReceiptView';

/**
 * The receipt shown after a till sale.
 *
 * The document is ReceiptView — the same component /dashboard/receipts/:id renders — so
 * a receipt looks the same wherever it appears. This used to be a bespoke block of
 * markup inside PosPage: a different header, a plain list instead of the products table,
 * and only subtotal/tax/total, so the receipt number, cashier, payment method, amount
 * paid and change given were missing at the till and present in the back office.
 *
 * Extracted from PosPage so that promise is testable. While it lived inline, nothing
 * could assert it: a test could render the receipt page and the shared view and still
 * pass with the POS markup long gone.
 *
 * The receipt is fetched from the receipts endpoint by id rather than assembled from the
 * sale the till already has: checkout() loads receipt.items but not receipt.user or
 * receipt.customer, both of which the shared design renders.
 */
export const PosReceiptModal = ({
  receiptId,
  onClose,
}: {
  receiptId?: number | null;
  onClose: () => void;
}) => {
  const { data, isFetching } = useReceiptQuery(receiptId ? String(receiptId) : '', { skip: !receiptId });
  const receipt = data?.receipt || data;

  return (
    <div className='fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4' onClick={onClose}>
      <div className='w-full max-w-3xl max-h-[90vh] overflow-y-auto space-y-4' onClick={(e) => e.stopPropagation()}>
        {receipt?.id ? (
          <ReceiptView receipt={receipt} />
        ) : (
          <div className='rounded-3xl border border-border/70 bg-card p-6'>
            <p className='text-sm text-muted-foreground'>
              {isFetching
                ? 'Loading receipt...'
                : 'Receipt could not be loaded. It is available under Finance -> Receipts.'}
            </p>
          </div>
        )}

        <div className='flex gap-2'>
          <Button variant='default' className='flex-1' onClick={() => window.print()}>
            <Printer className='h-4 w-4 mr-2' /> Print
          </Button>
          <Button variant='outline' className='flex-1' onClick={onClose}>
            <Download className='h-4 w-4 mr-2' /> New Sale
          </Button>
        </div>
      </div>
    </div>
  );
};
