import { useState, useMemo, type SyntheticEvent } from 'react';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Plus, Search } from 'lucide-react';
import { toast } from 'sonner';
import { usePurchasesQuery } from '@/app/store/features/branch/purchases/purchasesQuery';
import { usePurchaseReturnsQuery } from '@/app/store/features/branch/purchase-returns/purchaseReturnsQuery';
import { useSuppliersQuery } from '@/app/store/features/business/suppliers/supplierQuery';
import { PaginationComponent } from '@/app/utils/Pagination';

interface AddPurchaseReturnProps {
  addPurchaseReturn: any;
}

interface SelectedItem {
  purchase_item_id: number;
  product_name: string;
  purchase_id: number;
  supplier_id: number | null;
  qty_purchased: number;
  qty_already_returned: number;
  qty_to_return: number;
}

export const AddPurchaseReturn = ({ addPurchaseReturn }: AddPurchaseReturnProps) => {
  const [open, setOpen] = useState(false);
  const { data: purchasesData } = usePurchasesQuery();
  const { data: returnsData } = usePurchaseReturnsQuery();
  const { data: sup } = useSuppliersQuery();
  const purchases = purchasesData?.purchases ?? purchasesData ?? [];
  const existingReturns = returnsData?.purchase_returns ?? returnsData ?? [];
  const suppliers = sup?.suppliers || [];

  const [supplierId, setSupplierId] = useState('');
  const [search, setSearch] = useState('');
  const [selectedItems, setSelectedItems] = useState<SelectedItem[]>([]);
  const [reason, setReason] = useState('');
  const [notes, setNotes] = useState('');
  const [restock, setRestock] = useState(false);
  const [page, setPage] = useState(1);
  const perPage = 15;

  const returnedQtyByPurchaseItem = useMemo(() => {
    const map: Record<number, number> = {};
    (existingReturns || []).forEach((pr: any) => {
      (pr.purchase_return_items || []).forEach((item: any) => {
        const id = item.purchase_item_id || item.purchase_item?.id;
        if (id) map[id] = (map[id] || 0) + Number(item.quantity);
      });
    });
    return map;
  }, [existingReturns]);

  const availableItems = useMemo(() => {
    const items: Array<{
      purchase_item_id: number;
      product_name: string;
      purchase_id: number;
      supplier_id: number | null;
      qty_purchased: number;
      qty_already_returned: number;
      available: number;
    }> = [];

    (purchases || []).forEach((purchase: any) => {
      const sid = purchase.supplier_id ?? null;
      (purchase.purchase_items || []).forEach((pi: any) => {
        const qtyReturned = returnedQtyByPurchaseItem[pi.id] || 0;
        const available = Number(pi.quantity) - qtyReturned;
        if (available > 0) {
          items.push({
            purchase_item_id: pi.id,
            product_name: pi.product?.name || `Product #${pi.product_id}`,
            purchase_id: purchase.id,
            supplier_id: sid,
            qty_purchased: Number(pi.quantity),
            qty_already_returned: qtyReturned,
            available,
          });
        }
      });
    });

    return items;
  }, [purchases, returnedQtyByPurchaseItem]);

  const filteredBySupplier = useMemo(() => {
    if (!supplierId) return availableItems;
    return availableItems.filter((item) => String(item.supplier_id) === supplierId);
  }, [availableItems, supplierId]);

  const searched = useMemo(() => {
    if (!search) return filteredBySupplier;
    const q = search.toLowerCase();
    return filteredBySupplier.filter(
      (item) => item.product_name.toLowerCase().includes(q) || String(item.purchase_id).includes(q),
    );
  }, [filteredBySupplier, search]);

  const totalPages = Math.ceil(searched.length / perPage);
  const paginatedItems = searched.slice((page - 1) * perPage, page * perPage);

  const toggleItem = (item: (typeof availableItems)[0]) => {
    setSelectedItems((prev) => {
      const exists = prev.find((s) => s.purchase_item_id === item.purchase_item_id);
      if (exists) return prev.filter((s) => s.purchase_item_id !== item.purchase_item_id);
      return [...prev, { ...item, qty_to_return: 0 }];
    });
  };

  const updateQty = (purchaseItemId: number, qty: number) => {
    setSelectedItems((prev) =>
      prev.map((s) => (s.purchase_item_id === purchaseItemId ? { ...s, qty_to_return: qty } : s)),
    );
  };

  const handleSubmit = async (e: SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault();
    const validItems = selectedItems.filter((s) => s.qty_to_return > 0);
    if (validItems.length === 0) {
      toast.error('Select at least one item with a quantity to return.');
      return;
    }
    for (const item of validItems) {
      const avail = availableItems.find((a) => a.purchase_item_id === item.purchase_item_id);
      if (avail && item.qty_to_return > avail.available) {
        toast.error(`Cannot return more than ${avail.available} of ${item.product_name}.`);
        return;
      }
    }
    try {
      const body = {
        supplier_id: supplierId ? Number(supplierId) : null,
        reason,
        notes,
        restock,
        items: validItems.map((item) => ({
          purchase_item_id: item.purchase_item_id,
          quantity: item.qty_to_return,
        })),
      };
      const res = await addPurchaseReturn(body).unwrap();
      if (res) {
        toast.success(res.message || 'Purchase return processed');
        setOpen(false);
        resetForm();
      }
    } catch (error: any) {
      toast.error(error.data?.message || 'Failed to process purchase return');
    }
  };

  const resetForm = () => {
    setSelectedItems([]);
    setSupplierId('');
    setReason('');
    setNotes('');
    setRestock(false);
    setSearch('');
    setPage(1);
  };

  return (
    <Dialog
      open={open}
      onOpenChange={(o) => {
        setOpen(o);
        if (!o) resetForm();
      }}
    >
      <DialogTrigger asChild>
        <Button>
          <Plus className='h-4 w-4 mr-2' />
          Purchase Return
        </Button>
      </DialogTrigger>
      <DialogContent className='sm:max-w-156.25 max-h-[85vh] overflow-y-auto'>
        <DialogHeader>
          <DialogTitle>Process Purchase Return</DialogTitle>
          <DialogDescription>Filter by supplier then select items to return.</DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit}>
          <div className='grid gap-4 py-4'>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label className='text-right'>Supplier</Label>
              <Select
                value={supplierId}
                onValueChange={(v) => {
                  setSupplierId(v);
                  setPage(1);
                }}
              >
                <SelectTrigger className='col-span-3'>
                  <SelectValue placeholder='All suppliers' />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value='All'>All suppliers</SelectItem>
                  {suppliers.map((s: any) => (
                    <SelectItem key={s.id} value={String(s.id)}>
                      {s.company_name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className='relative'>
              <Search className='absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground' />
              <Input
                placeholder='Search by product name or purchase ID...'
                value={search}
                onChange={(e) => {
                  setSearch(e.target.value);
                  setPage(1);
                }}
                className='pl-9'
              />
            </div>

            <div className='max-h-60 overflow-y-auto border rounded-lg'>
              <Table>
                <TableHeader className='sticky top-0 bg-background'>
                  <TableRow>
                    <TableHead className='w-10'></TableHead>
                    <TableHead>Purchase</TableHead>
                    <TableHead>Product</TableHead>
                    <TableHead>Purchased</TableHead>
                    <TableHead>Returned</TableHead>
                    <TableHead>Available</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {paginatedItems.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={6} className='text-center text-muted-foreground py-4'>
                        No items available for return.
                      </TableCell>
                    </TableRow>
                  ) : (
                    paginatedItems.map((item) => {
                      const isSelected = selectedItems.some((s) => s.purchase_item_id === item.purchase_item_id);
                      return (
                        <TableRow
                          key={item.purchase_item_id}
                          className='cursor-pointer'
                          onClick={() => toggleItem(item)}
                        >
                          <TableCell>
                            <input
                              type='checkbox'
                              checked={isSelected}
                              onChange={() => toggleItem(item)}
                              className='h-4 w-4'
                            />
                          </TableCell>
                          <TableCell>#{item.purchase_id}</TableCell>
                          <TableCell className='font-medium'>{item.product_name}</TableCell>
                          <TableCell>{item.qty_purchased}</TableCell>
                          <TableCell>{item.qty_already_returned}</TableCell>
                          <TableCell>{item.available}</TableCell>
                        </TableRow>
                      );
                    })
                  )}
                </TableBody>
              </Table>
              {totalPages > 1 && (
                <div className='p-2 border-t'>
                  <PaginationComponent currentPage={page} totalPages={totalPages} onPageChange={setPage} />
                </div>
              )}
            </div>

            {selectedItems.length > 0 && (
              <div className='border rounded-lg p-4 space-y-3'>
                <p className='text-sm font-medium'>Selected Items — Set Return Quantities</p>
                {selectedItems.map((item) => {
                  const avail = availableItems.find((a) => a.purchase_item_id === item.purchase_item_id);
                  const maxQty = avail?.available || 0;
                  return (
                    <div key={item.purchase_item_id} className='flex items-center gap-4'>
                      <span className='text-sm flex-1'>
                        {item.product_name} <span className='text-muted-foreground'>(max {maxQty})</span>
                      </span>
                      <Input
                        type='number'
                        min={0}
                        max={maxQty}
                        value={item.qty_to_return || ''}
                        onChange={(e) => {
                          const v = Math.min(Number(e.target.value), maxQty);
                          updateQty(item.purchase_item_id, v);
                        }}
                        className='w-24'
                      />
                    </div>
                  );
                })}
              </div>
            )}

            <div className='flex items-center gap-4'>
              <Label htmlFor='restock'>Reduce Inventory (restock=false)</Label>
              <Switch id='restock' checked={!restock} onCheckedChange={(c) => setRestock(!c)} />
            </div>

            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='reason' className='text-right'>
                Reason
              </Label>
              <Input
                id='reason'
                value={reason}
                onChange={(e) => setReason(e.target.value)}
                className='col-span-3'
                placeholder='e.g. Defective items'
              />
            </div>

            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='notes' className='text-right'>
                Notes
              </Label>
              <Textarea id='notes' value={notes} onChange={(e) => setNotes(e.target.value)} className='col-span-3' />
            </div>
          </div>
          <DialogFooter>
            <Button type='submit'>Process Return</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};
