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
import { useSalesQuery } from '@/app/store/features/branch/sales/salesQuery';
import { useSaleReturnsQuery } from '@/app/store/features/branch/sale-returns/saleReturnsQuery';
import { useBranchCustomersQuery } from '@/app/store/features/branch/customers/branchCustomersQuery';
import { PaginationComponent } from '@/app/utils/Pagination';

interface AddSaleReturnProps {
  addSaleReturn: any;
}

interface SelectedItem {
  sale_item_id: number;
  product_name: string;
  sale_id: number;
  qty_sold: number;
  qty_already_returned: number;
  qty_to_return: number;
}

export const AddSaleReturn = ({ addSaleReturn }: AddSaleReturnProps) => {
  const [open, setOpen] = useState(false);
  const { data: salesData } = useSalesQuery();
  const { data: returnsData } = useSaleReturnsQuery();
  const { data: customersData } = useBranchCustomersQuery();

  const sales = salesData?.sales ?? salesData ?? [];
  const existingReturns = returnsData?.sale_returns ?? returnsData ?? [];
  const customers = customersData?.customers ?? customersData ?? [];

  const [customerId, setCustomerId] = useState('');
  const [search, setSearch] = useState('');
  const [selectedItems, setSelectedItems] = useState<SelectedItem[]>([]);
  const [reason, setReason] = useState('');
  const [notes, setNotes] = useState('');
  const [restock, setRestock] = useState(true);
  const [page, setPage] = useState(1);
  const perPage = 15;

  const returnedQtyBySaleItem = useMemo(() => {
    const map: Record<number, number> = {};
    (existingReturns || []).forEach((sr: any) => {
      (sr.sale_return_items || []).forEach((item: any) => {
        const id = item.sale_item_id || item.sale_item?.id;
        if (id) map[id] = (map[id] || 0) + Number(item.quantity);
      });
    });
    return map;
  }, [existingReturns]);

  const availableItems = useMemo(() => {
    const items: Array<{
      sale_item_id: number;
      product_name: string;
      sale_id: number;
      customer_id: number | null;
      qty_sold: number;
      qty_already_returned: number;
      available: number;
    }> = [];

    (sales || []).forEach((sale: any) => {
      (sale.sale_items || []).forEach((si: any) => {
        const qtyReturned = returnedQtyBySaleItem[si.id] || 0;
        const available = Number(si.quantity) - qtyReturned;
        if (available > 0) {
          items.push({
            sale_item_id: si.id,
            product_name: si.product?.name || `Product #${si.product_id}`,
            sale_id: sale.id,
            customer_id: sale.customer_id ?? null,
            qty_sold: Number(si.quantity),
            qty_already_returned: qtyReturned,
            available,
          });
        }
      });
    });

    return items;
  }, [sales, returnedQtyBySaleItem]);

  const customerLookup = useMemo(() => {
    const map: Record<string, string> = {};
    (customers || []).forEach((c: any) => {
      const name = c.name || c.firstname || c.user?.firstname || `Customer #${c.id}`;
      if (c.user?.lastname) map[String(c.id)] = `${name} ${c.user.lastname}`.trim();
      else map[String(c.id)] = name;
    });
    return map;
  }, [customers]);

  const filteredByCustomer = useMemo(() => {
    if (!customerId) return availableItems;
    return availableItems.filter((item) => String(item.customer_id) === customerId);
  }, [availableItems, customerId]);

  const searched = useMemo(() => {
    if (!search) return filteredByCustomer;
    const q = search.toLowerCase();
    return filteredByCustomer.filter(
      (item) =>
        item.product_name.toLowerCase().includes(q) || String(item.sale_id).includes(q),
    );
  }, [filteredByCustomer, search]);

  const totalPages = Math.ceil(searched.length / perPage);
  const paginatedItems = searched.slice((page - 1) * perPage, page * perPage);

  const uniqueCustomers = useMemo(() => {
    const seen = new Set<number>();
    return availableItems
      .filter((item) => {
        if (item.customer_id === null || seen.has(item.customer_id)) return false;
        seen.add(item.customer_id);
        return true;
      })
      .map((item) => ({ id: item.customer_id, label: customerLookup[String(item.customer_id)] || `Customer #${item.customer_id}` }));
  }, [availableItems, customerLookup]);

  const toggleItem = (item: (typeof availableItems)[0]) => {
    setSelectedItems((prev) => {
      const exists = prev.find((s) => s.sale_item_id === item.sale_item_id);
      if (exists) return prev.filter((s) => s.sale_item_id !== item.sale_item_id);
      return [...prev, { ...item, qty_to_return: 0 }];
    });
  };

  const updateQty = (saleItemId: number, qty: number) => {
    setSelectedItems((prev) => prev.map((s) => (s.sale_item_id === saleItemId ? { ...s, qty_to_return: qty } : s)));
  };

  const handleSubmit = async (e: SyntheticEvent<HTMLFormElement>) => {
    e.preventDefault();
    const validItems = selectedItems.filter((s) => s.qty_to_return > 0);
    if (validItems.length === 0) {
      toast.error('Select at least one item with a quantity to return.');
      return;
    }
    for (const item of validItems) {
      const avail = availableItems.find((a) => a.sale_item_id === item.sale_item_id);
      if (avail && item.qty_to_return > avail.available) {
        toast.error(`Cannot return more than ${avail.available} of ${item.product_name}.`);
        return;
      }
    }
    try {
      const body = {
        reason,
        notes,
        restock,
        items: validItems.map((item) => ({
          sale_item_id: item.sale_item_id,
          quantity: item.qty_to_return,
        })),
      };
      const res = await addSaleReturn(body).unwrap();
      if (res) {
        toast.success(res.message || 'Sale return processed');
        setOpen(false);
        resetForm();
      }
    } catch (error: any) {
      toast.error(error.data?.message || 'Failed to process sale return');
    }
  };

  const resetForm = () => {
    setSelectedItems([]);
    setCustomerId('');
    setReason('');
    setNotes('');
    setRestock(true);
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
          Sale Return
        </Button>
      </DialogTrigger>
      <DialogContent className='sm:max-w-156.25 max-h-[85vh] overflow-y-auto'>
        <DialogHeader>
          <DialogTitle>Process Sale Return</DialogTitle>
          <DialogDescription>
            Filter by customer then select items to return. A single return can span multiple sales.
          </DialogDescription>
        </DialogHeader>
        <form onSubmit={handleSubmit}>
          <div className='grid gap-4 py-4'>
            <div className='grid grid-cols-4 items-center gap-4'>
              <Label className='text-right'>Customer</Label>
              <Select value={customerId} onValueChange={(v) => { setCustomerId(v); setPage(1); }}>
                <SelectTrigger className='col-span-3'>
                  <SelectValue placeholder='All customers' />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value='All'>All customers</SelectItem>
                  {uniqueCustomers.map((c) => (
                    <SelectItem key={c.id} value={String(c.id)}>{c.label}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className='relative'>
              <Search className='absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground' />
              <Input
                placeholder='Search by product name or sale ID...'
                value={search}
                onChange={(e) => { setSearch(e.target.value); setPage(1); }}
                className='pl-9'
              />
            </div>

            <div className='max-h-60 overflow-y-auto border rounded-lg'>
              <Table>
                <TableHeader className='sticky top-0 bg-background'>
                  <TableRow>
                    <TableHead className='w-10'></TableHead>
                    <TableHead>Sale</TableHead>
                    <TableHead>Product</TableHead>
                    <TableHead>Sold</TableHead>
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
                      const isSelected = selectedItems.some((s) => s.sale_item_id === item.sale_item_id);
                      return (
                        <TableRow key={item.sale_item_id} className='cursor-pointer' onClick={() => toggleItem(item)}>
                          <TableCell>
                            <input type='checkbox' checked={isSelected} onChange={() => toggleItem(item)} className='h-4 w-4' />
                          </TableCell>
                          <TableCell>#{item.sale_id}</TableCell>
                          <TableCell className='font-medium'>{item.product_name}</TableCell>
                          <TableCell>{item.qty_sold}</TableCell>
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
                  const avail = availableItems.find((a) => a.sale_item_id === item.sale_item_id);
                  const maxQty = avail?.available || 0;
                  return (
                    <div key={item.sale_item_id} className='flex items-center gap-4'>
                      <span className='text-sm flex-1'>
                        {item.product_name} <span className='text-muted-foreground'>(max {maxQty})</span>
                      </span>
                      <Input
                        type='number' min={0} max={maxQty}
                        value={item.qty_to_return || ''}
                        onChange={(e) => { const v = Math.min(Number(e.target.value), maxQty); updateQty(item.sale_item_id, v); }}
                        className='w-24'
                      />
                    </div>
                  );
                })}
              </div>
            )}

            <div className='flex items-center gap-4'>
              <Label htmlFor='restock'>Restock Inventory</Label>
              <Switch id='restock' checked={restock} onCheckedChange={setRestock} />
            </div>

            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='reason' className='text-right'>Reason</Label>
              <Input id='reason' value={reason} onChange={(e) => setReason(e.target.value)} className='col-span-3' placeholder='e.g. Customer not satisfied' />
            </div>

            <div className='grid grid-cols-4 items-center gap-4'>
              <Label htmlFor='notes' className='text-right'>Notes</Label>
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
