import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { usePaymentGatewaysQuery, useDeletePaymentGatewayMutation } from '@/app/store/features/business/admin/paymentGatewaysQuery';
import { Wallet, Trash2, CheckCircle, XCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { toast } from 'sonner';

export const AdminPaymentGatewaysPage = () => {
  const { data, isLoading } = usePaymentGatewaysQuery();
  const [deleteGw] = useDeletePaymentGatewayMutation();
  const gateways = data?.data || [];

  const handleDelete = async (id: string) => {
    try { await deleteGw(id).unwrap(); toast.success('Gateway removed'); }
    catch { toast.error('Failed to delete'); }
  };

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-3xl font-bold tracking-tight flex items-center gap-3'>
          <Wallet className='h-8 w-8' /> Payment Gateways
        </h1>
        <p className='text-muted-foreground'>Configure mobile money and card payment providers</p>
      </div>
      <Card>
        <CardHeader><CardTitle>Active Gateways</CardTitle></CardHeader>
        <CardContent>
          {gateways.length === 0 ? (
            <p className='text-muted-foreground text-sm text-center py-8'>No payment gateways configured.</p>
          ) : (
            <div className='space-y-2'>
              {gateways.map((g: any) => (
                <div key={g.id} className='flex items-center justify-between p-3 bg-muted/50 rounded-lg'>
                  <div className='flex items-center gap-3'>
                    <span className='font-medium capitalize'>{g.provider.replace(/_/g, ' ')}</span>
                    <Badge variant={g.is_active ? 'default' : 'secondary'}>
                      {g.is_active ? <CheckCircle className='h-3 w-3 mr-1' /> : <XCircle className='h-3 w-3 mr-1' />}
                      {g.is_active ? 'Active' : 'Inactive'}
                    </Badge>
                  </div>
                  <Button variant='ghost' size='icon' onClick={() => handleDelete(g.id)}><Trash2 className='h-4 w-4 text-destructive' /></Button>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
};
