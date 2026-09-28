import { useEffect, useState } from 'react';
import { Switch } from '@/components/ui/switch';
import { Card, CardContent } from '@/components/ui/card';
import {
  useGetAttendanceSettingsQuery,
  useUpdateAttendanceSettingsMutation,
} from '@/app/store/features/business/settings/attendance';
import { Loader2, Users, Clock } from 'lucide-react';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { toast } from 'sonner';

export const AttendanceSettings = () => {
  const { data, isLoading } = useGetAttendanceSettingsQuery();
  const [updateSetting, { isLoading: isUpdating }] = useUpdateAttendanceSettingsMutation();

  const [enabled, setEnabled] = useState(false);
  const [updatingId, setUpdatingId] = useState<string | null>(null);

  useEffect(() => {
    if (data?.settings) {
      setEnabled(data.settings.status === 'enabled');
    }
  }, [data]);

  const toggleAttendance = async () => {
    if (!data?.settings?.id) return;

    const settingId = data.settings.id;
    const oldValue = enabled;
    const newValue = !enabled;
    const status = newValue ? 'enabled' : 'disabled';

    setEnabled(newValue);
    setUpdatingId(settingId);

    try {
      const payload = { id: settingId, body: { status } };
      const res = await updateSetting(payload).unwrap();
      console.log('✅ Success response==>', res);
      toast.success(res.message || 'Attendance updated successfully');
    } catch (error: any) {
      console.error('❌ Full error:', error);
      setEnabled(oldValue);
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
          <Clock className='h-7 w-7 text-primary' />
          Attendance Tracking
        </h3>
        <p className='text-muted-foreground mt-1'>Manage worker attendance system for your business</p>
      </div>

      <Card className='transition-all hover:shadow-md'>
        <CardContent className='p-6'>
          <div className='flex items-center justify-between'>
            <div className='flex items-start gap-4'>
              <div className='mt-1'>
                <Users className='h-10 w-10 text-muted-foreground' />
              </div>
              <div className='space-y-1'>
                <p className='font-semibold text-lg'>Enable Attendance</p>
                <p className='text-sm text-muted-foreground max-w-md'>
                  Allow workers to clock in/out and track daily attendance. This helps with payroll, reports, and
                  productivity insights.
                </p>
              </div>
            </div>

            <div className='flex items-center gap-3'>
              <Switch
                checked={enabled}
                onCheckedChange={toggleAttendance}
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

export default AttendanceSettings;
