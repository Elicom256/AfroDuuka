import { useEffect, useState } from 'react';
import { Switch } from '@/components/ui/switch';
import { Card, CardContent } from '@/components/ui/card';
import {
  useGetCustomerSettingsQuery,
  useUpdateCustomerSettingsMutation,
} from '@/app/store/features/business/settings/customer';
import { Loader2, Users, UserPlus } from 'lucide-react';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { toast } from 'sonner';

export const CustomerSettings = () => {
  const { data, isLoading } = useGetCustomerSettingsQuery();
  const [updateSetting, { isLoading: isUpdating }] = useUpdateCustomerSettingsMutation();

  const [allowCreate, setAllowCreate] = useState(false);
  const [updatingId, setUpdatingId] = useState<string | null>(null);
  console.log('setting customers ==>', data);
  useEffect(() => {
    if (data?.settings) {
      setAllowCreate(data.settings.status === 'enabled');
    }
  }, [data]);

  const toggleCustomerCreate = async () => {
    if (!data?.settings?.id) return;

    const settingId = data.settings.id;
    const oldValue = allowCreate;
    const newValue = !allowCreate;
    const status = newValue ? 'enabled' : 'disabled';

    setAllowCreate(newValue);
    setUpdatingId(settingId);

    try {
      const payload = { id: settingId, body: { status } };
      const res = await updateSetting(payload).unwrap();
      console.log('✅ Success response==>', res);
      toast.success(res.message || 'Customer settings updated successfully');
    } catch (error: any) {
      console.error('❌ Full error:', error);
      setAllowCreate(oldValue);
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
          <Users className='h-7 w-7 text-primary' />
          Customer Settings
        </h3>
        <p className='text-muted-foreground mt-1'>Control how customers are managed in your system</p>
      </div>

      <Card className='transition-all hover:shadow-md'>
        <CardContent className='p-6'>
          <div className='flex items-center justify-between'>
            <div className='flex items-start gap-4'>
              <div className='mt-1'>
                <UserPlus className='h-10 w-10 text-muted-foreground' />
              </div>
              <div className='space-y-1'>
                <p className='font-semibold text-lg'>Allow Adding Customers</p>
                <p className='text-sm text-muted-foreground max-w-md'>
                  Enable staff to create new customer profiles directly from the POS during checkout. Useful for walk-in
                  customers and building your database.
                </p>
              </div>
            </div>

            <div className='flex items-center gap-3'>
              <Switch
                checked={allowCreate}
                onCheckedChange={toggleCustomerCreate}
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

export default CustomerSettings;
