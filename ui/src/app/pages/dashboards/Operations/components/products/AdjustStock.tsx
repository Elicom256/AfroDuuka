import React, { useState } from 'react';
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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { toast } from 'sonner';
import { useUpdateProductMutation } from '@/app/store/features/branch/products/branchProductsQuery';
import { LoadingState } from '@/utils/LoadingState';

// Mirrors ADJUSTMENT_REASONS in api/app/Http/Requests/UpdateProductRequest.php. The API
// rejects anything else, so a reason outside this list is a 422 waiting to happen.
const ADJUSTMENT_REASONS = [
  { value: 'stock_take', label: 'Stock take' },
  { value: 'adjustment', label: 'Adjustment' },
  { value: 'damaged', label: 'Damaged' },
  { value: 'expired', label: 'Expired' },
  { value: 'lost', label: 'Lost' },
  { value: 'other', label: 'Other' },
];

interface AdjustStockProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  product: any;
}

/**
 * Counted-stock entry for roles that may move quantity but not edit the catalogue.
 *
 * The field is the level on the shelf, not a delta, because that is what a count
 * observes. The API diffs it and books the difference as a stock movement. Anything
 * else about the product — name, price, category — is not sent, since the API treats
 * those as prohibited for this role rather than quietly ignoring them.
 */
export const AdjustStock: React.FC<AdjustStockProps> = ({ open, onOpenChange, product }) => {
  const [updateProduct, { isLoading }] = useUpdateProductMutation();
  const [countedQuantity, setCountedQuantity] = useState('');
  const [reason, setReason] = useState('stock_take');
  const [notes, setNotes] = useState('');

  const recordedQuantity = Number(product?.quantity ?? 0);

  React.useEffect(() => {
    if (product) {
      setCountedQuantity(String(product.quantity ?? 0));
      setReason('stock_take');
      setNotes('');
    }
  }, [product]);

  const delta = Number(countedQuantity || 0) - recordedQuantity;

  const handleSubmit = async (e: React.SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault();
    try {
      const res = await updateProduct({
        body: {
          quantity: Number(countedQuantity),
          adjustment_reason: reason,
          ...(notes ? { adjustment_notes: notes } : {}),
        },
        id: product.id,
      }).unwrap();
      toast.success(res?.message || 'Stock adjusted successfully');
      onOpenChange(false);
    } catch (error) {
      toast.error('Failed to adjust stock');
      console.error('Adjustment error:', error);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className='sm:max-w-106.25'>
        <DialogHeader>
          <DialogTitle>Adjust Stock</DialogTitle>
          <DialogDescription>
            Record the counted quantity for {product?.name}. The difference is logged as a stock movement.
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit}>
          <div className='grid gap-4 py-4'>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='recorded_quantity' className='text-right'>
                Recorded
              </Label>
              <p id='recorded_quantity' className='col-span-3 text-sm text-muted-foreground'>
                {recordedQuantity}
              </p>
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='counted_quantity' className='text-right'>
                Counted
              </Label>
              <Input
                id='counted_quantity'
                type='number'
                min={0}
                value={countedQuantity}
                onChange={(e) => setCountedQuantity(e.target.value)}
                className='col-span-3'
                required
              />
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label className='text-right'>Change</Label>
              <p className='col-span-3 text-sm font-medium'>
                {delta > 0 ? `+${delta}` : delta}
                {delta !== 0 ? (
                  <span className='ml-2 text-xs text-muted-foreground'>
                    ({delta > 0 ? 'stock in' : 'stock out'})
                  </span>
                ) : (
                  <span className='ml-2 text-xs text-muted-foreground'>(no change)</span>
                )}
              </p>
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='reason' className='text-right'>Reason</Label>
              <Select value={reason} onValueChange={setReason}>
                <SelectTrigger id='reason' className='col-span-3'>
                  <SelectValue placeholder='Select a reason' />
                </SelectTrigger>
                <SelectContent>
                  {ADJUSTMENT_REASONS.map((item) => (
                    <SelectItem key={item.value} value={item.value}>
                      {item.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='adjustment_notes' className='text-right'>
                Notes
              </Label>
              <Textarea
                id='adjustment_notes'
                value={notes}
                onChange={(e) => setNotes(e.target.value)}
                className='col-span-3'
              />
            </div>
          </div>
          <DialogFooter>
            <Button type='submit' disabled={isLoading}>
              {isLoading ? <LoadingState /> : 'Save Adjustment'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
