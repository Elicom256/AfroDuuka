import { useEffect, useState } from 'react';
import {
  useGetAttendanceSettingsQuery,
  useUpdateAttendanceSettingsMutation,
} from '@/app/store/features/business/settings/attendance';
import { Button } from '@/components/ui/button';

export const AttendanceSettings = () => {
  const { data, isLoading } = useGetAttendanceSettingsQuery();
  const [updateSetting] = useUpdateAttendanceSettingsMutation();
  const [enabled, setEnabled] = useState(false);

  useEffect(() => {
    if (data?.enabled !== undefined) setEnabled(!!data.enabled);
  }, [data]);

  const save = async () => {
    try {
      await updateSetting({ key: 'attendance-settings', body: { enabled } }).unwrap();
      alert('Attendance settings saved');
    } catch (e) {
      console.error(e);
      alert('Save failed');
    }
  };

  if (isLoading) return <p>Loading...</p>;

  return (
    <div className='space-y-4'>
      <h3 className='text-lg font-semibold'>Attendance</h3>
      <label className='inline-flex items-center gap-2'>
        <input type='checkbox' checked={enabled} onChange={() => setEnabled((v) => !v)} />
        <span>Enable attendance tracking for workers</span>
      </label>
      <div>
        <Button onClick={save}>Save</Button>
      </div>
    </div>
  );
};

export default AttendanceSettings;
