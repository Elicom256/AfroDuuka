import { MessageCircle } from 'lucide-react';
import { OperationsPageShell, SectionCard } from './components/Operations-page-shell';
import { useGetNotificationsQuery } from '@/app/store/features/branch/notifications/notificationsQuery';
import { resolveList } from './components/Operations-page-utils';

export const OperationsMessagesPage = () => {
  // Messaging has no backend module yet, so the branch inbox is driven by the
  // notification feed until a messages endpoint exists.
  const { data, isLoading } = useGetNotificationsQuery();
  const messages = resolveList(data, 'notifications');
  const unreadCount = messages.filter((msg: any) => !msg.is_read).length;

  return (
    <div className='space-y-6'>
      <OperationsPageShell title='Messages' description='Read and manage branch-level messages and requests.'>
        <div className='grid gap-4 md:grid-cols-3'>
          <SectionCard title='Unread messages' value={unreadCount} icon={<MessageCircle className='h-5 w-5' />} />
          <SectionCard title='Total messages' value={messages.length} icon={<MessageCircle className='h-5 w-5' />} />
          <SectionCard
            title='Latest sender'
            value={messages[0]?.sender ?? 'No sender'}
            icon={<MessageCircle className='h-5 w-5' />}
          />
        </div>
        <div className='space-y-3'>
          {messages.length === 0 ? (
            <p className='text-sm text-muted-foreground'>No messages have arrived for this branch yet.</p>
          ) : (
            messages.slice(0, 6).map((message: any) => (
              <div
                key={message.id ?? message.subject}
                className='rounded-3xl border border-border/70 bg-background p-4'
              >
                <p className='font-semibold'>{message.subject ?? 'Message subject'}</p>
                <p className='text-sm text-muted-foreground'>{message.sender ?? 'Unknown sender'}</p>
              </div>
            ))
          )}
        </div>
      </OperationsPageShell>
    </div>
  );
};
