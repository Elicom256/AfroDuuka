import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useStockTransfersQuery, useDispatchStockTransferMutation, useCancelStockTransferMutation } from '@/app/store/features/business/admin/stockTransfersQuery';
import { ArrowLeftRight, ArrowRight, XCircle, Send } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { toast } from 'sonner';

const statusColors: Record<string, string> = {
  draft: 'bg-gray-100 text-gray-700',
  in_transit: 'bg-blue-100 text-blue-700',
  received: 'bg-green-100 text-green-700',
  cancelled: 'bg-red-100 text-red-700',
};

export const AdminStockTransfersPage = () => {
  const { data, isLoading } = useStockTransfersQuery();
  const [dispatch] = useDispatchStockTransferMutation();
  const [cancel] = useCancelStockTransferMutation();
  const transfers = data?.data || [];

  const handleDispatch = async (id: string) => {
    try { await dispatch(id).unwrap(); toast.success('Transfer dispatched'); }
    catch (err: any) { toast.error(err?.data?.message || 'Failed to dispatch'); }
  };

  const handleCancel = async (id: string) => {
    try { await cancel(id).unwrap(); toast.success('Transfer cancelled'); }
    catch { toast.error('Failed to cancel'); }
  };

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-3xl font-bold tracking-tight flex items-center gap-3'>
          <ArrowLeftRight className='h-8 w-8' /> Stock Transfers
        </h1>
        <p className='text-muted-foreground'>Inter-branch stock movement tracking</p>
      </div>
      <Card>
        <CardHeader><CardTitle>All Transfers</CardTitle></CardHeader>
        <CardContent>
          {transfers.length === 0 ? (
            <p className='text-muted-foreground text-sm text-center py-8'>No stock transfers yet.</p>
          ) : (
            <div className='space-y-2'>
              {transfers.map((t: any) => (
                <div key={t.id} className='flex items-center justify-between p-3 bg-muted/50 rounded-lg'>
                  <div className='flex items-center gap-3'>
                    <span className='font-medium text-sm'>{t.from_branch?.name || 'Branch A'}</span>
                    <ArrowRight className='h-4 w-4 text-muted-foreground' />
                    <span className='font-medium text-sm'>{t.to_branch?.name || 'Branch B'}</span>
                    <Badge className={statusColors[t.status]}>{t.status.replace(/_/g, ' ')}</Badge>
                    <span className='text-muted-foreground text-xs'>{t.items?.length || 0} items</span>
                  </div>
                  <div className='flex gap-1'>
                    {t.status === 'draft' && (
                      <>
                        <Button size='sm' variant='outline' onClick={() => handleDispatch(t.id)}>
                          <Send className='h-3 w-3 mr-1' /> Dispatch
                        </Button>
                        <Button size='sm' variant='ghost' onClick={() => handleCancel(t.id)}>
                          <XCircle className='h-3 w-3 text-destructive' />
                        </Button>
                      </>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
};
