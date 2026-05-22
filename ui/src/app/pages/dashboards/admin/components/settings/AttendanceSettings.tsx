import { useEffect, useState } from 'react';
import { Switch } from '@/components/ui/switch';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
  useGetAttendanceSettingsQuery,
  useUpdateAttendanceSettingsMutation,
} from '@/app/store/features/business/settings/attendance';
import { Loader2, Users, Clock } from 'lucide-react';

export const AttendanceSettings = () => {
  const { data, isLoading } = useGetAttendanceSettingsQuery();
  const [updateSetting, { isLoading: isSaving }] = useUpdateAttendanceSettingsMutation();

  const [enabled, setEnabled] = useState(false);

  useEffect(() => {
    if (data?.enabled !== undefined) {
      setEnabled(!!data.enabled);
    }
  }, [data]);

  const save = async () => {
    try {
      await updateSetting({
        key: 'attendance-settings',
        body: { enabled },
      }).unwrap();

      alert('✅ Attendance settings updated successfully!');
    } catch (e) {
      console.error(e);
      alert('❌ Failed to save settings');
    }
  };

  if (isLoading) {
    return <p className='text-center py-8 text-muted-foreground'>Loading attendance settings...</p>;
  }

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

            <Switch
              checked={enabled}
              onCheckedChange={setEnabled}
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

export default AttendanceSettings;
