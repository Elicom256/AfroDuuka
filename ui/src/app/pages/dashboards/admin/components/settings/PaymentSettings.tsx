import { useEffect, useState } from 'react';
import { Switch } from '@/components/ui/switch';
import { Card, CardContent } from '@/components/ui/card';
import {
  useGetPaymentSettingsQuery,
  useUpdatePaymentSettingsMutation,
} from '@/app/store/features/business/settings/payment';
import { Loader2 } from 'lucide-react';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { toast } from 'sonner';

const methods = [
  { method: 'mobile_money', label: 'Mobile Money', icon: '📱' },
  { method: 'card', label: 'Card Payment', icon: '💳' },
  { method: 'cash', label: 'Cash', icon: '💵' },
  { method: 'credit', label: 'Credit', icon: '🏦' },
  { method: 'cryptocurrency', label: 'Cryptocurrency', icon: '₿' },
];

export const PaymentSettings = () => {
  const { data, isLoading } = useGetPaymentSettingsQuery();
  const [updateSetting, { isLoading: isUpdating }] = useUpdatePaymentSettingsMutation();

  const [localSettings, setLocalSettings] = useState<any[]>([]);
  const [updatingId, setUpdatingId] = useState<string | null>(null);
  console.log('settings==>', data);

  // Sync backend data
  useEffect(() => {
    if (data?.settings) {
      const merged = data.settings.map((method: any) => {
        const methodInfo = methods.find((m) => m.method === method.method);
        return {
          ...method,
          ...methodInfo,
          // Ensure status is always present
          status: method.status || 'disabled',
        };
      });
      setLocalSettings(merged);
    }
  }, [data]);

  const toggleMethod = async (id: string) => {
    const currentMethod = localSettings.find((item) => item.id === id);
    if (!currentMethod) return;

    const currentStatus = currentMethod.status;
    const newStatus = currentStatus === 'enabled' ? 'disabled' : 'enabled';

    setLocalSettings((prev) => prev.map((item) => (item.id === id ? { ...item, status: newStatus } : item)));
    setUpdatingId(id);

    try {
      const payload = { id, body: { status: newStatus } };
      const res = await updateSetting(payload).unwrap();
      console.log('✅ Success response==>', res);
      toast.success(res.message);
    } catch (error: any) {
      console.error('❌ Full error:', error);
      setLocalSettings((prev) => prev.map((item) => (item.id === id ? { ...item, status: currentStatus } : item)));
      const errorMsg = error?.data?.message || error?.message || 'Failed to update';
      toast.error(errorMsg);
    } finally {
      setUpdatingId(null);
    }
  };

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <div>
        <h3 className='text-2xl font-semibold tracking-tight'>Payment Methods</h3>
        <p className='text-muted-foreground mt-1'>Toggle to enable or disable payment options</p>
      </div>

      <div className='grid gap-3 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3'>
        {localSettings.map((method) => (
          <Card key={method.id} className='transition-all hover:shadow-md hover:border-primary/30'>
            <CardContent className='p-6 flex items-center justify-between'>
              <div className='flex items-center gap-4'>
                <div className='text-3xl'>{method.icon}</div>
                <div>
                  <p className='font-medium'>{method.label}</p>
                  <p className='text-xs text-muted-foreground capitalize'>{method.method.replace('_', ' ')}</p>
                </div>
              </div>

              <div className='flex items-center gap-3'>
                <Switch
                  checked={method.status === 'enabled'}
                  onCheckedChange={() => toggleMethod(method.id)}
                  disabled={isUpdating && updatingId === method.id}
                  className='scale-125 data-[state=checked]:bg-green-500'
                />

                {isUpdating && updatingId === method.id && (
                  <Loader2 className='h-4 w-4 animate-spin text-muted-foreground' />
                )}
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  );
};

export default PaymentSettings;
