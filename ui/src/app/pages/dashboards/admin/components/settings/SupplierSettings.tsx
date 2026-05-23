import { useEffect, useState } from 'react';
import { Switch } from '@/components/ui/switch';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
  useGetSupplierSettingsQuery,
  useUpdateSupplierSettingsMutation,
} from '@/app/store/features/business/settings/supplier';
import { Loader2, Truck, UserCheck } from 'lucide-react';

export const SupplierSettings = () => {
  const { data, isLoading } = useGetSupplierSettingsQuery();
  const [updateSetting, { isLoading: isSaving }] = useUpdateSupplierSettingsMutation();
  console.log('useGetSupplierSettingsQuery==>', data);
  const [autoApprove, setAutoApprove] = useState(false);

  useEffect(() => {
    if (data?.autoApprove !== undefined) {
      setAutoApprove(!!data.autoApprove);
    }
  }, [data]);

  const save = async () => {
    try {
      await updateSetting({
        key: 'supplier-settings',
        body: { autoApprove },
      }).unwrap();

      alert('✅ Supplier settings updated successfully!');
    } catch (e) {
      console.error(e);
      alert('❌ Failed to save settings');
    }
  };

  if (isLoading) {
    return <p className='text-center py-8 text-muted-foreground'>Loading supplier settings...</p>;
  }

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

            <Switch
              checked={autoApprove}
              onCheckedChange={setAutoApprove}
              className='scale-125 data-[state=checked]:bg-green-500'
            />
          </div>
        </CardContent>
      </Card>

      <div className='flex justify-end pt-2'>
        <Button onClick={save} disabled={isSaving} size='lg' className='min-w-40'>
          {isSaving ? (
            <>
              <Loader2 className='mr-2 h-4 w-4 animate-spin' />
              Saving Changes...
            </>
          ) : (
            'Save Settings'
          )}
        </Button>
      </div>
    </div>
  );
};

export default SupplierSettings;
