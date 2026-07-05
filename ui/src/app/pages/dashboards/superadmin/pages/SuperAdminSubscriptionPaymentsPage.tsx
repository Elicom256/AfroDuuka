import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
  useGetSubscriptionPaymentsQuery,
  useUpdateSubscriptionPaymentMutation,
} from '@/app/store/features/subscriptions/subscriptionPaymentsQuery';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { CreditCard, CheckCircle, XCircle, Loader2, Building2, Clock, Wallet } from 'lucide-react';
import { toast } from 'sonner';

const paymentStatusColors: Record<string, string> = {
  pending: 'bg-amber-500/10 text-amber-600 border-amber-500/20',
  completed: 'bg-green-500/10 text-green-600 border-green-500/20',
  failed: 'bg-red-500/10 text-red-600 border-red-500/20',
  rejected: 'bg-gray-500/10 text-gray-600 border-gray-500/20',
};

type FilterTab = 'all' | 'pending' | 'completed' | 'rejected';

export const SuperAdminSubscriptionPaymentsPage = () => {
  const navigate = useNavigate();
  const { data, isLoading } = useGetSubscriptionPaymentsQuery();
  const [updatePayment, { isLoading: isUpdating }] = useUpdateSubscriptionPaymentMutation();
  const [filter, setFilter] = useState<FilterTab>('pending');
  const [rejectDialogOpen, setRejectDialogOpen] = useState(false);
  const [rejectingPayment, setRejectingPayment] = useState<any>(null);
  const [rejectionReason, setRejectionReason] = useState('');

  const allPayments = data?.subscription_payments ?? [];
  const filteredPayments = filter === 'all' ? allPayments : allPayments.filter((p: any) => p.payment_status === filter);

  const handleVerify = async (payment: any, e: React.MouseEvent) => {
    e.stopPropagation();
    try {
      await updatePayment({
        id: payment.id,
        body: { payment_status: 'completed' },
      }).unwrap();
      toast.success('Payment verified as completed');
    } catch (err: any) {
      toast.error(err?.data?.message || 'Failed to verify payment');
    }
  };

  const handleReject = async () => {
    if (!rejectingPayment) return;
    if (!rejectionReason.trim()) {
      toast.error('Rejection reason is required');
      return;
    }
    try {
      await updatePayment({
        id: rejectingPayment.id,
        body: { payment_status: 'rejected', rejection_reason: rejectionReason },
      }).unwrap();
      toast.success('Payment rejected');
      setRejectDialogOpen(false);
      setRejectingPayment(null);
      setRejectionReason('');
    } catch (err: any) {
      toast.error(err?.data?.message || 'Failed to reject payment');
    }
  };

  const tabs: { key: FilterTab; label: string; count: number }[] = [
    { key: 'pending', label: 'Pending', count: allPayments.filter((p: any) => p.payment_status === 'pending').length },
    { key: 'completed', label: 'Completed', count: allPayments.filter((p: any) => p.payment_status === 'completed').length },
    { key: 'rejected', label: 'Rejected', count: allPayments.filter((p: any) => p.payment_status === 'rejected').length },
    { key: 'all', label: 'All', count: allPayments.length },
  ];

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-3xl font-bold tracking-tight flex items-center gap-3'>
          <Wallet className='h-8 w-8' />
          Subscription Payments
        </h1>
        <p className='text-muted-foreground mt-1'>Review and verify subscription payments from businesses</p>
      </div>

      <div className='flex flex-wrap gap-2'>
        {tabs.map((tab) => (
          <Button
            key={tab.key}
            variant={filter === tab.key ? 'default' : 'outline'}
            size='sm'
            onClick={() => setFilter(tab.key)}
          >
            {tab.label}
            <Badge variant='secondary' className='ml-2 text-xs'>
              {tab.count}
            </Badge>
          </Button>
        ))}
      </div>

      <Card>
        <CardHeader>
          <CardTitle className='flex items-center gap-2 text-lg'>
            <CreditCard className='h-5 w-5' />
            {filter === 'all' ? `All Payments (${filteredPayments.length})` : `${filter.charAt(0).toUpperCase() + filter.slice(1)} Payments (${filteredPayments.length})`}
          </CardTitle>
        </CardHeader>
        <CardContent>
          {filteredPayments.length === 0 ? (
            <p className='text-muted-foreground text-sm text-center py-8'>No {filter} payments found.</p>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Business / Plan</TableHead>
                  <TableHead>Amount</TableHead>
                  <TableHead>Method</TableHead>
                  <TableHead>Phone / Transaction</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Date</TableHead>
                  <TableHead>Verified By</TableHead>
                  <TableHead className='text-right'>Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {filteredPayments.map((payment: any) => (
                  <TableRow
                    key={payment.id}
                    className='cursor-pointer hover:bg-muted/50'
                    onClick={() => navigate(`/superadmin/subscription-payments/${payment.id}`)}
                  >
                    <TableCell>
                      <div className='flex flex-col'>
                        <span className='font-medium text-sm flex items-center gap-1.5'>
                          <Building2 className='h-3.5 w-3.5 text-muted-foreground' />
                          {payment.subscription?.business?.name ?? payment.subscription?.business_id ?? 'N/A'}
                        </span>
                        <span className='text-xs text-muted-foreground'>{payment.subscription?.plan?.name ?? 'N/A'}</span>
                      </div>
                    </TableCell>
                    <TableCell className='font-medium'>
                      {payment.subscription?.plan?.currency ?? 'UGX'} {Number(payment.amount_paid).toLocaleString()}
                    </TableCell>
                    <TableCell className='capitalize'>
                      {payment.payment_method?.method?.replace(/_/g, ' ') ?? 'N/A'}
                    </TableCell>
                    <TableCell>
                      <div className='flex flex-col text-sm'>
                        {payment.number_paid && <span>{payment.number_paid}</span>}
                        {payment.transaction_id && (
                          <span className='text-xs text-muted-foreground'>Ref: {payment.transaction_id}</span>
                        )}
                      </div>
                    </TableCell>
                    <TableCell>
                      <Badge className={paymentStatusColors[payment.payment_status] ?? ''} variant='outline'>
                        {payment.payment_status}
                      </Badge>
                    </TableCell>
                    <TableCell className='text-sm'>
                      <div className='flex items-center gap-1.5'>
                        <Clock className='h-3.5 w-3.5 text-muted-foreground' />
                        {payment.created_at ? new Date(payment.created_at).toLocaleDateString() : '-'}
                      </div>
                    </TableCell>
                    <TableCell className='text-sm'>
                      {payment.verified_by ? (payment.verified_by?.username ?? `User #${payment.verified_by}`) : '-'}
                    </TableCell>
                    <TableCell className='text-right'>
                      {payment.payment_status === 'pending' && (
                        <div className='flex items-center justify-end gap-1'>
                          <Button
                            size='sm'
                            variant='outline'
                            className='text-green-600 border-green-500/30 hover:bg-green-500/10'
                            onClick={(e) => handleVerify(payment, e)}
                            disabled={isUpdating}
                          >
                            <CheckCircle className='h-3.5 w-3.5 mr-1' />
                            Verify
                          </Button>
                          <Dialog open={rejectDialogOpen && rejectingPayment?.id === payment.id} onOpenChange={(open) => { setRejectDialogOpen(open); if (!open) { setRejectingPayment(null); setRejectionReason(''); } }}>
                            <DialogTrigger asChild>
                              <Button
                                size='sm'
                                variant='outline'
                                className='text-red-600 border-red-500/30 hover:bg-red-500/10'
                                onClick={(e) => { e.stopPropagation(); setRejectingPayment(payment); setRejectDialogOpen(true); }}
                              >
                                <XCircle className='h-3.5 w-3.5 mr-1' />
                                Reject
                              </Button>
                            </DialogTrigger>
                            <DialogContent>
                              <DialogHeader>
                                <DialogTitle>Reject Payment</DialogTitle>
                                <DialogDescription>
                                  Provide a reason for rejecting this payment.
                                </DialogDescription>
                              </DialogHeader>
                              <div className='space-y-4'>
                                <div className='space-y-2'>
                                  <label className='text-sm font-medium'>Rejection Reason</label>
                                  <Textarea
                                    value={rejectionReason}
                                    onChange={(e) => setRejectionReason(e.target.value)}
                                    placeholder='Explain why this payment is being rejected...'
                                    rows={3}
                                  />
                                </div>
                              </div>
                              <DialogFooter>
                                <Button variant='outline' onClick={() => { setRejectDialogOpen(false); setRejectingPayment(null); setRejectionReason(''); }}>
                                  Cancel
                                </Button>
                                <Button
                                  variant='destructive'
                                  onClick={handleReject}
                                  disabled={isUpdating || !rejectionReason.trim()}
                                >
                                  {isUpdating && <Loader2 className='h-4 w-4 mr-2 animate-spin' />}
                                  Reject Payment
                                </Button>
                              </DialogFooter>
                            </DialogContent>
                          </Dialog>
                        </div>
                      )}
                      {payment.payment_status === 'rejected' && payment.rejection_reason && (
                        <span className='text-xs text-muted-foreground' title={payment.rejection_reason}>
                          {payment.rejection_reason.length > 30
                            ? payment.rejection_reason.slice(0, 30) + '...'
                            : payment.rejection_reason}
                        </span>
                      )}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>
    </div>
  );
};
