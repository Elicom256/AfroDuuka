import { History } from 'lucide-react';
import { ManagerPageShell, SectionCard } from './components/manager-page-shell';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { useBranchHistoryQuery } from '@/app/store/features/branch';
import { resolveList } from './components/manager-page-utils';

export const ManagerHistoryPage = () => {
  const { data, isLoading } = useBranchHistoryQuery();
  const history = resolveList(data, 'history');

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <ManagerPageShell title='History' description='Review recent system activity and branch events.'>
        <div className='grid gap-4 md:grid-cols-3'>
          <SectionCard title='History entries' value={history.length} icon={<History className='h-5 w-5' />} />
          <SectionCard
            title='Recent activity'
            value={history[0]?.description ?? 'No events'}
            icon={<History className='h-5 w-5' />}
          />
          <SectionCard title='Last update' value={history[0]?.time ?? 'N/A'} icon={<History className='h-5 w-5' />} />
        </div>
        <div className='space-y-3'>
          {history.length === 0 ? (
            <p className='text-sm text-muted-foreground'>No history events are available yet.</p>
          ) : (
            history.slice(0, 6).map((event: any) => (
              <div
                key={event.id ?? `${event.time}-${event.description}`}
                className='rounded-3xl border border-border/70 bg-background p-4'
              >
                <p className='font-semibold'>{event.description ?? 'History event'}</p>
                <p className='text-sm text-muted-foreground'>{event.time ?? event.date ?? ''}</p>
              </div>
            ))
          )}
        </div>
      </ManagerPageShell>
    </div>
  );
};
