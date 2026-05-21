import { useEffect, useState } from 'react';
import {
  useGetPaymentSettingsQuery,
  useUpdatePaymentSettingsMutation,
} from '@/app/store/features/business/settings/payment';
import { Button } from '@/components/ui/button';

const methods = ['mobile_money', 'card', 'cash', 'credit', 'crypto'];

export const PaymentSettings = () => {
  const { data, isLoading } = useGetPaymentSettingsQuery();
  const [updateSetting] = useUpdatePaymentSettingsMutation();
  const [enabled, setEnabled] = useState<Record<string, boolean>>({});

  useEffect(() => {
    if (data?.methods) {
      const map: Record<string, boolean> = {};
      methods.forEach((m) => (map[m] = data.methods.includes(m)));
      setEnabled(map);
    }
  }, [data]);

  const save = async () => {
    const selected = methods.filter((m) => enabled[m]);
    try {
      await updateSetting({ key: 'payment-settings', body: { methods: selected } }).unwrap();
      alert('Payment settings saved');
    } catch (e) {
      console.error(e);
      alert('Save failed');
    }
  };

  if (isLoading) return <p>Loading...</p>;

  return (
    <div className='space-y-4'>
      <h3 className='text-lg font-semibold'>Payment Methods</h3>
      <div className='grid gap-2'>
        {methods.map((m) => (
          <label key={m} className='inline-flex items-center gap-2'>
            <input type='checkbox' checked={!!enabled[m]} onChange={() => setEnabled((s) => ({ ...s, [m]: !s[m] }))} />
            <span className='capitalize'>{m.replace('_', ' ')}</span>
          </label>
        ))}
      </div>
      <div>
        <Button onClick={save}>Save</Button>
      </div>
    </div>
  );
};

export default PaymentSettings;
