import { useMemo, useState } from 'react';
import { toast } from 'sonner';
import ReportCard from './ReportCard';
import { Button } from '@/components/ui/button';
import { useCurrency } from '@/app/hooks/useCurrency';
import { useBranchesQuery } from '@/app/store/features/business/branches/branchesQuery';
import {
  useMonthlyPerformancePdfMutation,
  useMonthlyPerformanceQuery,
} from '@/app/store/features/branch/reports/branchReportsQuery';

const MONTHS_OFFERED = 24;

const monthKey = (date: Date) =>
  `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;

/**
 * The last MONTHS_OFFERED completed months, newest first.
 *
 * The current month is excluded on purpose. It is still accumulating, so its totals
 * understate it, and offering it next to a closed month invites the reader to compare
 * a finished period against a partial one and read the gap as a collapse in trade.
 */
const recentMonths = (count = MONTHS_OFFERED): { value: string; label: string }[] => {
  const months: { value: string; label: string }[] = [];
  const cursor = new Date();

  cursor.setDate(1);
  cursor.setMonth(cursor.getMonth() - 1);

  for (let i = 0; i < count; i++) {
    months.push({
      value: monthKey(cursor),
      label: cursor.toLocaleDateString('en-US', { month: 'long', year: 'numeric' }),
    });
    cursor.setMonth(cursor.getMonth() - 1);
  }

  return months;
};

const MONTHS = recentMonths();

/**
 * The branches query is untyped (`builder.query<any, void>`), so the shape it actually
 * returns is pinned here instead of being taken on trust. Spelled out locally rather than
 * by typing the shared endpoint, which would put this change in front of every consumer
 * of the branches API.
 */
type BranchOption = { id: number | string; name: string };

const slug = (value: string) =>
  value
    .replace(/[^a-z0-9]+/gi, '-')
    .replace(/^-|-$/g, '')
    .toLowerCase();

/**
 * Must produce exactly what MonthlyReportPdf::filename() produces.
 *
 * The anchor element's download attribute wins over Content-Disposition, so a browser
 * never sees the header the server carefully set. If the two disagree the user gets a
 * file called "monthly-report-august-2026.pdf" for a branch, which is the same filename
 * for every branch of the business — so the branch has to be in it here too.
 */
const fileNameFor = (branchName: string, period: string) =>
  ['monthly-report', slug(branchName), slug(period)].filter(Boolean).join('-') + '.pdf';

/**
 * The monthly performance report, and the same document the notification email attaches.
 *
 * Figures come from the API on demand rather than from a stored email, so what is
 * downloaded here is the report as it stands. The emailed copy is a snapshot frozen when
 * the notification was reserved, which is the right thing for a record of what was sent
 * and the wrong thing for someone who wants the current numbers.
 *
 * Every branch has its own report, so the branch is a required choice rather than a
 * filter: there is no company-wide document to fall back on, and offering one would put
 * a blended total in front of the branch manager who has to act on it.
 */
export const MonthlyPerformanceReport = () => {
  const { currency: fallbackCurrency } = useCurrency();
  const [month, setMonth] = useState<string>(MONTHS[0].value);
  const [branchId, setBranchId] = useState<string>('');

  const { data: branchesData } = useBranchesQuery();
  const branches = useMemo<BranchOption[]>(
    () => branchesData?.branches || [],
    [branchesData]
  );

  // Defaults to the first branch once the list arrives. Unlike the comparison card there
  // is no "all branches" view to preserve here, so there is nothing for a default to
  // destroy.
  const activeBranchId = branchId || (branches[0] ? String(branches[0].id) : '');

  // Skipped until a branch is known: firing with an empty branch_id would come back 422,
  // because the API has no whole-company document to serve.
  const { data, isLoading, isError } = useMonthlyPerformanceQuery(
    { month, branchId: activeBranchId },
    { skip: !activeBranchId }
  );

  // Lazy, so the PDF is fetched on click rather than with the page. A report is a few
  // hundred KB of PDF and nobody who came to read the figures asked for that.
  const [fetchPdf, { isLoading: isDownloading }] = useMonthlyPerformancePdfMutation();

  const report = data?.data;
  const currency = report?.currency || fallbackCurrency || 'UGX';
  const pending = !activeBranchId || isLoading;

  const figures = useMemo(
    () =>
      [
        { label: 'Total sales', value: report?.figures.sales, tone: 'text-emerald-600' },
        { label: 'Total purchases', value: report?.figures.purchases, tone: 'text-amber-600' },
        { label: 'Total expenses', value: report?.figures.expenses, tone: 'text-red-600' },
      ] as const,
    [report]
  );

  const formatMoney = (value: number | null | undefined) => {
    if (value === null || value === undefined) {
      return '—';
    }

    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency,
      minimumFractionDigits: 2,
    }).format(value);
  };

  const download = async () => {
    try {
      const blob = await fetchPdf({ month, branchId: activeBranchId }).unwrap();

      if (!(blob instanceof Blob)) {
        throw new Error('Unexpected response');
      }

      // Revoked straight after the click: an object URL pins the whole PDF in memory
      // until the document unloads, and this one is disposable.
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');

      link.href = url;
      link.download = report
        ? fileNameFor(report.branch_name, report.period)
        : `monthly-report-${month}.pdf`;
      link.click();
      URL.revokeObjectURL(url);
    } catch {
      toast.error('Could not prepare the PDF. Please try again.');
    }
  };

  const profit = report?.figures.profit_loss;

  return (
    <ReportCard title='Monthly Performance Report' loading={pending}>
      <div className='flex flex-wrap items-center justify-between gap-4 mb-6'>
        <div className='flex flex-wrap items-center gap-3'>
          <div className='flex items-center gap-3'>
            <label className='text-sm text-muted-foreground' htmlFor='monthly-report-branch'>
              Branch:
            </label>
            <select
              id='monthly-report-branch'
              className='rounded border px-3 py-1.5 text-sm bg-background min-w-[180px]'
              value={activeBranchId}
              onChange={(e) => setBranchId(e.target.value)}
              disabled={branches.length === 0}
            >
              {branches.map((branch) => (
                <option key={branch.id} value={String(branch.id)}>
                  {branch.name}
                </option>
              ))}
            </select>
          </div>

          <div className='flex items-center gap-3'>
            <label className='text-sm text-muted-foreground' htmlFor='monthly-report-month'>
              Month:
            </label>
            <select
              id='monthly-report-month'
              className='rounded border px-3 py-1.5 text-sm bg-background min-w-[180px]'
              value={month}
              onChange={(e) => setMonth(e.target.value)}
            >
              {MONTHS.map((option) => (
                <option key={option.value} value={option.value}>
                  {option.label}
                </option>
              ))}
            </select>
          </div>
        </div>

        <Button size='sm' disabled={isDownloading || !activeBranchId} onClick={download}>
          {isDownloading ? 'Preparing…' : 'Download PDF'}
        </Button>
      </div>

      {isError && (
        <div className='text-center py-12 text-destructive'>
          Could not load the monthly report. Please try again.
        </div>
      )}

      {!isError && !report && !pending && (
        <div className='text-center py-12 text-muted-foreground'>No report data available.</div>
      )}

      {!isError && !report && pending && (
        <div className='text-center py-12 text-muted-foreground'>
          {branches.length === 0 ? 'This business has no branches to report on.' : 'Loading…'}
        </div>
      )}

      {report && (
        <div className='space-y-6'>
          <p className='text-sm text-muted-foreground'>
            Every figure below describes {report.branch_name} only.
          </p>

          <div className='grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4'>
            {figures.map((figure) => (
              <div key={figure.label} className='bg-card border rounded-xl p-5'>
                <p className='text-sm text-muted-foreground'>{figure.label}</p>
                <p className={`text-2xl font-semibold mt-2 ${figure.tone}`}>
                  {formatMoney(figure.value)}
                </p>
              </div>
            ))}

            <div className='bg-card border rounded-xl p-5'>
              <p className='text-sm text-muted-foreground'>
                {profit !== null && profit !== undefined && profit < 0 ? 'Loss' : 'Profit'}
              </p>
              <p
                className={`text-2xl font-semibold mt-2 ${
                  profit !== null && profit !== undefined && profit < 0 ? 'text-red-600' : 'text-emerald-600'
                }`}
              >
                {formatMoney(profit)}
              </p>
            </div>
          </div>

          <div className='flex flex-wrap gap-6 text-sm text-muted-foreground'>
            <span>{report.counts.sales ?? 0} sales recorded</span>
            <span>{report.counts.purchases ?? 0} purchases recorded</span>
          </div>

          <p className='text-xs text-muted-foreground'>
            The download is the same document the monthly summary email attaches.
          </p>
        </div>
      )}
    </ReportCard>
  );
};

export default MonthlyPerformanceReport;
