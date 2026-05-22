import { useEffect, useState } from 'react';
import { Switch } from '@/components/ui/switch';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { useGetPaymentSettingsQuery, useUpdatePaymentSettingsMutation } from '@/app/store/features/business/settings/payment';
import { Loader2 } from 'lucide-react';

const methods = [
  { id: 'mobile_money', label: 'Mobile Money', icon: '📱' },
  { id: 'card', label: 'Card Payment', icon: '💳' },
  { id: 'cash', label: 'Cash', icon: '💵' },
  { id: 'credit', label: 'Credit', icon: '🏦' },
  { id: 'crypto', label: 'Cryptocurrency', icon: '₿' },
];

export const PaymentSettings = () => {
  const { data, isLoading } = useGetPaymentSettingsQuery();
  const [updateSetting, { isLoading: isSaving }] = useUpdatePaymentSettingsMutation();
  
  const [enabled, setEnabled] = useState<Record<string, boolean>>({});

  useEffect(() => {
    if (data?.methods) {
      const map: Record<string, boolean> = {};
      methods.forEach((m) => {
        map[m.id] = data.methods.includes(m.id);
      });
      setEnabled(map);
    }
  }, [data]);

  const toggleMethod = (id: string) => {
    setEnabled(prev => ({ ...prev, [id]: !prev[id] }));
  };

  const save = async () => {
    const selected = methods.filter(m => enabled[m.id]).map(m => m.id);
    
    try {
      await updateSetting({ 
        key: 'payment-settings', 
        body: { methods: selected } 
      }).unwrap();
      
      // You can replace this with a nice toast later
      alert('✅ Payment methods updated successfully!');
    } catch (e) {
      console.error(e);
      alert('❌ Failed to save settings');
    }
  };

  if (isLoading) return <p className="text-center py-8">Loading payment settings...</p>;

  return (
    <div className="space-y-6">
      <div>
        <h3 className="text-2xl font-semibold tracking-tight">Payment Methods</h3>
        <p className="text-muted-foreground mt-1">
          Choose which payment options your customers can use
        </p>
      </div>

      <div className="grid gap-3 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
        {methods.map(({ id, label, icon }) => (
          <Card key={id} className="transition-all hover:shadow-md hover:border-primary/30">
            <CardContent className="p-6 flex items-center justify-between">
              <div className="flex items-center gap-4">
                <div className="text-3xl">{icon}</div>
                <div>
                  <p className="font-medium">{label}</p>
                  <p className="text-xs text-muted-foreground capitalize">
                    {id.replace('_', ' ')}
                  </p>
                </div>
              </div>

              <Switch
                // checked={!!enabled[id]}
                onCheckedChange={() => toggleMethod(id)}
                className="scale-125 data-[state=checked]:bg-green-500"
                // className='size-38'
              />
            </CardContent>
          </Card>
        ))}
      </div>

      <div className="flex justify-end pt-4">
        <Button 
          onClick={save} 
          disabled={isSaving}
          size="lg"
          className="min-w-35"
        >
          {isSaving ? (
            <>
              <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              Saving...
            </>
          ) : (
            'Save Changes'
          )}
        </Button>
      </div>
    </div>
  );
};

export default PaymentSettings;