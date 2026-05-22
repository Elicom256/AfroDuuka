import { useEffect, useState } from 'react';
import { Switch } from '@/components/ui/switch';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
  useGetCustomerSettingsQuery,
  useUpdateCustomerSettingsMutation,
} from '@/app/store/features/business/settings/customer';
import { Loader2, Users, UserPlus } from 'lucide-react';

export const CustomerSettings = () => {
  const { data, isLoading } = useGetCustomerSettingsQuery();
  const [updateSetting, { isLoading: isSaving }] = useUpdateCustomerSettingsMutation();
  
  const [allowCreate, setAllowCreate] = useState(false);

  useEffect(() => {
    if (data?.allowCreate !== undefined) {
      setAllowCreate(!!data.allowCreate);
    }
  }, [data]);

  const save = async () => {
    try {
      await updateSetting({ 
        key: 'customer-settings', 
        body: { allowCreate } 
      }).unwrap();
      
      alert('✅ Customer settings updated successfully!');
    } catch (e) {
      console.error(e);
      alert('❌ Failed to save settings');
    }
  };

  if (isLoading) {
    return <p className="text-center py-8 text-muted-foreground">Loading customer settings...</p>;
  }

  return (
    <div className="space-y-6">
      <div>
        <h3 className="text-2xl font-semibold tracking-tight flex items-center gap-3">
          <Users className="h-7 w-7 text-primary" />
          Customer Settings
        </h3>
        <p className="text-muted-foreground mt-1">
          Control how customers are managed in your system
        </p>
      </div>

      <Card className="transition-all hover:shadow-md">
        <CardContent className="p-6">
          <div className="flex items-center justify-between">
            <div className="flex items-start gap-4">
              <div className="mt-1">
                <UserPlus className="h-10 w-10 text-muted-foreground" />
              </div>
              <div className="space-y-1">
                <p className="font-semibold text-lg">Allow Adding Customers</p>
                <p className="text-sm text-muted-foreground max-w-md">
                  Enable staff to create new customer profiles directly from the POS 
                  during checkout. Useful for walk-in customers and building your database.
                </p>
              </div>
            </div>

            <Switch
              checked={allowCreate}
              onCheckedChange={setAllowCreate}
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

export default CustomerSettings;