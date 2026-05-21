import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { MessageSquare, Search, Send, Inbox, Bell, Users } from 'lucide-react';

export const AdminMessagesPage = () => {
  return (
    <div className='space-y-6 p-6'>
      {/* Header */}
      <div className='flex flex-col gap-4 md:flex-row md:items-center md:justify-between'>
        <div>
          <h1 className='text-3xl font-bold tracking-tight'>Messages</h1>
          <p className='text-muted-foreground'>Manage customer, supplier, and staff communications.</p>
        </div>

        <div className='flex gap-2'>
          <Button variant='outline'>
            <Bell className='mr-2 h-4 w-4' />
            Notifications
          </Button>

          <Button>
            <Send className='mr-2 h-4 w-4' />
            New Message
          </Button>
        </div>
      </div>

      {/* Stats */}
      <div className='grid gap-4 md:grid-cols-2 xl:grid-cols-4'>
        <Card>
          <CardContent className='flex items-center justify-between p-6'>
            <div>
              <p className='text-sm text-muted-foreground'>Inbox</p>
              <h2 className='text-3xl font-bold'>128</h2>
            </div>

            <Inbox className='h-10 w-10 text-muted-foreground' />
          </CardContent>
        </Card>

        <Card>
          <CardContent className='flex items-center justify-between p-6'>
            <div>
              <p className='text-sm text-muted-foreground'>Unread</p>
              <h2 className='text-3xl font-bold'>19</h2>
            </div>

            <MessageSquare className='h-10 w-10 text-muted-foreground' />
          </CardContent>
        </Card>

        <Card>
          <CardContent className='flex items-center justify-between p-6'>
            <div>
              <p className='text-sm text-muted-foreground'>Staff Chats</p>
              <h2 className='text-3xl font-bold'>42</h2>
            </div>

            <Users className='h-10 w-10 text-muted-foreground' />
          </CardContent>
        </Card>

        <Card>
          <CardContent className='flex items-center justify-between p-6'>
            <div>
              <p className='text-sm text-muted-foreground'>Announcements</p>
              <h2 className='text-3xl font-bold'>7</h2>
            </div>

            <Bell className='h-10 w-10 text-muted-foreground' />
          </CardContent>
        </Card>
      </div>

      {/* Main Layout */}
      <div className='grid gap-6 lg:grid-cols-3'>
        {/* Conversations */}
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
            {[1, 2, 3, 4, 5].map((item) => (
              <div
                key={item}
                className='flex cursor-pointer items-start gap-3 rounded-lg border p-3 transition hover:bg-muted/50'
              >
                <div className='flex h-10 w-10 items-center justify-center rounded-full bg-muted'>
                  <Users className='h-5 w-5' />
                </div>

                <div className='flex-1'>
                  <div className='flex items-center justify-between'>
                    <h4 className='font-medium'>Placeholder User</h4>
                    <span className='text-xs text-muted-foreground'>2m ago</span>
                  </div>

                  <p className='line-clamp-1 text-sm text-muted-foreground'>This is a placeholder message preview...</p>
                </div>
              </div>
            ))}
          </CardContent>
        </Card>

        {/* Chat Area */}
        <Card className='lg:col-span-2'>
          <CardHeader>
            <CardTitle>Chat Preview</CardTitle>
            <CardDescription>Select a conversation to view messages</CardDescription>
          </CardHeader>

          <CardContent>
            <div className='flex min-h-[500px] flex-col items-center justify-center rounded-lg border border-dashed'>
              <MessageSquare className='mb-4 h-14 w-14 text-muted-foreground' />

              <h3 className='text-lg font-semibold'>No conversation selected</h3>

              <p className='mt-2 text-sm text-muted-foreground'>Choose a conversation from the left panel.</p>

              <Button className='mt-6'>
                <Send className='mr-2 h-4 w-4' />
                Start Conversation
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
};
