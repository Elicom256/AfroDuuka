import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Settings, Shield, Bell, Globe } from 'lucide-react';

const settingsSections = [
  {
    title: 'General',
    description: 'System-wide settings and preferences',
    icon: Settings,
    items: [
      { label: 'System Name', value: 'DuukaFlow' },
      { label: 'Platform Status', value: 'Operational' },
    ],
  },
  {
    title: 'Security',
    description: 'Security and access control',
    icon: Shield,
    items: [
      { label: 'Super Admin Email', value: 'superadmin@gmail.com' },
    ],
  },
  {
    title: 'Notifications',
    description: 'Notification preferences',
    icon: Bell,
    items: [
      { label: 'Payment Alerts', value: 'Enabled' },
      { label: 'New Business Alerts', value: 'Enabled' },
    ],
  },
  {
    title: 'Regional',
    description: 'Regional and localization settings',
    icon: Globe,
    items: [
      { label: 'Default Currency', value: 'UGX' },
      { label: 'Timezone', value: 'Africa/Kampala' },
    ],
  },
];

export const SuperAdminSettingsPage = () => {
  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-3xl font-bold tracking-tight flex items-center gap-3'>
          <Settings className='h-8 w-8' />
          Settings
        </h1>
        <p className='text-muted-foreground mt-1'>System configuration and preferences</p>
      </div>

      <div className='grid gap-6 md:grid-cols-2'>
        {settingsSections.map((section) => {
          const Icon = section.icon;
          return (
            <Card key={section.title}>
              <CardHeader>
                <div className='flex items-center gap-3'>
                  <div className='flex h-10 w-10 items-center justify-center rounded-2xl bg-primary/10 text-primary'>
                    <Icon className='h-5 w-5' />
                  </div>
                  <div>
                    <CardTitle className='text-lg'>{section.title}</CardTitle>
                    <CardDescription>{section.description}</CardDescription>
                  </div>
                </div>
              </CardHeader>
              <CardContent>
                <dl className='space-y-3'>
                  {section.items.map((item) => (
                    <div key={item.label} className='flex items-center justify-between'>
                      <dt className='text-sm text-muted-foreground'>{item.label}</dt>
                      <dd className='text-sm font-medium'>{item.value}</dd>
                    </div>
                  ))}
                </dl>
              </CardContent>
            </Card>
          );
        })}
      </div>
    </div>
  );
};
