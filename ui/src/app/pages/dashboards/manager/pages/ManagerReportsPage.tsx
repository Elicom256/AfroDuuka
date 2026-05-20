import { FileText, TrendingUp } from 'lucide-react';
import { ManagerPageShell, SectionCard } from './components/manager-page-shell';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { useBranchReportsQuery } from '@/app/store/features/branch';
import { resolveList } from './components/manager-page-utils';

export const ManagerReportsPage = () => {
  const { data, isLoading } = useBranchReportsQuery();
  const reports = resolveList(data, 'reports');
  const hasReports = reports.length > 0;

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <ManagerPageShell title='Reports' description='Review branch reports and performance snapshots.'>
        <div className='grid gap-4 md:grid-cols-3'>
          <SectionCard title='Reports generated' value={reports.length} icon={<FileText className='h-5 w-5' />} />
          <SectionCard
            title='Ready reports'
            value={reports.filter((report: any) => report.status === 'Ready').length}
            icon={<TrendingUp className='h-5 w-5' />}
          />
          <SectionCard
            title='Draft reports'
            value={reports.filter((report: any) => report.status === 'Draft').length}
            icon={<FileText className='h-5 w-5' />}
          />
        </div>
        {hasReports ? (
          <div className='space-y-3'>
            {reports.slice(0, 6).map((report: any) => (
              <div key={report.id ?? report.title} className='rounded-3xl border border-border/70 bg-background p-4'>
                <p className='font-semibold'>{report.title ?? 'Branch report'}</p>
                <p className='text-sm text-muted-foreground'>{report.status ?? 'Unknown status'}</p>
              </div>
            ))}
          </div>
        ) : (
          <p className='text-sm text-muted-foreground'>
            No reports available yet. Create branch reports to analyze sales, inventory, and customer trends.
          </p>
        )}
      </ManagerPageShell>
    </div>
  );
};
