import { useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { format } from 'date-fns';
import { AlertTriangle, ArrowLeft, Bell, CheckCheck, Package, ShoppingCart, Users } from 'lucide-react';
import { toast } from 'sonner';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import {
  useGetNotificationQuery,
  useMarkAsReadMutation,
} from '@/app/store/features/branch/notifications/notificationsQuery';
import {
  notificationRouteForType,
  notificationTypeLabel,
} from '../../admin/components/notifications/notificationUtils';

const iconMap: Record<string, typeof Bell> = {
  low_stock: Package,
  new_sale: ShoppingCart,
  new_purchase: Package,
  payment_received: Bell,
  overdue_payment: AlertTriangle,
  attendance: Users,
};

export const NotificationDetailPage = ({ scope }: { scope: 'admin' | 'manager' }) => {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const { data, isLoading, isError } = useGetNotificationQuery(id ?? '', { skip: !id });
  const [markAsRead] = useMarkAsReadMutation();
  const notification = data?.notification ?? data;
  const Icon = iconMap[notification?.type] ?? Bell;

  useEffect(() => {
    if (notification && !notification.is_read) {
      markAsRead(notification.id).catch(() => toast.error('Failed to mark notification as read'));
    }
  }, [notification, markAsRead]);

  if (isLoading) {
    return (
      <div className='space-y-4 p-6'>
        <Skeleton className='h-10 w-48' />
        <Skeleton className='h-80 w-full' />
      </div>
    );
  }

  if (isError || !notification) {
    return (
      <div className='space-y-4 p-6'>
        <Button variant='ghost' onClick={() => navigate(`/${scope}/notifications`)}>
          <ArrowLeft className='mr-2 h-4 w-4' />
          Back to notifications
        </Button>
        <Card>
          <CardContent className='py-16 text-center text-muted-foreground'>
            This notification could not be found.
          </CardContent>
        </Card>
      </div>
    );
  }

  const relatedRoute = notificationRouteForType(notification.type, scope);

  return (
    <div className='space-y-6 p-6'>
      <Button variant='ghost' className='-ml-3' onClick={() => navigate(`/${scope}/notifications`)}>
        <ArrowLeft className='mr-2 h-4 w-4' />
        Back to notifications
      </Button>

      <Card className='overflow-hidden border-border/70'>
        <div className='h-2 bg-primary' />
        <CardHeader className='space-y-6 pb-4'>
          <div className='flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between'>
            <div className='flex gap-4'>
              <div className='flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary'>
                <Icon className='h-7 w-7' />
              </div>
              <div className='space-y-2'>
                <div className='flex flex-wrap items-center gap-2'>
                  <Badge variant='secondary'>{notificationTypeLabel(notification.type)}</Badge>
                  {notification.is_read && (
                    <Badge variant='outline'>
                      <CheckCheck className='mr-1 h-3 w-3' />
                      Read
                    </Badge>
                  )}
                </div>
                <CardTitle className='text-2xl'>{notification.title}</CardTitle>
                <CardDescription>{format(new Date(notification.created_at), 'MMM d, yyyy · h:mm a')}</CardDescription>
              </div>
            </div>
          </div>
        </CardHeader>
        <CardContent className='space-y-6'>
          <div className='rounded-2xl bg-muted/50 p-5 text-sm leading-7'>{notification.message}</div>
          {relatedRoute && (
            <Button onClick={() => navigate(relatedRoute)}>
              Open related {notificationTypeLabel(notification.type).toLowerCase()}{' '}
              <ArrowLeft className='ml-2 h-4 w-4 rotate-180' />
            </Button>
          )}
        </CardContent>
      </Card>
    </div>
  );
};
