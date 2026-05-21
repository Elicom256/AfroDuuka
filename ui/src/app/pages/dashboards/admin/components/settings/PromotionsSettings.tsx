import { useEffect, useState } from 'react';
import {
  useGetPromotionsSettingsQuery,
  useUpdatePromotionsSettingsMutation,
} from '@/app/store/features/business/settings/promotions';
import { Button } from '@/components/ui/button';

export const PromotionsSettings = () => {
  const { data, isLoading } = useGetPromotionsSettingsQuery();
  const [updateSetting] = useUpdatePromotionsSettingsMutation();
  const [enabled, setEnabled] = useState(false);

  useEffect(() => {
    if (data?.enabled !== undefined) setEnabled(!!data.enabled);
  }, [data]);

  const save = async () => {
    try {
      await updateSetting({ key: 'promotions-settings', body: { enabled } }).unwrap();
      alert('Promotions settings saved');
    } catch (e) {
      console.error(e);
      alert('Save failed');
    }
  };

  if (isLoading) return <p>Loading...</p>;

  return (
    <div className='space-y-4'>
      <h3 className='text-lg font-semibold'>Promotions</h3>
      <label className='inline-flex items-center gap-2'>
        <input type='checkbox' checked={enabled} onChange={() => setEnabled((v) => !v)} />
        <span>Enable promotions</span>
      </label>
      <div>
        <Button onClick={save}>Save</Button>
      </div>
    </div>
  );
};

export default PromotionsSettings;
