import { useEffect, useState } from 'react';
import {
  useGetSupplierSettingsQuery,
  useUpdateSupplierSettingsMutation,
} from '@/app/store/features/business/settings/supplier';
import { Button } from '@/components/ui/button';

export const SupplierSettings = () => {
  const { data, isLoading } = useGetSupplierSettingsQuery();
  const [updateSetting] = useUpdateSupplierSettingsMutation();
  const [autoApprove, setAutoApprove] = useState(false);

  useEffect(() => {
    if (data?.autoApprove !== undefined) setAutoApprove(!!data.autoApprove);
  }, [data]);

  const save = async () => {
    try {
      await updateSetting({ key: 'supplier-settings', body: { autoApprove } }).unwrap();
      alert('Supplier settings saved');
    } catch (e) {
      console.error(e);
      alert('Save failed');
    }
  };

  if (isLoading) return <p>Loading...</p>;

  return (
    <div className='space-y-4'>
      <h3 className='text-lg font-semibold'>Supplier Settings</h3>
      <label className='inline-flex items-center gap-2'>
        <input type='checkbox' checked={autoApprove} onChange={() => setAutoApprove((v) => !v)} />
        <span>Auto-approve new suppliers</span>
      </label>
      <div>
        <Button onClick={save}>Save</Button>
      </div>
    </div>
  );
};

export default SupplierSettings;
