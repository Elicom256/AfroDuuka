import { useEffect, useState } from 'react';
import { Switch } from '@/components/ui/switch';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
  useGetReportsSettingsQuery,
  useUpdateReportsSettingsMutation,
} from '@/app/store/features/business/settings/reports';
import { Loader2, BarChart3, TrendingUp } from 'lucide-react';

export const ReportsSettings = () => {
  const { data, isLoading } = useGetReportsSettingsQuery();
  const [updateSetting, { isLoading: isSaving }] = useUpdateReportsSettingsMutation();
  
  const [enabled, setEnabled] = useState(false);

  useEffect(() => {
    if (data?.enabled !== undefined) {
      setEnabled(!!data.enabled);
    }
  }, [data]);

  const save = async () => {
    try {
      await updateSetting({ 
        key: 'reports-settings', 
        body: { enabled } 
      }).unwrap();
      
      alert('✅ Reports settings updated successfully!');
    } catch (e) {
      console.error(e);
      alert('❌ Failed to save settings');
    }
  };

  if (isLoading) {
    return <p className="text-center py-8 text-muted-foreground">Loading reports settings...</p>;
  }

  return (
    <div className="space-y-6">
      <div>
        <h3 className="text-2xl font-semibold tracking-tight flex items-center gap-3">
          <BarChart3 className="h-7 w-7 text-primary" />
          Business Reports
        </h3>
        <p className="text-muted-foreground mt-1">
          Manage access to performance and analytics reports
        </p>
      </div>

      <Card className="transition-all hover:shadow-md">
        <CardContent className="p-6">
          <div className="flex items-center justify-between">
            <div className="flex items-start gap-4">
              <div className="mt-1">
                <TrendingUp className="h-10 w-10 text-muted-foreground" />
              </div>
              <div className="space-y-1">
                <p className="font-semibold text-lg">Enable Reports</p>
                <p className="text-sm text-muted-foreground max-w-md">
                  Allow viewing of detailed business performance reports including 
                  sales, attendance, inventory, and profitability analytics.
                </p>
              </div>
            </div>

            <Switch
              checked={enabled}
              onCheckedChange={setEnabled}
              className="scale-125 data-[state=checked]:bg-green-500"
            />
          </div>
        </CardContent>
      </Card>

      <div className="flex justify-end pt-2">
        <Button 
          onClick={save} 
          disabled={isSaving}
          size="lg"
          className="min-w-40"
        >
          {isSaving ? (
            <>
              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
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

export default ReportsSettings;