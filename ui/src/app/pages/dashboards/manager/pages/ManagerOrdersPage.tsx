import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { Package, Eye, Plus, Minus, PlusIcon, X, Truck } from 'lucide-react';
import { useOrdersQuery, useCreateOrderMutation } from '@/app/store/features/orders/ordersQuery';
import { usePurchaseOrdersQuery, useCreatePurchaseOrderMutation } from '@/app/store/features/orders/purchaseOrdersQuery';
import { useProductsQuery } from '@/app/store/features/branch/products/branchProductsQuery';
import { useBranchSuppliersQuery } from '@/app/store/features/branch/suppliers/branchSuppliersQuery';
import { useState } from 'react';
import { toast } from 'sonner';

const statusStyles: Record<string, string> = {
  pending: 'bg-yellow-100 text-yellow-800',
  confirmed: 'bg-blue-100 text-blue-800',
  processing: 'bg-indigo-100 text-indigo-800',
  shipped: 'bg-purple-100 text-purple-800',
  delivered: 'bg-green-100 text-green-800',
  cancelled: 'bg-red-100 text-red-800',
};

interface OrderItemInput {
  product_id: number;
  name: string;
  quantity: number;
  unit_price: number;
}

const SalesOrdersTab = () => {
  const { data, isLoading } = useOrdersQuery();
  const { data: productsData } = useProductsQuery();
  const [createOrder] = useCreateOrderMutation();
  const [selectedOrder, setSelectedOrder] = useState<any>(null);
  const [showCreate, setShowCreate] = useState(false);
  const [orderItems, setOrderItems] = useState<OrderItemInput[]>([]);
  const [notes, setNotes] = useState('');
  const [searchQuery, setSearchQuery] = useState('');

  const orders = data?.data ?? [];
  const products = productsData?.products ?? [];

  const filteredProducts = searchQuery
    ? products.filter((p: any) => p.name.toLowerCase().includes(searchQuery.toLowerCase()) || (p.sku && p.sku.toLowerCase().includes(searchQuery.toLowerCase())))
    : products;

  const addItem = (product: any) => {
    setOrderItems((prev) => {
      const existing = prev.find((i) => i.product_id === product.id);
      if (existing) {
        return prev.map((i) => i.product_id === product.id ? { ...i, quantity: i.quantity + 1 } : i);
      }
      return [...prev, { product_id: product.id, name: product.name, quantity: 1, unit_price: Number(product.price) }];
    });
  };

  const updateItemQty = (productId: number, delta: number) => {
    setOrderItems((prev) =>
      prev.map((i) => i.product_id === productId ? { ...i, quantity: Math.max(1, i.quantity + delta) } : i)
    );
  };

  const removeItem = (productId: number) => {
    setOrderItems((prev) => prev.filter((i) => i.product_id !== productId));
  };

  const totalAmount = orderItems.reduce((sum, i) => sum + i.quantity * i.unit_price, 0);

  const handleCreateOrder = async () => {
    if (orderItems.length === 0) {
      toast.error('Add at least one product');
      return;
    }
    try {
      await createOrder({
        items: orderItems.map((i) => ({ product_id: i.product_id, quantity: i.quantity, unit_price: i.unit_price })),
        notes: notes || undefined,
      }).unwrap();
      toast.success('Sales order created');
      setShowCreate(false);
      setOrderItems([]);
      setNotes('');
    } catch (err: any) {
      toast.error(err?.data?.message || 'Failed to create sales order');
    }
  };

  if (isLoading) return <PageLoadingState />;

  return (
    <Card className='border-border/60'>
      <CardHeader className='flex flex-row items-center justify-between'>
        <CardTitle className='text-lg flex items-center gap-2'>
          <Package className='h-5 w-5' />
          Sales Orders
        </CardTitle>
        <Dialog open={showCreate} onOpenChange={setShowCreate}>
          <DialogTrigger asChild>
            <Button size='sm'>
              <Plus className='h-4 w-4 mr-1' /> Create Sales Order
            </Button>
          </DialogTrigger>
          <DialogContent className='sm:max-w-2xl max-h-screen overflow-y-auto'>
            <DialogHeader><DialogTitle>Create Sales Order</DialogTitle></DialogHeader>
            <div className='space-y-4'>
              <div>
                <Label>Search Products</Label>
                <Input value={searchQuery} onChange={(e) => setSearchQuery(e.target.value)} placeholder='Search by name or SKU...' />
              </div>
              {searchQuery && filteredProducts.length > 0 && (
                <div className='border border-border rounded-xl max-h-40 overflow-y-auto'>
                  {filteredProducts.map((p: any) => (
                    <button
                      key={p.id}
                      type='button'
                      onClick={() => addItem(p)}
                      className='flex items-center justify-between w-full px-3 py-2 text-sm hover:bg-muted border-b border-border last:border-0'
                    >
                      <span>{p.emoji} {p.name}</span>
                      <span className='text-muted-foreground'>{Number(p.price).toLocaleString()}</span>
                    </button>
                  ))}
                </div>
              )}
              {orderItems.length > 0 && (
                <div className='space-y-2'>
                  <Label>Items</Label>
                  {orderItems.map((item) => (
                    <div key={item.product_id} className='flex items-center justify-between gap-2 bg-muted rounded-xl px-3 py-2'>
                      <span className='text-sm flex-1'>{item.name}</span>
                      <div className='flex items-center gap-1'>
                        <Button variant='ghost' size='icon' className='h-7 w-7' onClick={() => updateItemQty(item.product_id, -1)}>
                          <Minus className='h-3 w-3' />
                        </Button>
                        <span className='text-sm font-medium w-6 text-center'>{item.quantity}</span>
                        <Button variant='ghost' size='icon' className='h-7 w-7' onClick={() => updateItemQty(item.product_id, 1)}>
                          <PlusIcon className='h-3 w-3' />
                        </Button>
                      </div>
                      <span className='text-sm w-24 text-right'>{(item.quantity * item.unit_price).toLocaleString()}</span>
                      <Button variant='ghost' size='icon' className='h-7 w-7 text-red-500' onClick={() => removeItem(item.product_id)}>
                        <X className='h-3 w-3' />
                      </Button>
                    </div>
                  ))}
                  <div className='flex justify-between font-bold text-sm border-t border-border pt-2'>
                    <span>Total</span>
                    <span>{totalAmount.toLocaleString()}</span>
                  </div>
                </div>
              )}
              <div>
                <Label>Notes</Label>
                <Textarea value={notes} onChange={(e) => setNotes(e.target.value)} />
              </div>
              <Button className='w-full' onClick={handleCreateOrder} disabled={orderItems.length === 0}>
                Create Sales Order - {totalAmount.toLocaleString()}
              </Button>
            </div>
          </DialogContent>
        </Dialog>
      </CardHeader>
      <CardContent className='p-0'>
        {orders.length === 0 ? (
          <div className='flex flex-col items-center justify-center gap-3 px-6 py-12 text-center'>
            <Package className='h-8 w-8 text-muted-foreground' />
            <p className='font-medium'>No sales orders yet</p>
          </div>
        ) : (
          <div className='overflow-x-auto'>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Order #</TableHead>
                  <TableHead>Customer</TableHead>
                  <TableHead>Items</TableHead>
                  <TableHead>Total</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Date</TableHead>
                  <TableHead className='text-right'>Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {orders.map((order: any) => (
                  <TableRow key={order.id}>
                    <TableCell className='font-mono'>{order.order_number}</TableCell>
                    <TableCell>{order.customer?.name || 'Walk-in'}</TableCell>
                    <TableCell>{order.items?.length || 0}</TableCell>
                    <TableCell>{Number(order.total_amount).toLocaleString()}</TableCell>
                    <TableCell>
                      <Badge className={statusStyles[order.status] || ''}>{order.status}</Badge>
                    </TableCell>
                    <TableCell className='text-sm text-muted-foreground'>
                      {new Date(order.created_at).toLocaleDateString()}
                    </TableCell>
                    <TableCell className='text-right'>
                      <Button variant='ghost' size='sm' onClick={() => setSelectedOrder(order)}>
                        <Eye className='h-4 w-4' />
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
        )}
      </CardContent>

      {selectedOrder && (
        <div className='fixed inset-0 bg-black/50 flex items-center justify-center z-50' onClick={() => setSelectedOrder(null)}>
          <div className='bg-card rounded-3xl border p-6 w-125 max-h-screen overflow-y-auto' onClick={(e) => e.stopPropagation()}>
            <div className='flex justify-between mb-4'>
              <h2 className='text-lg font-semibold'>Order {selectedOrder.order_number}</h2>
              <Button variant='ghost' onClick={() => setSelectedOrder(null)}>Close</Button>
            </div>
            <div className='space-y-1 mb-4'>
              <p className='text-sm'><span className='text-muted-foreground'>Status:</span> {selectedOrder.status}</p>
              <p className='text-sm'><span className='text-muted-foreground'>Customer:</span> {selectedOrder.customer?.name || 'Walk-in'}</p>
              <p className='text-sm'><span className='text-muted-foreground'>Date:</span> {new Date(selectedOrder.created_at).toLocaleString()}</p>
            </div>
            <div className='border-t pt-3'>
              <h3 className='text-sm font-semibold mb-2'>Items</h3>
              {selectedOrder.items?.map((item: any) => (
                <div key={item.id} className='flex justify-between text-sm py-1'>
                  <span>{item.product?.name || `Product #${item.product_id}`} x{item.quantity}</span>
                  <span>{Number(item.subtotal).toLocaleString()}</span>
                </div>
              ))}
              <div className='flex justify-between font-bold border-t pt-2 mt-2'>
                <span>Total</span>
                <span>{Number(selectedOrder.total_amount).toLocaleString()}</span>
              </div>
            </div>
          </div>
        </div>
      )}
    </Card>
  );
};

const PurchaseOrdersTab = () => {
  const { data, isLoading } = usePurchaseOrdersQuery();
  const { data: productsData } = useProductsQuery();
  const { data: suppliersData } = useBranchSuppliersQuery();
  const [createPurchaseOrder] = useCreatePurchaseOrderMutation();
  const [selectedOrder, setSelectedOrder] = useState<any>(null);
  const [showCreate, setShowCreate] = useState(false);
  const [orderItems, setOrderItems] = useState<OrderItemInput[]>([]);
  const [supplierId, setSupplierId] = useState('');
  const [notes, setNotes] = useState('');
  const [searchQuery, setSearchQuery] = useState('');

  const orders = data?.data ?? [];
  const products = productsData?.products ?? [];
  const suppliers = suppliersData?.data ?? [];

  const filteredProducts = searchQuery
    ? products.filter((p: any) => p.name.toLowerCase().includes(searchQuery.toLowerCase()) || (p.sku && p.sku.toLowerCase().includes(searchQuery.toLowerCase())))
    : products;

  const addItem = (product: any) => {
    setOrderItems((prev) => {
      const existing = prev.find((i) => i.product_id === product.id);
      if (existing) {
        return prev.map((i) => i.product_id === product.id ? { ...i, quantity: i.quantity + 1 } : i);
      }
      return [...prev, { product_id: product.id, name: product.name, quantity: 1, unit_price: Number(product.price) }];
    });
  };

  const updateItemQty = (productId: number, delta: number) => {
    setOrderItems((prev) =>
      prev.map((i) => i.product_id === productId ? { ...i, quantity: Math.max(1, i.quantity + delta) } : i)
    );
  };

  const removeItem = (productId: number) => {
    setOrderItems((prev) => prev.filter((i) => i.product_id !== productId));
  };

  const totalAmount = orderItems.reduce((sum, i) => sum + i.quantity * i.unit_price, 0);

  const handleCreateOrder = async () => {
    if (!supplierId) {
      toast.error('Select a supplier');
      return;
    }
    if (orderItems.length === 0) {
      toast.error('Add at least one product');
      return;
    }
    try {
      await createPurchaseOrder({
        supplier_id: Number(supplierId),
        items: orderItems.map((i) => ({ product_id: i.product_id, quantity: i.quantity, unit_price: i.unit_price })),
        notes: notes || undefined,
      }).unwrap();
      toast.success('Purchase order created');
      setShowCreate(false);
      setOrderItems([]);
      setNotes('');
      setSupplierId('');
    } catch (err: any) {
      toast.error(err?.data?.message || 'Failed to create purchase order');
    }
  };

  if (isLoading) return <PageLoadingState />;

  return (
    <Card className='border-border/60'>
      <CardHeader className='flex flex-row items-center justify-between'>
        <CardTitle className='text-lg flex items-center gap-2'>
          <Truck className='h-5 w-5' />
          Purchase Orders
        </CardTitle>
        <Dialog open={showCreate} onOpenChange={setShowCreate}>
          <DialogTrigger asChild>
            <Button size='sm'>
              <Plus className='h-4 w-4 mr-1' /> Create Purchase Order
            </Button>
          </DialogTrigger>
          <DialogContent className='sm:max-w-2xl max-h-screen overflow-y-auto'>
            <DialogHeader><DialogTitle>Create Purchase Order</DialogTitle></DialogHeader>
            <div className='space-y-4'>
              <div>
                <Label>Supplier</Label>
                <Select value={supplierId} onValueChange={setSupplierId}>
                  <SelectTrigger>
                    <SelectValue placeholder='Select a supplier' />
                  </SelectTrigger>
                  <SelectContent>
                    {suppliers.map((s: any) => (
                      <SelectItem key={s.id} value={String(s.id)}>{s.company_name || s.user?.name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              <div>
                <Label>Search Products</Label>
                <Input value={searchQuery} onChange={(e) => setSearchQuery(e.target.value)} placeholder='Search by name or SKU...' />
              </div>
              {searchQuery && filteredProducts.length > 0 && (
                <div className='border border-border rounded-xl max-h-40 overflow-y-auto'>
                  {filteredProducts.map((p: any) => (
                    <button
                      key={p.id}
                      type='button'
                      onClick={() => addItem(p)}
                      className='flex items-center justify-between w-full px-3 py-2 text-sm hover:bg-muted border-b border-border last:border-0'
                    >
                      <span>{p.emoji} {p.name}</span>
                      <span className='text-muted-foreground'>{Number(p.price).toLocaleString()}</span>
                    </button>
                  ))}
                </div>
              )}
              {orderItems.length > 0 && (
                <div className='space-y-2'>
                  <Label>Items</Label>
                  {orderItems.map((item) => (
                    <div key={item.product_id} className='flex items-center justify-between gap-2 bg-muted rounded-xl px-3 py-2'>
                      <span className='text-sm flex-1'>{item.name}</span>
                      <div className='flex items-center gap-1'>
                        <Button variant='ghost' size='icon' className='h-7 w-7' onClick={() => updateItemQty(item.product_id, -1)}>
                          <Minus className='h-3 w-3' />
                        </Button>
                        <span className='text-sm font-medium w-6 text-center'>{item.quantity}</span>
                        <Button variant='ghost' size='icon' className='h-7 w-7' onClick={() => updateItemQty(item.product_id, 1)}>
                          <PlusIcon className='h-3 w-3' />
                        </Button>
                      </div>
                      <span className='text-sm w-24 text-right'>{(item.quantity * item.unit_price).toLocaleString()}</span>
                      <Button variant='ghost' size='icon' className='h-7 w-7 text-red-500' onClick={() => removeItem(item.product_id)}>
                        <X className='h-3 w-3' />
                      </Button>
                    </div>
                  ))}
                  <div className='flex justify-between font-bold text-sm border-t border-border pt-2'>
                    <span>Total</span>
                    <span>{totalAmount.toLocaleString()}</span>
                  </div>
                </div>
              )}
              <div>
                <Label>Notes</Label>
                <Textarea value={notes} onChange={(e) => setNotes(e.target.value)} />
              </div>
              <Button className='w-full' onClick={handleCreateOrder} disabled={orderItems.length === 0 || !supplierId}>
                Create Purchase Order - {totalAmount.toLocaleString()}
              </Button>
            </div>
          </DialogContent>
        </Dialog>
      </CardHeader>
      <CardContent className='p-0'>
        {orders.length === 0 ? (
          <div className='flex flex-col items-center justify-center gap-3 px-6 py-12 text-center'>
            <Truck className='h-8 w-8 text-muted-foreground' />
            <p className='font-medium'>No purchase orders yet</p>
          </div>
        ) : (
          <div className='overflow-x-auto'>
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>PO #</TableHead>
                  <TableHead>Supplier</TableHead>
                  <TableHead>Items</TableHead>
                  <TableHead>Total</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Date</TableHead>
                  <TableHead className='text-right'>Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {orders.map((order: any) => (
                  <TableRow key={order.id}>
                    <TableCell className='font-mono'>{order.order_number}</TableCell>
                    <TableCell>{order.supplier?.company_name || order.supplier?.user?.name || 'N/A'}</TableCell>
                    <TableCell>{order.items?.length || 0}</TableCell>
                    <TableCell>{Number(order.total_amount).toLocaleString()}</TableCell>
                    <TableCell>
                      <Badge className={statusStyles[order.status] || ''}>{order.status}</Badge>
                    </TableCell>
                    <TableCell className='text-sm text-muted-foreground'>
                      {new Date(order.created_at).toLocaleDateString()}
                    </TableCell>
                    <TableCell className='text-right'>
                      <Button variant='ghost' size='sm' onClick={() => setSelectedOrder(order)}>
                        <Eye className='h-4 w-4' />
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
        )}
      </CardContent>

      {selectedOrder && (
        <div className='fixed inset-0 bg-black/50 flex items-center justify-center z-50' onClick={() => setSelectedOrder(null)}>
          <div className='bg-card rounded-3xl border p-6 w-125 max-h-screen overflow-y-auto' onClick={(e) => e.stopPropagation()}>
            <div className='flex justify-between mb-4'>
              <h2 className='text-lg font-semibold'>PO {selectedOrder.order_number}</h2>
              <Button variant='ghost' onClick={() => setSelectedOrder(null)}>Close</Button>
            </div>
            <div className='space-y-1 mb-4'>
              <p className='text-sm'><span className='text-muted-foreground'>Status:</span> {selectedOrder.status}</p>
              <p className='text-sm'><span className='text-muted-foreground'>Supplier:</span> {selectedOrder.supplier?.company_name || selectedOrder.supplier?.user?.name || 'N/A'}</p>
              <p className='text-sm'><span className='text-muted-foreground'>Date:</span> {new Date(selectedOrder.created_at).toLocaleString()}</p>
            </div>
            <div className='border-t pt-3'>
              <h3 className='text-sm font-semibold mb-2'>Items</h3>
              {selectedOrder.items?.map((item: any) => (
                <div key={item.id} className='flex justify-between text-sm py-1'>
                  <span>{item.product?.name || `Product #${item.product_id}`} x{item.quantity}</span>
                  <span>{Number(item.subtotal).toLocaleString()}</span>
                </div>
              ))}
              <div className='flex justify-between font-bold border-t pt-2 mt-2'>
                <span>Total</span>
                <span>{Number(selectedOrder.total_amount).toLocaleString()}</span>
              </div>
            </div>
          </div>
        </div>
      )}
    </Card>
  );
};

export const ManagerOrdersPage = () => {
  return (
    <Tabs defaultValue='sales-orders'>
      <TabsList>
        <TabsTrigger value='sales-orders'>Sales Orders</TabsTrigger>
        <TabsTrigger value='purchase-orders'>Purchase Orders</TabsTrigger>
      </TabsList>
      <TabsContent value='sales-orders'>
        <SalesOrdersTab />
      </TabsContent>
      <TabsContent value='purchase-orders'>
        <PurchaseOrdersTab />
      </TabsContent>
    </Tabs>
  );
};
