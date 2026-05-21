import { useEffect, useState } from 'react';
import {
  useGetReportsSettingsQuery,
  useUpdateReportsSettingsMutation,
} from '@/app/store/features/business/settings/reports';
import { Button } from '@/components/ui/button';

export const ReportsSettings = () => {
  const { data, isLoading } = useGetReportsSettingsQuery();
  const [updateSetting] = useUpdateReportsSettingsMutation();
  const [enabled, setEnabled] = useState(false);

  useEffect(() => {
    if (data?.enabled !== undefined) setEnabled(!!data.enabled);
  }, [data]);

  const save = async () => {
    try {
      await updateSetting({ key: 'reports-settings', body: { enabled } }).unwrap();
      alert('Reports settings saved');
    } catch (e) {
      console.error(e);
      alert('Save failed');
    }
  };

  if (isLoading) return <p>Loading...</p>;

  return (
    <div className='space-y-4'>
      <h3 className='text-lg font-semibold'>Reports</h3>
      <label className='inline-flex items-center gap-2'>
        <input type='checkbox' checked={enabled} onChange={() => setEnabled((v) => !v)} />
        <span>Enable business performance reports</span>
      </label>
      <div>
        <Button onClick={save}>Save</Button>
      </div>
    </div>
  );
};

export default ReportsSettings;
