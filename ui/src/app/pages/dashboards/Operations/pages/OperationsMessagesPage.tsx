import { MessageCircle } from 'lucide-react';
import { OperationsPageShell, SectionCard } from './components/Operations-page-shell';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { useBranchMessagesQuery } from '@/app/store/features/branch';
import { resolveList } from './components/Operations-page-utils';

export const OperationsMessagesPage = () => {
  const { data, isLoading } = useBranchMessagesQuery();
  const messages = resolveList(data, 'messages');
  const unreadCount = messages.filter((msg: any) => !msg.read).length;

  if (isLoading) return <PageLoadingState />;

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
