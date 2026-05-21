import { useEffect, useState } from 'react';
import {
  useGetCustomerSettingsQuery,
  useUpdateCustomerSettingsMutation,
} from '@/app/store/features/business/settings/customer';
import { Button } from '@/components/ui/button';

export const CustomerSettings = () => {
  const { data, isLoading } = useGetCustomerSettingsQuery();
  const [updateSetting] = useUpdateCustomerSettingsMutation();
  const [allowCreate, setAllowCreate] = useState(false);

  useEffect(() => {
    if (data?.allowCreate !== undefined) setAllowCreate(!!data.allowCreate);
  }, [data]);

  const save = async () => {
    try {
      await updateSetting({ key: 'customer-settings', body: { allowCreate } }).unwrap();
      alert('Customer settings saved');
    } catch (e) {
      console.error(e);
      alert('Save failed');
    }
  };

  if (isLoading) return <p>Loading...</p>;

  return (
    <div className='space-y-4'>
      <h3 className='text-lg font-semibold'>Customer Settings</h3>
      <label className='inline-flex items-center gap-2'>
        <input type='checkbox' checked={allowCreate} onChange={() => setAllowCreate((v) => !v)} />
        <span>Allow adding customers from POS</span>
      </label>
      <div>
        <Button onClick={save}>Save</Button>
      </div>
    </div>
  );
};

export default CustomerSettings;
