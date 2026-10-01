import { Badge } from '@/components/ui/badge';
import { ReportCard } from './ReportCard';
import { useBranchReportsQuery } from '@/app/store/features/branch';

type ScheduledReport = {
  id: number;
  name: string;
  type: string;
  description?: string | null;
  schedule?: string | null;
  is_active?: boolean;
};

/**
 * The business's scheduled report definitions (Report model), listed for viewing.
 * Data comes from the existing branchReports endpoint (GET /api/reports), which
 * ReportController@index now serves.
 */
export const ScheduledReports = () => {
  const { data, isLoading } = useBranchReportsQuery();
  const reports: ScheduledReport[] = data?.reports ?? [];

  return (
    <ReportCard title='Scheduled reports' loading={isLoading}>
      {reports.length === 0 ? (
        <p className='text-sm text-muted-foreground'>No scheduled reports yet.</p>
      ) : (
        <div className='space-y-3'>
          {reports.map((report) => (
            <div
              key={report.id}
              className='flex items-center justify-between gap-4 rounded-3xl border border-border/70 bg-background p-4'
            >
              <div className='min-w-0'>
                <p className='font-semibold truncate'>{report.name}</p>
                <p className='text-sm text-muted-foreground capitalize'>
                  {report.type}
                  {report.schedule ? ` · every ${report.schedule}` : ''}
                </p>
                {report.description ? (
                  <p className='text-sm text-muted-foreground truncate'>{report.description}</p>
                ) : null}
              </div>
              <Badge variant={report.is_active === false ? 'outline' : 'secondary'}>
                {report.is_active === false ? 'Paused' : 'Active'}
              </Badge>
            </div>
          ))}
        </div>
      )}
    </ReportCard>
  );
};

export default ScheduledReports;
