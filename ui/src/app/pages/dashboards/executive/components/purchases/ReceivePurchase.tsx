import { useEffect, useState, type SyntheticEvent } from 'react';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { toast } from 'sonner';
import { useCurrency } from '@/app/hooks/useCurrency';
import { useReceivePurchaseMutation } from '@/app/store/features/branch/purchases/purchasesQuery';

interface ReceivePurchaseProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  purchaseId: number;
  items: {
    id: number;
    product_name: string;
    quantity: number;
    cost_price: number;
    selling_price: number | null;
  }[];
  note: string | null;
  onReceived?: () => void;
}

export const ReceivePurchase = ({ open, onOpenChange, purchaseId, items, note, onReceived }: ReceivePurchaseProps) => {
  const { currency } = useCurrency();
  const [receiptItems, setReceiptItems] = useState<
    Record<number, { quantity: string; cost_price: string; selling_price: string }>
  >({});
  const [purchaseNote, setPurchaseNote] = useState(note ?? '');
  const [receivePurchase, { isLoading }] = useReceivePurchaseMutation();

  useEffect(() => {
    if (!open) return;

    setReceiptItems(
      Object.fromEntries(
        items.map((item) => [
          item.id,
          {
            quantity: String(item.quantity),
            cost_price: String(item.cost_price),
            selling_price: item.selling_price == null ? '' : String(item.selling_price),
          },
        ]),
      ),
    );
    setPurchaseNote(note ?? '');
  }, [items, note, open]);

  const handleSubmit = async (e: SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault();

    const payload = items.map((item) => ({
      purchase_item_id: item.id,
      quantity: Number(receiptItems[item.id]?.quantity ?? item.quantity),
      cost_price: Number(receiptItems[item.id]?.cost_price ?? item.cost_price),
      selling_price:
        receiptItems[item.id]?.selling_price === ''
          ? null
          : Number(receiptItems[item.id]?.selling_price ?? item.selling_price),
    }));

    try {
      await receivePurchase({ id: purchaseId, items: payload, note: purchaseNote }).unwrap();
      toast.success('Purchase received — stock updated');
      setReceiptItems({});
      onOpenChange(false);
      onReceived?.();
    } catch (error: any) {
      toast.error(error?.data?.message || 'Failed to receive purchase');
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className='max-h-[85vh] overflow-y-auto sm:max-w-3xl'>
        <DialogHeader>
          <DialogTitle>Receive Purchase</DialogTitle>
          <DialogDescription>
            Confirm what arrived, correct any prices, and update the purchase note before completing it.
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit} className='space-y-5'>
          <div className='space-y-3'>
            <div className='hidden grid-cols-[minmax(8rem,1.4fr)_repeat(3,minmax(5rem,1fr))] gap-3 px-1 text-xs font-medium text-muted-foreground sm:grid'>
              <span>Product</span>
              <span>Received / Ordered</span>
              <span>Cost price</span>
              <span>Selling price</span>
            </div>
            {items.map((item) => (
              <div
                key={item.id}
                className='grid gap-3 rounded-md border p-3 sm:grid-cols-[minmax(8rem,1.4fr)_repeat(3,minmax(5rem,1fr))] sm:items-center'
              >
                <Label className='font-medium'>{item.product_name}</Label>
                <div className='grid grid-cols-2 gap-2'>
                  <Label className='text-xs text-muted-foreground sm:hidden'>Received</Label>
                  <Input
                    aria-label={`${item.product_name} received quantity`}
                    type='number'
                    min={0}
                    max={item.quantity}
                    value={receiptItems[item.id]?.quantity ?? item.quantity}
                    onChange={(e) =>
                      setReceiptItems((prev) => ({
                        ...prev,
                        [item.id]: {
                          ...prev[item.id],
                          quantity: e.target.value,
                          cost_price: prev[item.id]?.cost_price ?? String(item.cost_price),
                          selling_price:
                            prev[item.id]?.selling_price ??
                            (item.selling_price == null ? '' : String(item.selling_price)),
                        },
                      }))
                    }
                    required
                  />
                  <span className='self-center text-sm text-muted-foreground'>/ {item.quantity}</span>
                </div>
                <div>
                  <Label className='text-xs text-muted-foreground sm:hidden'>Cost price ({currency})</Label>
                  <Input
                    aria-label={`${item.product_name} cost price`}
                    type='number'
                    min={0}
                    step='0.01'
                    value={receiptItems[item.id]?.cost_price ?? item.cost_price}
                    onChange={(e) =>
                      setReceiptItems((prev) => ({
                        ...prev,
                        [item.id]: {
                          ...prev[item.id],
                          quantity: prev[item.id]?.quantity ?? String(item.quantity),
                          cost_price: e.target.value,
                          selling_price:
                            prev[item.id]?.selling_price ??
                            (item.selling_price == null ? '' : String(item.selling_price)),
                        },
                      }))
                    }
                    required
                  />
                </div>
                <div>
                  <Label className='text-xs text-muted-foreground sm:hidden'>Selling price ({currency})</Label>
                  <Input
                    aria-label={`${item.product_name} selling price`}
                    type='number'
                    min={0}
                    step='0.01'
                    value={
                      receiptItems[item.id]?.selling_price ?? (item.selling_price == null ? '' : item.selling_price)
                    }
                    onChange={(e) =>
                      setReceiptItems((prev) => ({
                        ...prev,
                        [item.id]: {
                          ...prev[item.id],
                          quantity: prev[item.id]?.quantity ?? String(item.quantity),
                          cost_price: prev[item.id]?.cost_price ?? String(item.cost_price),
                          selling_price: e.target.value,
                        },
                      }))
                    }
                  />
                </div>
              </div>
            ))}
          </div>
          <div className='space-y-2'>
            <Label htmlFor='receipt-note'>Purchase note</Label>
            <Textarea
              id='receipt-note'
              value={purchaseNote}
              maxLength={1000}
              onChange={(e) => setPurchaseNote(e.target.value)}
              placeholder='e.g. Goods received with no balance'
            />
          </div>
          <DialogFooter>
            <Button type='button' variant='outline' onClick={() => onOpenChange(false)}>
              Cancel
            </Button>
            <Button type='submit' disabled={isLoading}>
              {isLoading ? 'Receiving...' : 'Confirm Receipt'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
