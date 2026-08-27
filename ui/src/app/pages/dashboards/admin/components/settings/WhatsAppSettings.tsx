import { useEffect, useState } from 'react';

/**
 * WhatsApp business settings panel.
 * This is intentionally demo-safe: it gives the operator a real configuration screen and test flow,
 * while still keeping the app usable before a paid provider is activated.
 */
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { toast } from 'sonner';
import { MessageSquareText, Send, Save, Phone } from 'lucide-react';
import {
  useGetWhatsAppConfigQuery,
  useSaveWhatsAppConfigMutation,
  useTestWhatsAppMessageMutation,
} from '@/app/store/features/business/admin/whatsappQuery';

export const WhatsAppSettings = () => {
  const { data, isLoading } = useGetWhatsAppConfigQuery();
  const [saveConfig, { isLoading: isSaving }] = useSaveWhatsAppConfigMutation();
  const [testMessage, { isLoading: isTesting }] = useTestWhatsAppMessageMutation();

  const [form, setForm] = useState({
    business_phone: '+256731794401',
    provider: 'demo',
    phone_number_id: 'demo_phone_number_id',
    access_token: 'demo_access_token',
    webhook_verify_token: 'demo_verify_token',
    is_active: true,
    welcome_message: 'Welcome to DuukaFlow. This is a demo WhatsApp notification message.',
  });

  useEffect(() => {
    if (data?.data) {
      setForm((prev) => ({
        ...prev,
        ...data.data,
      }));
    }
  }, [data]);

  const handleSave = async () => {
    try {
      await saveConfig({
        ...form,
        business_id: data?.data?.business_id ?? undefined,
      }).unwrap();
      toast.success('WhatsApp settings saved');
    } catch (error: any) {
      toast.error(error?.data?.message || 'Failed to save WhatsApp settings');
    }
  };

  const handleTest = async () => {
    try {
      const result = await testMessage({
        recipient: form.business_phone,
        message: form.welcome_message,
      }).unwrap();
      toast.success(result?.message || 'Demo WhatsApp message sent');
    } catch (error: any) {
      toast.error(error?.data?.message || 'Failed to send demo WhatsApp message');
    }
  };

  if (isLoading) {
    return (
      <Card>
        <CardHeader>
          <CardTitle>WhatsApp Settings</CardTitle>
        </CardHeader>
        <CardContent className='space-y-3'>
          <div className='h-10 w-full animate-pulse rounded bg-muted' />
          <div className='h-10 w-full animate-pulse rounded bg-muted' />
        </CardContent>
      </Card>
    );
  }

  return (
    <Card>
      <CardHeader>
        <div className='flex items-center gap-3'>
          <MessageSquareText className='h-6 w-6 text-primary' />
          <div>
            <CardTitle>WhatsApp Settings</CardTitle>
            <CardDescription>Configure the business WhatsApp number and demo notification flow.</CardDescription>
          </div>
        </div>
      </CardHeader>

      <CardContent className='space-y-6'>
        <div className='grid grid-cols-1 gap-4 md:grid-cols-2'>
          <div className='space-y-2'>
            <Label>Provider</Label>
            <Input value={form.provider} onChange={(e) => setForm({ ...form, provider: e.target.value })} />
          </div>

          <div className='space-y-2'>
            <Label>Business WhatsApp Number</Label>
            <div className='flex items-center gap-2'>
              <Phone className='h-4 w-4 text-muted-foreground' />
              <Input value={form.business_phone} onChange={(e) => setForm({ ...form, business_phone: e.target.value })} />
            </div>
          </div>

          <div className='space-y-2'>
            <Label>Phone Number ID</Label>
            <Input value={form.phone_number_id} onChange={(e) => setForm({ ...form, phone_number_id: e.target.value })} />
          </div>

          <div className='space-y-2'>
            <Label>Access Token</Label>
            <Input value={form.access_token} onChange={(e) => setForm({ ...form, access_token: e.target.value })} />
          </div>

          <div className='space-y-2 md:col-span-2'>
            <Label>Webhook Verify Token</Label>
            <Input value={form.webhook_verify_token} onChange={(e) => setForm({ ...form, webhook_verify_token: e.target.value })} />
          </div>

          <div className='space-y-2 md:col-span-2'>
            <Label>Welcome Message</Label>
            <Input value={form.welcome_message} onChange={(e) => setForm({ ...form, welcome_message: e.target.value })} />
          </div>
        </div>

        <div className='flex items-center justify-between rounded-lg border p-3'>
          <div>
            <p className='font-medium'>Enable WhatsApp notifications</p>
            <p className='text-sm text-muted-foreground'>Demo mode is active until a paid API is connected.</p>
          </div>
          <Switch checked={form.is_active} onCheckedChange={(checked) => setForm({ ...form, is_active: checked })} />
        </div>

        <div className='flex gap-3'>
          <Button onClick={handleSave} disabled={isSaving}>
            <Save className='mr-2 h-4 w-4' />
            {isSaving ? 'Saving...' : 'Save Settings'}
          </Button>

          <Button variant='outline' onClick={handleTest} disabled={isTesting}>
            <Send className='mr-2 h-4 w-4' />
            {isTesting ? 'Sending...' : 'Send Demo Message'}
          </Button>
        </div>
      </CardContent>
    </Card>
  );
};
