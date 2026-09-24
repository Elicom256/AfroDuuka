import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { FileText, Eye, Plus, X, Minus, PlusIcon, Send, CheckCircle2, Ban, Download } from 'lucide-react';
import {
  useQuotationsQuery,
  useCreateQuotationMutation,
  useSendQuotationMutation,
  useAcceptQuotationMutation,
  useCancelQuotationMutation,
  useQuotationPdfQuery,
} from '@/app/store/features/orders/quotationsQuery';
import { useProductsQuery } from '@/app/store/features/branch/products/branchProductsQuery';
import { useState } from 'react';
import { toast } from 'sonner';

const statusStyles: Record<string, string> = {
  draft: 'bg-gray-100 text-gray-800',
  sent: 'bg-blue-100 text-blue-800',
  accepted: 'bg-green-100 text-green-800',
  expired: 'bg-orange-100 text-orange-800',
  cancelled: 'bg-red-100 text-red-800',
};

interface QuoteItemInput {
  product_id: number;
  name: string;
  quantity: number;
  unit_price: number;
}

const QuoteDetailModal = ({ quotation, onClose }: { quotation: any; onClose: () => void }) => {
  const { data: pdfData } = useQuotationPdfQuery(String(quotation.id));

  const downloadPdf = () => {
    if (!pdfData?.pdf) {
      toast.error('PDF not ready');
      return;
    }
    const link = document.createElement('a');
    link.href = `data:application/pdf;base64,${pdfData.pdf}`;
    link.download = pdfData.filename || `quotation-${quotation.quotation_number}.pdf`;
    link.click();
  };

  return (
    <div className='fixed inset-0 bg-black/50 flex items-center justify-center z-50' onClick={onClose}>
      <div
        className='bg-card rounded-3xl border border-border p-6 w-125 max-h-[80vh] overflow-y-auto'
        onClick={(e) => e.stopPropagation()}
      >
        <div className='flex items-center justify-between mb-4'>
          <h2 className='text-lg font-semibold'>Quote {quotation.quotation_number}</h2>
          <Button variant='ghost' size='sm' onClick={onClose}>
            Close
          </Button>
        </div>
        <div className='space-y-2 mb-4'>
          <p className='text-sm'>
            <span className='text-muted-foreground'>Status:</span> {quotation.status}
          </p>
          <p className='text-sm'>
            <span className='text-muted-foreground'>Customer:</span> {quotation.customer?.name || 'Walk-in'}
          </p>
          <p className='text-sm'>
            <span className='text-muted-foreground'>Valid until:</span>{' '}
            {quotation.valid_until ? new Date(quotation.valid_until).toLocaleDateString() : '-'}
          </p>
          <p className='text-sm'>
            <span className='text-muted-foreground'>Date:</span> {new Date(quotation.created_at).toLocaleString()}
          </p>
          {quotation.notes && (
            <p className='text-sm'>
              <span className='text-muted-foreground'>Notes:</span> {quotation.notes}
            </p>
          )}
        </div>
        <div className='border-t border-border pt-3'>
          <h3 className='text-sm font-semibold mb-2'>Items</h3>
          <div className='space-y-2'>
            {quotation.items?.map((item: any) => (
              <div key={item.id} className='flex justify-between text-sm'>
                <span>
                  {item.product_name || item.product?.name || `Product #${item.product_id}`} x{item.quantity}
                </span>
                <span>{Number(item.subtotal).toLocaleString()}</span>
              </div>
            ))}
          </div>
          <div className='flex justify-between text-sm border-t border-border pt-2 mt-2'>
            <span>Subtotal</span>
            <span>{Number(quotation.subtotal).toLocaleString()}</span>
          </div>
          {Number(quotation.discount) > 0 && (
            <div className='flex justify-between text-sm'>
              <span>Discount</span>
              <span>-{Number(quotation.discount).toLocaleString()}</span>
            </div>
          )}
          {Number(quotation.tax_amount) > 0 && (
            <div className='flex justify-between text-sm'>
              <span>Tax</span>
              <span>{Number(quotation.tax_amount).toLocaleString()}</span>
            </div>
          )}
          <div className='flex justify-between font-bold text-lg border-t border-border pt-2 mt-1'>
            <span>Total</span>
            <span>{Number(quotation.total_amount).toLocaleString()}</span>
          </div>
        </div>
        <div className='flex flex-wrap gap-2 mt-4'>
          <Button size='sm' onClick={downloadPdf} disabled={!pdfData?.pdf}>
            <Download className='h-4 w-4 mr-1' /> PDF
          </Button>
          <QuoteActions quotation={quotation} onDone={onClose} />
        </div>
      </div>
    </div>
  );
};

