import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { usePaymentGatewaysQuery, useCreatePaymentGatewayMutation, useDeletePaymentGatewayMutation } from '@/app/store/features/business/admin/paymentGatewaysQuery';
import { Wallet } from 'lucide-react';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { AddPaymentGateway } from '../components/payment-gateways/AddPaymentGateway';
import { PaymentGatewaysTable } from '../components/payment-gateways/PaymentGatewaysTable';

export const AdminPaymentGatewaysPage = () => {
  const { data, isLoading } = usePaymentGatewaysQuery();
  const [createGateway] = useCreatePaymentGatewayMutation();
  const [deleteGw] = useDeletePaymentGatewayMutation();
  const gateways = data?.data || [];

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <div className='flex items-center justify-between'>
        <div>
          <h1 className='text-3xl font-bold tracking-tight flex items-center gap-3'>
            <Wallet className='h-8 w-8' /> Payment Gateways
          </h1>
          <p className='text-muted-foreground'>Configure mobile money and card payment providers</p>
        </div>
        <AddPaymentGateway createGateway={createGateway} />
      </div>
      <Card>
        <CardHeader><CardTitle>Active Gateways ({gateways.length})</CardTitle></CardHeader>
        <CardContent>
          {gateways.length === 0 ? (
            <p className='text-muted-foreground text-sm text-center py-8'>No payment gateways configured.</p>
          ) : (
            <PaymentGatewaysTable gateways={gateways} onDelete={deleteGw} />
          )}
        </CardContent>
      </Card>
    </div>
  );
};
