// components/notifications/NotificationItem.tsx
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { formatDistanceToNow } from 'date-fns';
import { AlertTriangle, Bell, CheckCheck, MailCheck, Package, ShoppingCart, Trash2, Users } from 'lucide-react';

const iconMap: Record<string, React.ElementType> = {
  low_stock: Package,
  new_sale: ShoppingCart,
  new_purchase: Package,
  payment_received: Bell,
  overdue_payment: AlertTriangle,
  attendance: Users,
  default: Bell,
};

interface NotificationItemProps {
  notification: any;
  onMarkAsRead: (id: number) => void;
  onDelete: (id: number) => void;
}

export const NotificationItem = ({ notification, onMarkAsRead, onDelete }: NotificationItemProps) => {
  const Icon = iconMap[notification.type] || iconMap.default;

  return (
    <div
      className={`flex items-start justify-between rounded-lg border p-4 transition hover:bg-muted/40 ${
        !notification.is_read ? 'bg-muted/30 border-l-4 border-l-yellow-500' : ''
      }`}
    >
      <div className='flex gap-4'>
        {/* Icon */}
        <div className='flex h-11 w-11 items-center justify-center rounded-full bg-muted'>
          <Icon className='h-5 w-5 text-muted-foreground' />
        </div>

        {/* Content */}
        <div className='space-y-1 flex-1'>
          <div className='flex items-center gap-2'>
            <h3 className='font-medium'>{notification.title}</h3>
            <Badge variant='secondary' className='text-xs'>
              {notification.type.replace('_', ' ')}
            </Badge>
            {!notification.is_read && (
              <Badge variant='default' className='text-xs bg-yellow-500 hover:bg-yellow-600'>
                New
              </Badge>
            )}
          </div>

          <p className='text-sm text-muted-foreground leading-relaxed'>{notification.message}</p>

          <span className='text-xs text-muted-foreground'>
            {formatDistanceToNow(new Date(notification.created_at), { addSuffix: true })}
          </span>
        </div>
      </div>

      {/* Actions */}
      <div className='flex flex-col gap-2 ml-4'>
        {!notification.is_read && (
          <Button size='sm' variant='ghost' onClick={() => onMarkAsRead(notification.id)} className='text-xs'>
            <CheckCheck />
            Mark as read
          </Button>
        )}

        <Button
          size='sm'
          variant='ghost'
          onClick={() => onDelete(notification.id)}
          className='text-destructive hover:text-destructive'
        >
          <Trash2 className='h-4 w-4' />
        </Button>
      </div>
    </div>
  );
};
