import { useState, useEffect, useCallback, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import { toast } from 'sonner';
import {
  ShoppingCart,
  X,
  Plus,
  Minus,
  Search,
  User,
  CreditCard,
  RotateCcw,
  Printer,
  Download,
  Ban,
  ArrowLeft,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  useLazySearchProductsQuery,
  useLazySearchCustomersQuery,
  useCheckoutMutation,
  useHoldSaleMutation,
  useGetHeldSalesQuery,
  useDeleteHeldSaleMutation,
} from '@/app/store/features/branch/pos/posQuery';
import { useLoggedinUserQuery } from '@/app/store/features/auth/authQuery';

interface CartItem {
  product_id: number;
  name: string;
  sku: string;
  quantity: number;
  unit_price: number;
  discount: number;
  stock: number;
}

interface PaymentInput {
  method: string;
  amount: number;
}

const PAYMENT_METHODS = ['cash', 'mobile_money', 'card', 'credit'];

export const PosPage = () => {
  const navigate = useNavigate();
  const { data: userData } = useLoggedinUserQuery();
  const business = userData?.data?.business;
  const role = userData?.data?.role?.name;

  const [searchQuery, setSearchQuery] = useState('');
  const [cart, setCart] = useState<CartItem[]>([]);
  const [selectedCustomer, setSelectedCustomer] = useState<any>(null);
  const [customerSearch, setCustomerSearch] = useState('');
  const [customerResults, setCustomerResults] = useState<any[]>([]);
  const [showCustomerModal, setShowCustomerModal] = useState(false);
  const [showCheckoutModal, setShowCheckoutModal] = useState(false);
  const [showReceiptModal, setShowReceiptModal] = useState(false);
  const [completedSale, setCompletedSale] = useState<any>(null);
  const [note, setNote] = useState('');
  const [payments, setPayments] = useState<PaymentInput[]>([]);
  const [heldSaleId, setHeldSaleId] = useState<number | null>(null);

  const [triggerSearch, { data: searchResults, isFetching: isSearching }] = useLazySearchProductsQuery();
  const [triggerCustomerSearch] = useLazySearchCustomersQuery();
  const [checkout, { isLoading: isCheckingOut }] = useCheckoutMutation();
  const [holdSale] = useHoldSaleMutation();
  const { data: heldSalesData, refetch: refetchHeld } = useGetHeldSalesQuery();
  const [deleteHeldSale] = useDeleteHeldSaleMutation();

  const searchRef = useRef<HTMLInputElement>(null);
  const debounceRef = useRef<any>(null);

  const heldSales = heldSalesData?.data || [];

  useEffect(() => {
    searchRef.current?.focus();
  }, []);

  const handleSearch = useCallback(
    (q: string) => {
      setSearchQuery(q);
      if (debounceRef.current) clearTimeout(debounceRef.current);
      if (q.length < 1) return;
      debounceRef.current = setTimeout(() => {
        triggerSearch(q);
      }, 300);
    },
    [triggerSearch],
  );

  const handleBarcodeSubmit = useCallback(
    (e: React.KeyboardEvent) => {
      if (e.key === 'Enter' && searchQuery.trim()) {
        e.preventDefault();
        triggerSearch(searchQuery).then((res) => {
          const products = res.data?.data || [];
          if (products.length === 1) {
            addToCart(products[0]);
            setSearchQuery('');
          }
        });
      }
    },
    [searchQuery, triggerSearch],
  );

  const addToCart = useCallback((product: any) => {
    setCart((prev) => {
      const existing = prev.find((c) => c.product_id === product.id);
      if (existing) {
        if (existing.quantity >= product.stock) {
          toast.error(`Only ${product.stock} available`);
          return prev;
        }
        return prev.map((c) => (c.product_id === product.id ? { ...c, quantity: c.quantity + 1 } : c));
      }
      return [
        ...prev,
        {
          product_id: product.id,
          name: product.name,
          sku: product.sku,
          quantity: 1,
          unit_price: product.price,
          discount: 0,
          stock: product.stock,
        },
      ];
    });
    setSearchQuery('');
  }, []);

  const updateQty = (productId: number, delta: number) => {
    setCart((prev) =>
      prev.map((c) => {
        if (c.product_id !== productId) return c;
        const newQty = c.quantity + delta;
        if (newQty < 1) return c;
        if (newQty > c.stock) {
          toast.error(`Only ${c.stock} available`);
          return c;
        }
        return { ...c, quantity: newQty };
      }),
    );
  };

  const removeFromCart = (productId: number) => {
    setCart((prev) => prev.filter((c) => c.product_id !== productId));
  };

  const clearCart = () => {
    setCart([]);
    setSelectedCustomer(null);
    setNote('');
    setHeldSaleId(null);
  };

  const subtotal = cart.reduce((sum, c) => sum + c.quantity * c.unit_price, 0);
  const discountTotal = cart.reduce((sum, c) => sum + c.discount * c.quantity, 0);
  const total = subtotal - discountTotal;

  const handleCustomerSearch = async (q: string) => {
    setCustomerSearch(q);
    if (q.length < 2) {
      setCustomerResults([]);
      return;
    }
    const res = await triggerCustomerSearch(q);
    setCustomerResults(res.data?.data || []);
  };

  const selectCustomer = (customer: any) => {
    setSelectedCustomer(customer);
    setShowCustomerModal(false);
    setCustomerSearch('');
    setCustomerResults([]);
  };

  const handleHoldSale = async () => {
    if (cart.length === 0) {
      toast.error('Cart is empty');
      return;
    }
    try {
      const items = cart.map((c) => ({
        product_id: c.product_id,
        quantity: c.quantity,
        unit_price: c.unit_price,
        discount: c.discount,
      }));
      await holdSale({ items, customer_id: selectedCustomer?.id, notes: note }).unwrap();
      toast.success('Sale held successfully');
      clearCart();
      refetchHeld();
    } catch (err: any) {
      toast.error(err?.data?.message || 'Failed to hold sale');
    }
  };

  const resumeHeldSale = (heldSale: any) => {
    const items: CartItem[] = (heldSale.sale_items || []).map((i: any) => ({
      product_id: i.product_id,
      name: i.product?.name || `Product #${i.product_id}`,
      sku: i.product?.sku || '',
      quantity: i.quantity,
      unit_price: i.unit_price,
      discount: i.discount || 0,
      stock: 9999,
    }));
    setCart(items);
    if (heldSale.customer) setSelectedCustomer(heldSale.customer);
    if (heldSale.note) setNote(heldSale.note);
    setHeldSaleId(heldSale.id);
    toast.success('Held sale restored');
  };

  const handleDeleteHeldSale = async (id: number) => {
    try {
      await deleteHeldSale(id).unwrap();
      toast.success('Held sale deleted');
    } catch {
      toast.error('Failed to delete');
    }
  };

  const handleCheckout = async () => {
    if (cart.length === 0) {
      toast.error('Cart is empty');
      return;
    }
    const totalPayments = payments.reduce((s, p) => s + (p.amount || 0), 0);
    if (totalPayments < total) {
      toast.error(`Total payment (${totalPayments}) is less than total (${total})`);
      return;
    }

    try {
      const items = cart.map((c) => ({
        product_id: c.product_id,
        quantity: c.quantity,
        unit_price: c.unit_price,
        discount: c.discount,
      }));

      const body: any = {
        items,
        payments: payments.map((p) => ({ method: p.method, amount: p.amount })),
        customer_id: selectedCustomer?.id || null,
        note: note || undefined,
      };

      if (heldSaleId) {
        body.sale_id = heldSaleId;
      }

      const res = await checkout(body).unwrap();

      toast.success(res.message || 'Sale completed successfully!');
      setCompletedSale(res.sale);
      setShowCheckoutModal(false);
      setShowReceiptModal(true);
      clearCart();
    } catch (err: any) {
      console.log("error=>", err)
      toast.error(err?.data?.error || err?.data?.message || 'Checkout failed');
    }
  };

  const addPaymentRow = () => {
    setPayments((prev) => [...prev, { method: 'cash', amount: 0 }]);
  };

  const updatePayment = (index: number, field: string, value: any) => {
    setPayments((prev) => prev.map((p, i) => (i === index ? { ...p, [field]: value } : p)));
  };

  const removePayment = (index: number) => {
    if (payments.length <= 1) return;
    setPayments((prev) => prev.filter((_, i) => i !== index));
  };

  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'F2') {
        e.preventDefault();
        searchRef.current?.focus();
      }
      if (e.key === 'F4') {
        e.preventDefault();
        setShowCustomerModal(true);
      }
      if (e.key === 'F8') {
        e.preventDefault();
        handleHoldSale();
      }
      if (e.key === 'F9') {
        e.preventDefault();
        setShowCheckoutModal(true);
      }
      if (e.key === 'Escape') {
        setShowCheckoutModal(false);
        setShowCustomerModal(false);
        setShowReceiptModal(false);
      }
      if (e.ctrlKey && e.key === 'Delete') {
        e.preventDefault();
        clearCart();
      }
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  });

  useEffect(() => {
    if (showCheckoutModal) {
      setPayments([{ method: 'cash', amount: total }]);
    }
  }, [showCheckoutModal, total]);

  const totalPaid = payments.reduce((s, p) => s + (p.amount || 0), 0);
  const changeDue = Math.max(0, totalPaid - total);

  return (
    <div className='h-screen flex flex-col bg-background overflow-hidden'>
      {/* Top bar */}
      <header className='flex items-center justify-between border-b border-border px-6 py-3 bg-card shrink-0'>
        <div className='flex items-center gap-4'>
          <Button variant='ghost' size='icon' onClick={() => navigate(`/${role}`)} className='h-9 w-9 rounded-xl'>
            <ArrowLeft className='h-5 w-5' />
          </Button>
          <div className='h-6 w-px bg-border' />
          <h1 className='text-xl font-bold tracking-tight'>POS</h1>
          <span className='text-xs text-muted-foreground'>{business?.name}</span>
        </div>
        <div className='flex items-center gap-3'>
          <span className='text-xs text-muted-foreground'>F2 Search | F4 Customer | F8 Hold | F9 Pay | Esc Close</span>
        </div>
      </header>

      {/* Main area */}
      <div className='flex flex-1 overflow-hidden'>
        {/* Left panel: search + products + cart */}
        <div className='flex flex-col flex-1 border-r border-border'>
          {/* Search */}
          <div className='p-4 border-b border-border bg-muted/30'>
            <div className='relative'>
              <Search className='absolute left-3 top-2.5 h-4 w-4 text-muted-foreground' />
              <Input
                ref={searchRef}
                value={searchQuery}
                onChange={(e) => handleSearch(e.target.value)}
                onKeyDown={handleBarcodeSubmit}
                placeholder='Search by barcode, SKU, or product name...'
                className='pl-10 h-10 text-base'
              />
            </div>
            {/* Search results */}
            {searchQuery && searchResults?.data && searchResults.data.length > 0 && (
              <div className='mt-2 border border-border rounded-xl bg-card max-h-48 overflow-y-auto'>
                {searchResults.data.map((p: any) => (
                  <button
                    key={p.id}
                    onClick={() => addToCart(p)}
                    className='flex items-center justify-between w-full px-4 py-2.5 text-sm hover:bg-muted transition-colors border-b border-border last:border-0'
                  >
                    <div className='flex flex-col items-start'>
                      <span className='font-medium'>{p.name}</span>
                      <span className='text-xs text-muted-foreground'>
                        SKU: {p.sku} | Stock: {p.stock}
                      </span>
                    </div>
                    <span className='font-semibold text-green-600'>{p.price.toLocaleString()}</span>
                  </button>
                ))}
              </div>
            )}
            {isSearching && <p className='text-xs text-muted-foreground mt-2'>Searching...</p>}
          </div>

          {/* Cart items */}
          <div className='flex-1 overflow-y-auto p-4'>
            {cart.length === 0 ? (
              <div className='flex flex-col items-center justify-center h-full text-muted-foreground'>
                <ShoppingCart className='h-16 w-16 mb-4 opacity-20' />
                <p className='text-lg'>Cart is empty</p>
                <p className='text-sm'>Scan barcode or search products</p>
              </div>
            ) : (
              <div className='space-y-2'>
                {cart.map((item) => (
                  <div
                    key={item.product_id}
                    className='flex items-center justify-between p-3 border border-border rounded-2xl bg-card'
                  >
                    <div className='flex-1 min-w-0'>
                      <p className='font-medium text-sm truncate'>{item.name}</p>
                      <p className='text-xs text-muted-foreground'>SKU: {item.sku}</p>
                      <p className='text-xs text-muted-foreground'>@ {item.unit_price.toLocaleString()}</p>
                    </div>
                    <div className='flex items-center gap-3'>
                      <div className='flex items-center border border-border rounded-xl'>
                        <button
                          onClick={() => updateQty(item.product_id, -1)}
                          className='p-1.5 hover:bg-muted rounded-l-xl'
                        >
                          <Minus className='h-3.5 w-3.5' />
                        </button>
                        <span className='px-3 text-sm font-semibold min-w-6 text-center'>{item.quantity}</span>
                        <button
                          onClick={() => updateQty(item.product_id, 1)}
                          className='p-1.5 hover:bg-muted rounded-r-xl'
                        >
                          <Plus className='h-3.5 w-3.5' />
                        </button>
                      </div>
                      <span className='font-semibold text-sm w-24 text-right'>
                        {(item.quantity * item.unit_price).toLocaleString()}
                      </span>
                      <button
                        onClick={() => removeFromCart(item.product_id)}
                        className='p-1.5 hover:bg-red-50 rounded-xl text-muted-foreground hover:text-red-500'
                      >
                        <X className='h-4 w-4' />
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>

          {/* Cart summary */}
          <div className='border-t border-border p-4 bg-card space-y-2'>
            <div className='flex justify-between text-sm'>
              <span className='text-muted-foreground'>Subtotal</span>
              <span>{subtotal.toLocaleString()}</span>
            </div>
            {discountTotal > 0 && (
              <div className='flex justify-between text-sm text-green-600'>
                <span>Discount</span>
                <span>-{discountTotal.toLocaleString()}</span>
              </div>
            )}
            <div className='flex justify-between text-lg font-bold'>
              <span>Total</span>
              <span>{total.toLocaleString()}</span>
            </div>
          </div>
        </div>

        {/* Right panel: actions */}
        <div className='w-80 flex flex-col bg-card shrink-0'>
          <div className='p-4 border-b border-border space-y-3'>
            <Button
              variant='outline'
              className='w-full justify-start gap-3 h-12 text-base'
              onClick={() => setShowCustomerModal(true)}
            >
              <User className='h-5 w-5' />
              {selectedCustomer
                ? selectedCustomer.name || selectedCustomer.company_name || `#${selectedCustomer.customer_code}`
                : 'Walk-in Customer'}
            </Button>

            <Button
              variant='default'
              size='lg'
              className='w-full h-14 text-lg font-bold gap-3'
              onClick={() => setShowCheckoutModal(true)}
              disabled={cart.length === 0}
            >
              <CreditCard className='h-6 w-6' />
              Checkout (F9)
            </Button>

            <div className='grid grid-cols-2 gap-2'>
              <Button variant='secondary' size='sm' onClick={handleHoldSale} disabled={cart.length === 0}>
                <RotateCcw className='h-4 w-4 mr-1' /> Hold (F8)
              </Button>
              <Button variant='secondary' size='sm' onClick={clearCart} disabled={cart.length === 0}>
                <Ban className='h-4 w-4 mr-1' /> Clear
              </Button>
            </div>
          </div>

          {/* Held sales */}
          <div className='flex-1 overflow-y-auto p-4'>
            <h3 className='text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-3'>Held Sales</h3>
            {heldSales.length === 0 ? (
              <p className='text-xs text-muted-foreground'>No held sales</p>
            ) : (
              <div className='space-y-2'>
                {heldSales.map((hs: any) => (
                  <div key={hs.id} className='p-3 border border-border rounded-2xl text-sm'>
                    <div className='flex items-center justify-between mb-1'>
                      <span className='font-medium'>Sale #{hs.id}</span>
                      <span className='text-xs text-muted-foreground'>
                        {new Date(hs.created_at).toLocaleTimeString()}
                      </span>
                    </div>
                    <p className='text-xs text-muted-foreground'>
                      {hs.sale_items?.length || 0} item(s)
                      {hs.customer?.name ? ` - ${hs.customer.name}` : ''}
                    </p>
                    <div className='flex gap-2 mt-2'>
                      <Button size='sm' variant='outline' className='text-xs h-7' onClick={() => resumeHeldSale(hs)}>
                        Resume
                      </Button>
                      <Button
                        size='sm'
                        variant='outline'
                        className='text-xs h-7 text-red-500'
                        onClick={() => handleDeleteHeldSale(hs.id)}
                      >
                        Delete
                      </Button>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      </div>

      {/* Customer modal */}
      {showCustomerModal && (
        <div
          className='fixed inset-0 bg-black/50 flex items-center justify-center z-50'
          onClick={() => setShowCustomerModal(false)}
        >
          <div
            className='bg-card rounded-3xl border border-border p-6 w-100 max-h-[80vh] overflow-y-auto'
            onClick={(e) => e.stopPropagation()}
          >
            <div className='flex items-center justify-between mb-4'>
              <h2 className='text-lg font-semibold'>Select Customer</h2>
              <Button variant='ghost' size='sm' onClick={() => setShowCustomerModal(false)}>
                <X className='h-4 w-4' />
              </Button>
            </div>
            <Input
              value={customerSearch}
              onChange={(e) => handleCustomerSearch(e.target.value)}
              placeholder='Search by name, phone, or code...'
              className='mb-3'
              autoFocus
            />
            <Button variant='outline' className='w-full mb-3 justify-start' onClick={() => selectCustomer(null)}>
              <User className='h-4 w-4 mr-2' /> Walk-in Customer
            </Button>
            <div className='space-y-1'>
              {customerResults.map((c: any) => (
                <button
                  key={c.id}
                  onClick={() => selectCustomer(c)}
                  className='w-full text-left p-3 hover:bg-muted rounded-xl text-sm'
                >
                  <p className='font-medium'>{c.name || c.company_name || 'Unknown'}</p>
                  <p className='text-xs text-muted-foreground'>
                    {c.phone} | {c.customer_code}
                  </p>
                </button>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* Checkout modal */}
      {showCheckoutModal && (
        <div
          className='fixed inset-0 bg-black/50 flex items-center justify-center z-50'
          onClick={() => setShowCheckoutModal(false)}
        >
          <div
            className='bg-card rounded-3xl border border-border p-6 w-125 max-h-[90vh] overflow-y-auto'
            onClick={(e) => e.stopPropagation()}
          >
            <div className='flex items-center justify-between mb-4'>
              <h2 className='text-lg font-semibold'>Checkout</h2>
              <Button variant='ghost' size='sm' onClick={() => setShowCheckoutModal(false)}>
                <X className='h-4 w-4' />
              </Button>
            </div>

            {/* Customer */}
            <div className='mb-4 p-3 bg-muted rounded-2xl'>
              <p className='text-xs text-muted-foreground'>Customer</p>
              <p className='font-medium'>
                {selectedCustomer
                  ? selectedCustomer.name || selectedCustomer.company_name || `#${selectedCustomer.customer_code}`
                  : 'Walk-in Customer'}
              </p>
            </div>

            {/* Order summary */}
            <div className='mb-4 space-y-1 max-h-40 overflow-y-auto'>
              {cart.map((item) => (
                <div key={item.product_id} className='flex justify-between text-sm'>
                  <span>
                    {item.name} x{item.quantity}
                  </span>
                  <span>{(item.quantity * item.unit_price).toLocaleString()}</span>
                </div>
              ))}
            </div>

            <div className='border-t border-border pt-3 mb-4 space-y-1'>
              <div className='flex justify-between text-sm'>
                <span>Subtotal</span>
                <span>{subtotal.toLocaleString()}</span>
              </div>
              {discountTotal > 0 && (
                <div className='flex justify-between text-sm text-green-600'>
                  <span>Discount</span>
                  <span>-{discountTotal.toLocaleString()}</span>
                </div>
              )}
              <div className='flex justify-between text-lg font-bold'>
                <span>Total</span>
                <span>{total.toLocaleString()}</span>
              </div>
            </div>

            {/* Payments */}
            <div className='space-y-3 mb-4'>
              <h3 className='text-sm font-semibold'>Payments</h3>
              {payments.map((payment, i) => (
                <div key={i} className='flex gap-2 items-start'>
                  <select
                    value={payment.method}
                    onChange={(e) => updatePayment(i, 'method', e.target.value)}
                    className='flex h-10 rounded-2xl border border-input bg-background px-3 py-2 text-sm'
                  >
                    {PAYMENT_METHODS.map((m) => (
                      <option key={m} value={m}>
                        {m.replace('_', ' ').replace(/\b\w/g, (l) => l.toUpperCase())}
                      </option>
                    ))}
                  </select>
                  <Input
                    type='number'
                    value={payment.amount || ''}
                    onChange={(e) => updatePayment(i, 'amount', parseFloat(e.target.value) || 0)}
                    placeholder='Amount'
                    className='flex-1'
                  />
                  {payments.length > 1 && (
                    <Button variant='ghost' size='sm' onClick={() => removePayment(i)}>
                      <X className='h-4 w-4' />
                    </Button>
                  )}
                </div>
              ))}
              <Button variant='outline' size='sm' onClick={addPaymentRow} className='w-full'>
                + Add Payment Method
              </Button>
            </div>

            {totalPaid >= total && (
              <div className='flex justify-between text-sm text-green-600 mb-4'>
                <span>Change Due</span>
                <span className='font-bold'>{changeDue.toLocaleString()}</span>
              </div>
            )}

            <Input
              value={note}
              onChange={(e) => setNote(e.target.value)}
              placeholder='Note (optional)'
              className='mb-4'
            />

            <Button
              className='w-full h-12 text-lg font-bold'
              onClick={handleCheckout}
              disabled={isCheckingOut || totalPaid < total}
            >
              {isCheckingOut ? 'Processing...' : `Confirm Sale - ${total.toLocaleString()}`}
            </Button>
          </div>
        </div>
      )}

      {/* Receipt modal */}
      {showReceiptModal && completedSale && (
        <div
          className='fixed inset-0 bg-black/50 flex items-center justify-center z-50'
          onClick={() => setShowReceiptModal(false)}
        >
          <div
            className='bg-card rounded-3xl border border-border p-6 w-105 max-h-[90vh] overflow-y-auto'
            onClick={(e) => e.stopPropagation()}
          >
            <div className='text-center mb-6'>
              <h2 className='text-xl font-bold'>{business?.name || 'Store'}</h2>
              <p className='text-sm text-muted-foreground'>Receipt</p>
            </div>

            <div className='text-sm space-y-1 mb-4'>
              <p>
                <span className='text-muted-foreground'>Invoice:</span> {completedSale.id}
              </p>
              <p>
                <span className='text-muted-foreground'>Date:</span> {new Date().toLocaleString()}
              </p>
              <p>
                <span className='text-muted-foreground'>Customer:</span> {selectedCustomer?.name || 'Walk-in'}
              </p>
            </div>

            <div className='border-t border-border pt-3 mb-4'>
              {completedSale.sale_items?.map((item: any) => (
                <div key={item.id} className='flex justify-between text-sm py-1'>
                  <span>
                    {item.product?.name || `Product #${item.product_id}`} x{item.quantity}
                  </span>
                  <span>{item.subtotal.toLocaleString()}</span>
                </div>
              ))}
            </div>

            <div className='border-t border-border pt-3 mb-6 space-y-1'>
              <div className='flex justify-between text-sm'>
                <span>Total</span>
                <span className='font-bold'>{(completedSale.total_amount || 0).toLocaleString()}</span>
              </div>
            </div>

            <div className='flex gap-2'>
              <Button variant='default' className='flex-1' onClick={() => window.print()}>
                <Printer className='h-4 w-4 mr-2' /> Print
              </Button>
              <Button variant='outline' className='flex-1' onClick={() => setShowReceiptModal(false)}>
                <Download className='h-4 w-4 mr-2' /> New Sale
              </Button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
