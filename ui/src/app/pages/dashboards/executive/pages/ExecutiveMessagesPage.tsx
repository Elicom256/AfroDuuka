import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { MessageSquare, Search, Send, Inbox, Bell, Users } from 'lucide-react';
import { useBranchMessagesQuery } from '@/app/store/features/branch/messages/messagesQuery';
import { useGetNotificationsQuery, useGetUnreadCountQuery, useMarkAllAsReadMutation } from '@/app/store/features/branch/notifications/notificationsQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { format } from 'date-fns';

export const ExecutiveMessagesPage = () => {
  const { data: messagesData, isLoading: messagesLoading } = useBranchMessagesQuery();
  const { data: notificationsData, isLoading: notificationsLoading } = useGetNotificationsQuery();
  const { data: unreadData, isLoading: unreadLoading } = useGetUnreadCountQuery();
  const [markAllAsRead] = useMarkAllAsReadMutation();
  const [selectedConvo, setSelectedConvo] = useState<any>(null);

  const isLoading = messagesLoading || notificationsLoading || unreadLoading;

  if (isLoading) return <PageLoadingState />;

  const messages = messagesData?.messages ?? messagesData ?? [];
  const notifications = notificationsData?.notifications ?? notificationsData ?? [];
  const unreadCount = unreadData?.count ?? unreadData?.unread ?? notifications.filter((n: any) => !n.is_read).length;

  const conversations = messages.length > 0 ? messages : notifications.slice(0, 5);

  const stats = [
    { label: 'Inbox', value: String(notifications.length), icon: Inbox },
    { label: 'Unread', value: String(unreadCount), icon: MessageSquare },
    { label: 'Messages', value: String(messages.length), icon: Users },
    { label: 'Announcements', value: String(notifications.filter((n: any) => n.type === 'announcement').length), icon: Bell },
  ];

  return (
    <div className='space-y-6 p-6'>
      <div className='flex flex-col gap-4 md:flex-row md:items-center md:justify-between'>
        <div>
          <h1 className='text-3xl font-bold tracking-tight'>Messages</h1>
          <p className='text-muted-foreground'>Manage customer, supplier, and staff communications.</p>
        </div>

        <div className='flex gap-2'>
          <Button variant='outline' onClick={() => markAllAsRead()}>
            <Bell className='mr-2 h-4 w-4' />
            Mark All Read
          </Button>

          <Button>
            <Send className='mr-2 h-4 w-4' />
            New Message
          </Button>
        </div>
      </div>

      <div className='grid gap-4 md:grid-cols-2 xl:grid-cols-4'>
        {stats.map((stat) => (
          <Card key={stat.label}>
            <CardContent className='flex items-center justify-between p-6'>
              <div>
                <p className='text-sm text-muted-foreground'>{stat.label}</p>
                <h2 className='text-3xl font-bold'>{stat.value}</h2>
              </div>
              <stat.icon className='h-10 w-10 text-muted-foreground' />
            </CardContent>
          </Card>
        ))}
      </div>

      <div className='grid gap-6 lg:grid-cols-3'>
        <Card className='lg:col-span-1'>
          <CardHeader>
            <div className='flex items-center justify-between'>
              <div>
                <CardTitle>Conversations</CardTitle>
                <CardDescription>Recent chats and discussions</CardDescription>
              </div>

              <Button size='icon' variant='ghost'>
                <Search className='h-4 w-4' />
              </Button>
            </div>
          </CardHeader>

          <CardContent className='space-y-4'>
            {conversations.length === 0 ? (
              <p className='text-sm text-muted-foreground'>No conversations yet.</p>
            ) : (
              conversations.slice(0, 8).map((convo: any) => (
                <div
                  key={convo.id}
                  className={`flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition hover:bg-muted/50 ${selectedConvo?.id === convo.id ? 'border-primary bg-muted/50' : ''}`}
                  onClick={() => setSelectedConvo(convo)}
                >
                  <div className='flex h-10 w-10 items-center justify-center rounded-full bg-muted'>
                    <Users className='h-5 w-5' />
                  </div>

                  <div className='flex-1'>
                    <div className='flex items-center justify-between'>
                      <h4 className='font-medium'>{convo.sender?.name ?? convo.title ?? 'Message'}</h4>
                      <span className='text-xs text-muted-foreground'>
                        {convo.created_at ? format(new Date(convo.created_at), 'PP') : ''}
                      </span>
                    </div>

                    <p className='line-clamp-1 text-sm text-muted-foreground'>
                      {convo.message ?? convo.body ?? convo.content ?? 'No preview available'}
                    </p>
                  </div>
                </div>
              ))
            )}
          </CardContent>
        </Card>

        <Card className='lg:col-span-2'>
          <CardHeader>
            <CardTitle>{selectedConvo ? 'Conversation' : 'Chat Preview'}</CardTitle>
            <CardDescription>
              {selectedConvo ? 'View message details' : 'Select a conversation to view messages'}
            </CardDescription>
          </CardHeader>

          <CardContent>
            {selectedConvo ? (
              <div className='space-y-4'>
                <div className='rounded-lg border border-border/70 bg-muted p-4'>
                  <div className='flex items-center justify-between'>
                    <h4 className='font-semibold'>{selectedConvo.sender?.name ?? selectedConvo.title ?? 'Message'}</h4>
                    <Badge variant={selectedConvo.is_read ? 'secondary' : 'default'}>
                      {selectedConvo.is_read ? 'Read' : 'Unread'}
                    </Badge>
                  </div>
                  <p className='mt-2 text-sm text-muted-foreground'>
                    {selectedConvo.message ?? selectedConvo.body ?? selectedConvo.content ?? 'No content'}
                  </p>
                  {selectedConvo.created_at && (
                    <p className='mt-2 text-xs text-muted-foreground'>
                      {format(new Date(selectedConvo.created_at), 'PPp')}
                    </p>
                  )}
                </div>
              </div>
            ) : (
              <div className='flex min-h-[500px] flex-col items-center justify-center rounded-lg border border-dashed'>
                <MessageSquare className='mb-4 h-14 w-14 text-muted-foreground' />

                <h3 className='text-lg font-semibold'>No conversation selected</h3>

                <p className='mt-2 text-sm text-muted-foreground'>Choose a conversation from the left panel.</p>

                <Button className='mt-6'>
                  <Send className='mr-2 h-4 w-4' />
                  Start Conversation
                </Button>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
};
