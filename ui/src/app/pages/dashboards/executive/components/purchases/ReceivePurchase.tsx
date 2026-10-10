import { useState, type SyntheticEvent } from 'react';
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
import { toast } from 'sonner';
import { useReceivePurchaseMutation } from '@/app/store/features/branch/purchases/purchasesQuery';

interface ReceivePurchaseProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  purchaseId: number;
  items: { id: number; product_name: string; quantity: number }[];
  onReceived?: () => void;
}

export const ReceivePurchase = ({
  open,
  onOpenChange,
  purchaseId,
  items,
  onReceived,
}: ReceivePurchaseProps) => {
  const [receivedQuantities, setReceivedQuantities] = useState<Record<number, string>>({});
  const [receivePurchase, { isLoading }] = useReceivePurchaseMutation();

  const handleSubmit = async (e: SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault();

    const payload = items.map((item) => ({
      purchase_item_id: item.id,
      quantity: Number(receivedQuantities[item.id] ?? item.quantity),
    }));

    try {
      await receivePurchase({ id: purchaseId, items: payload }).unwrap();
      toast.success('Purchase received — stock updated');
      setReceivedQuantities({});
      onOpenChange(false);
      onReceived?.();
    } catch (error: any) {
      toast.error(error?.data?.message || 'Failed to receive purchase');
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className='sm:max-w-lg'>
        <DialogHeader>
          <DialogTitle>Receive Purchase</DialogTitle>
          <DialogDescription>
            Enter the received quantity for each item. Product stock, cost price, and selling price
            will be updated.
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit} className='space-y-4'>
          {items.map((item) => (
            <div key={item.id} className='grid grid-cols-3 items-center gap-4'>
              <Label className='text-sm font-medium'>{item.product_name}</Label>
              <div className='col-span-2 flex items-center gap-2'>
                <Input
                  type='number'
                  min={0}
                  max={item.quantity}
                  value={receivedQuantities[item.id] ?? item.quantity}
                  onChange={(e) =>
                    setReceivedQuantities((prev) => ({ ...prev, [item.id]: e.target.value }))
                  }
                  required
                />
                <span className='text-sm text-muted-foreground'>/ {item.quantity}</span>
              </div>
            </div>
          ))}
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
