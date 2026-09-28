import { useEffect, useState } from 'react';
import { Switch } from '@/components/ui/switch';
import { Card, CardContent } from '@/components/ui/card';
import {
  useGetSupplierSettingsQuery,
  useUpdateSupplierSettingsMutation,
} from '@/app/store/features/business/settings/supplier';
import { Loader2, Truck, UserCheck } from 'lucide-react';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { toast } from 'sonner';

export const SupplierSettings = () => {
  const { data, isLoading } = useGetSupplierSettingsQuery();
  const [updateSetting, { isLoading: isUpdating }] = useUpdateSupplierSettingsMutation();

  const [autoApprove, setAutoApprove] = useState(false);
  const [updatingId, setUpdatingId] = useState<string | null>(null);

  useEffect(() => {
    if (data?.settings) {
      setAutoApprove(data.settings.status === 'enabled');
    }
  }, [data]);

  const toggleSupplierApproval = async () => {
    if (!data?.settings?.id) return;

    const settingId = data.settings.id;
    const oldValue = autoApprove;
    const newValue = !autoApprove;
    const status = newValue ? 'enabled' : 'disabled';

    setAutoApprove(newValue);
    setUpdatingId(settingId);

    try {
      const payload = { id: settingId, body: { status } };
      const res = await updateSetting(payload).unwrap();
      console.log('✅ Success response==>', res);
      toast.success(res.message || 'Supplier settings updated successfully');
    } catch (error: any) {
      console.error('❌ Full error:', error);
      setAutoApprove(oldValue);
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
        <h3 className='text-2xl font-semibold tracking-tight flex items-center gap-3'>
          <Truck className='h-7 w-7 text-primary' />
          Supplier Settings
        </h3>
        <p className='text-muted-foreground mt-1'>Configure how suppliers are managed in your system</p>
      </div>

      <Card className='transition-all hover:shadow-md'>
        <CardContent className='p-6'>
          <div className='flex items-center justify-between'>
            <div className='flex items-start gap-4'>
              <div className='mt-1'>
                <UserCheck className='h-10 w-10 text-muted-foreground' />
              </div>
              <div className='space-y-1'>
                <p className='font-semibold text-lg'>Auto-Approve New Suppliers</p>
                <p className='text-sm text-muted-foreground max-w-md'>
                  Automatically approve new suppliers when they register. Disable this if you want to manually review
                  and verify all supplier applications.
                </p>
              </div>
            </div>

            <div className='flex items-center gap-3'>
              <Switch
                checked={autoApprove}
                onCheckedChange={toggleSupplierApproval}
                disabled={!!(isUpdating && updatingId)}
                className='scale-125 data-[state=checked]:bg-green-500'
              />
              {isUpdating && updatingId && <Loader2 className='h-4 w-4 animate-spin text-muted-foreground' />}
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default SupplierSettings;
