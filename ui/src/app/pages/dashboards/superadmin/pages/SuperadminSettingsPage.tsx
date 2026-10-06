import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Settings, Shield, Bell, Globe } from 'lucide-react';
import { useGetBusinessQuery } from '@/app/store/features/business/setup/businessQuery';
import { useCurrency } from '@/app/hooks/useCurrency';

/**
 * Platform settings, read from the platform rather than restated here.
 *
 * This page held a `settingsSections` array of literals — System Name 'DuukaFlow',
 * Platform Status 'Operational', a support address, currency, timezone. Each was a claim
 * about the running install, written once in a component, with no connection to the value
 * it claimed to show (checked.md P2). If the platform were suspended, or its support
 * address changed, or it served a business in another country, this page would still say
 * "Operational" and "UGX". Anything with no stored value now says so instead.
 */


export const SuperadminSettingsPage = () => {
  const { data, isLoading } = useGetBusinessQuery();
  const { currency, countryName } = useCurrency();

  const business = data?.data?.business ?? data?.business ?? data;
  const loading = isLoading && !business;
  const value = (present: unknown) =>
    present ? String(present) : loading ? 'Loading…' : 'Not configured';

  const settingsSections = [
    {
      title: 'General',
      description: 'System-wide settings and preferences',
      icon: Settings,
      items: [
        { label: 'System Name', value: value(business?.name) },
        { label: 'Platform Status', value: value(business?.status) },
      ],
    },
    {
      title: 'Security',
      description: 'Security and access control',
      icon: Shield,
      items: [{ label: 'Core Support Email', value: value(business?.email) }],
    },
    {
      title: 'Notifications',
      description: 'Notification preferences',
      icon: Bell,
      items: [
        { label: 'Payment Alerts', value: 'Not configurable yet' },
        { label: 'New Business Alerts', value: 'Not configurable yet' },
      ],
    },
    {
      title: 'Regional',
      description: 'Regional and localization settings',
      icon: Globe,
      items: [
        { label: 'Default Currency', value: value(currency) },
        { label: 'Timezone', value: value(business?.timezone) },
        { label: 'Country', value: value(countryName) },
      ],
    },
  ];

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
