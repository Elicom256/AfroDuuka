import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Bell, AlertTriangle, Package, ShoppingCart } from 'lucide-react';

interface StatsTypes {
  notifications: any[];
  unreadCount: number;
  unreadByType?: Record<string, number>;
}

export const StatsCard = ({ notifications, unreadCount, unreadByType = {} }: StatsTypes) => {
  const stats: Array<{
    label: string;
    value: number;
    icon: any;
    className: string;
    valueClass?: string;
    badge?: number;
  }> = [
    {
      label: 'Total',
      value: notifications.length,
      icon: Bell,
      className: 'text-muted-foreground',
    },
    {
      label: 'Unread',
      value: unreadCount,
      icon: AlertTriangle,
      className: 'text-yellow-500',
      valueClass: 'text-yellow-600',
    },
    {
      label: 'Stock Alerts',
      value: notifications.filter((n: any) => n.type === 'low_stock').length,
      icon: Package,
      className: 'text-muted-foreground',
      badge: unreadByType?.['low_stock'] ?? 0,
    },
    {
      label: 'Sales',
      value: notifications.filter((n: any) => n.type === 'new_sale').length,
      icon: ShoppingCart,
      className: 'text-muted-foreground',
      badge: unreadByType?.['new_sale'] ?? 0,
    },
  ];

  return (
    <div className='grid gap-4 md:grid-cols-4 xl:grid-cols-4'>
      {stats.map((stat, idx) => {
        const Icon = stat.icon;
        return (
          <Card key={idx} className='rounded-lg shadow-sm p-0 sm:p-2'>
            <CardContent className='flex items-center justify-between p-2'>
              <div>
                <p className='text-sm text-muted-foreground'>{stat.label}</p>
                <h2 className={`text-xl font-semibold ${stat.valueClass || ''}`}>{stat.value}</h2>
              </div>
              <div className='flex flex-col items-end gap-1'>
                <Icon className={`h-6 w-6 ${stat.className}`} />
                {(stat.badge ?? 0) > 0 && (
                  <Badge variant='destructive' className='text-[10px] px-1.5 py-0'>
                    {stat.badge} new
                  </Badge>
                )}
              </div>
            </CardContent>
          </Card>
        );
      })}
    </div>
  );
};