const QuotationsTable = ({ status }: { status: string }) => {
  const { data, isLoading } = useQuotationsQuery(status ? { status } : undefined);
  const [selected, setSelected] = useState<any>(null);

  const quotations = data?.quotations?.data ?? [];

  if (isLoading) return <PageLoadingState />;

  return (
    <>
      <Card className='border-border/60'>
        <CardContent className='p-0'>
          {quotations.length === 0 ? (
            <div className='flex flex-col items-center justify-center gap-3 px-6 py-12 text-center'>
              <div className='rounded-full bg-muted p-3'>
                <FileText className='h-5 w-5 text-muted-foreground' />
              </div>
              <p className='font-medium'>No quotations here</p>
              <p className='text-sm text-muted-foreground'>Quotations will appear here once created.</p>
            </div>
          ) : (
            <div className='overflow-x-auto'>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Quote #</TableHead>
                    <TableHead>Customer</TableHead>
                    <TableHead>Items</TableHead>
                    <TableHead>Total</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Valid Until</TableHead>
                    <TableHead>Date</TableHead>
                    <TableHead className='text-right'>Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {quotations.map((q: any) => (
                    <TableRow key={q.id}>
                      <TableCell className='font-mono font-medium'>{q.quotation_number}</TableCell>
                      <TableCell>{q.customer?.name || 'Walk-in'}</TableCell>
                      <TableCell>{q.items?.length || 0}</TableCell>
                      <TableCell>{Number(q.total_amount).toLocaleString()}</TableCell>
                      <TableCell>
                        <Badge className={statusStyles[q.status] || ''}>{q.status}</Badge>
                      </TableCell>
                      <TableCell className='text-sm text-muted-foreground'>
                        {q.valid_until ? new Date(q.valid_until).toLocaleDateString() : '-'}
                      </TableCell>
                      <TableCell className='text-sm text-muted-foreground'>
                        {new Date(q.created_at).toLocaleDateString()}
                      </TableCell>
                      <TableCell className='text-right'>
                        <Button variant='ghost' size='sm' onClick={() => setSelected(q)}>
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
      </Card>

      {selected && <QuoteDetailModal quotation={selected} onClose={() => setSelected(null)} />}
    </>
  );
};

const QuoteActions = ({ quotation, onDone }: { quotation: any; onDone: () => void }) => {
  const [sendQuote] = useSendQuotationMutation();
  const [acceptQuote] = useAcceptQuotationMutation();
  const [cancelQuote] = useCancelQuotationMutation();

  const editable = quotation.status === 'draft' || quotation.status === 'sent';

  const run = async (fn: () => any, success: string) => {
    try {
      await fn().unwrap();
      toast.success(success);
      onDone();
    } catch (err: any) {
      toast.error(err?.data?.message || 'Action failed');
    }
  };

  return (
    <>
      {quotation.status === 'draft' && (
        <Button size='sm' onClick={() => run(() => sendQuote(quotation.id), 'Quotation sent')}>
          <Send className='h-4 w-4 mr-1' /> Send
        </Button>
      )}
      {editable && (
        <Button size='sm' onClick={() => run(() => acceptQuote(quotation.id), 'Accepted - sales order created')}>
          <CheckCircle2 className='h-4 w-4 mr-1' /> Accept
        </Button>
      )}
      {editable && (
        <Button
          size='sm'
          variant='outline'
          onClick={() => run(() => cancelQuote(quotation.id), 'Quotation cancelled')}
        >
          <Ban className='h-4 w-4 mr-1' /> Cancel
        </Button>
      )}
    </>
  );
};

const CreateQuotationDialog = ({ open, onOpenChange }: { open: boolean; onOpenChange: (v: boolean) => void }) => {
  const { data: productsData } = useProductsQuery();
  const [createQuotation] = useCreateQuotationMutation();
  const [quoteItems, setQuoteItems] = useState<QuoteItemInput[]>([]);
  const [searchQuery, setSearchQuery] = useState('');
  const [validUntil, setValidUntil] = useState('');
  const [notes, setNotes] = useState('');
  const [terms, setTerms] = useState('');

  const products = productsData?.products ?? [];

  const filteredProducts = searchQuery
    ? products.filter(
        (p: any) =>
          p.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
          (p.sku && p.sku.toLowerCase().includes(searchQuery.toLowerCase())),
      )
    : [];

  const addItem = (product: any) => {
    setQuoteItems((prev) => {
      const existing = prev.find((i) => i.product_id === product.id);
      if (existing) {
        return prev.map((i) => (i.product_id === product.id ? { ...i, quantity: i.quantity + 1 } : i));
      }
      return [...prev, { product_id: product.id, name: product.name, quantity: 1, unit_price: Number(product.selling_price) }];
    });
  };

  const updateItemQty = (productId: number, delta: number) => {
    setQuoteItems((prev) =>
      prev.map((i) => (i.product_id === productId ? { ...i, quantity: Math.max(1, i.quantity + delta) } : i)),
    );
  };

  const removeItem = (productId: number) => {
    setQuoteItems((prev) => prev.filter((i) => i.product_id !== productId));
  };

  const totalAmount = quoteItems.reduce((sum, i) => sum + i.quantity * i.unit_price, 0);

  const handleCreate = async () => {
    if (quoteItems.length === 0) {
      toast.error('Add at least one product');
      return;
    }
    try {
      await createQuotation({
        items: quoteItems.map((i) => ({ product_id: i.product_id, quantity: i.quantity, unit_price: i.unit_price })),
        valid_until: validUntil || undefined,
        notes: notes || undefined,
        terms: terms || undefined,
      }).unwrap();
      toast.success('Quotation created');
      onOpenChange(false);
      setQuoteItems([]);
      setSearchQuery('');
      setValidUntil('');
      setNotes('');
      setTerms('');
    } catch (err: any) {
      toast.error(err?.data?.message || 'Failed to create quotation');
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className='sm:max-w-2xl max-h-screen overflow-y-auto'>
        <DialogHeader>
          <DialogTitle>Create Quotation</DialogTitle>
        </DialogHeader>
        <div className='space-y-4'>
          <div>
            <Label>Search Products</Label>
            <Input
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder='Search by name or SKU...'
            />
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
                  <span>
                    {p.emoji} {p.name}
                  </span>
                  <span className='text-muted-foreground'>
                    {Number(p.selling_price).toLocaleString()} (stock {p.quantity})
                  </span>
                </button>
              ))}
            </div>
          )}
          {quoteItems.length > 0 && (
            <div className='space-y-2'>
              <Label>Items</Label>
              {quoteItems.map((item) => (
                <div
                  key={item.product_id}
                  className='flex items-center justify-between gap-2 bg-muted rounded-xl px-3 py-2'
                >
                  <span className='text-sm flex-1'>{item.name}</span>
                  <div className='flex items-center gap-1'>
                    <Button
                      variant='ghost'
                      size='icon'
                      className='h-7 w-7'
                      onClick={() => updateItemQty(item.product_id, -1)}
                    >
                      <Minus className='h-3 w-3' />
                    </Button>
                    <span className='text-sm font-medium w-6 text-center'>{item.quantity}</span>
                    <Button
                      variant='ghost'
                      size='icon'
                      className='h-7 w-7'
                      onClick={() => updateItemQty(item.product_id, 1)}
                    >
                      <PlusIcon className='h-3 w-3' />
                    </Button>
                  </div>
                  <span className='text-sm w-24 text-right'>{(item.quantity * item.unit_price).toLocaleString()}</span>
                  <Button
                    variant='ghost'
                    size='icon'
                    className='h-7 w-7 text-red-500'
                    onClick={() => removeItem(item.product_id)}
                  >
                    <X className='h-3 w-3' />
                  </Button>
                </div>
              ))}
              <div className='flex justify-between font-bold text-sm border-t border-border pt-2'>
                <span>Total (before tax)</span>
                <span>{totalAmount.toLocaleString()}</span>
              </div>
            </div>
          )}
          <div>
            <Label>Valid Until</Label>
            <Input type='date' value={validUntil} onChange={(e) => setValidUntil(e.target.value)} />
          </div>
          <div>
            <Label>Notes</Label>
            <Textarea value={notes} onChange={(e) => setNotes(e.target.value)} />
          </div>
          <div>
            <Label>Terms</Label>
            <Textarea value={terms} onChange={(e) => setTerms(e.target.value)} />
          </div>
          <Button className='w-full' onClick={handleCreate} disabled={quoteItems.length === 0}>
            Create Quotation
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
};

export const QuotationsPage = () => {
  const [status, setStatus] = useState('all');
  const [showCreate, setShowCreate] = useState(false);

  const statuses = ['all', 'draft', 'sent', 'accepted', 'expired', 'cancelled'];

  return (
    <div className='space-y-6'>
      <Card className='border-border/60'>
        <CardHeader className='flex flex-row items-center justify-between'>
          <CardTitle className='text-lg flex items-center gap-2'>
            <FileText className='h-5 w-5' />
            Quotations
          </CardTitle>
          <Dialog open={showCreate} onOpenChange={setShowCreate}>
            <DialogTrigger asChild>
              <Button size='sm'>
                <Plus className='h-4 w-4 mr-1' /> Create Quotation
              </Button>
            </DialogTrigger>
            <CreateQuotationDialog open={showCreate} onOpenChange={setShowCreate} />
          </Dialog>
        </CardHeader>
        <CardContent className='p-0'>
          <div className='flex flex-wrap gap-2 px-4 pt-2'>
            {statuses.map((s) => (
              <Button
                key={s}
                size='sm'
                variant={status === s ? 'default' : 'outline'}
                onClick={() => setStatus(s)}
                className='capitalize'
              >
                {s}
              </Button>
            ))}
          </div>
        </CardContent>
      </Card>

      <QuotationsTable status={status === 'all' ? '' : status} />
    </div>
  );
};