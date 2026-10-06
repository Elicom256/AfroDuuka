import { useEffect, useMemo, useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
  useGetActivityLogCategoriesQuery,
  useGetActivityLogsQuery,
  type ActivityLog,
} from '@/app/store/features/business/executive/activityLogQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { PaginationComponent } from '@/app/utils/Pagination';
import { Activity, Filter, RefreshCw, Search, ChevronDown, ChevronRight, Radio, ArrowRight } from 'lucide-react';
import { format } from 'date-fns';

const ALL_CATEGORIES = 'all';
const BUSINESS_CATEGORIES = 'business';

const LOG_NAME_COLORS: Record<string, string> = {
  auth: 'bg-blue-500/10 text-blue-600',
  permission: 'bg-purple-500/10 text-purple-600',
  settings: 'bg-amber-500/10 text-amber-600',
  data_export: 'bg-green-500/10 text-green-600',
  customer: 'bg-cyan-500/10 text-cyan-600',
  default: 'bg-gray-500/10 text-muted-foreground',
};

const CATEGORY_LABELS: Record<string, string> = {
  auth: 'Security (logins)',
};

const humanize = (value: string) =>
  value
    .replace(/[_-]+/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase());

const humanizeField = (field: string) =>
  field
    .replace(/[_-]+/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase())
    .replace(/\bId\b/g, 'ID');

const formatFieldValue = (value: unknown): string => {
  if (value === null || value === undefined) return '—';
  if (typeof value === 'boolean') return value ? 'Yes' : 'No';
  if (typeof value === 'object') {
    try {
      return JSON.stringify(value);
    } catch {
      return String(value);
    }
  }
  return String(value);
};

const ChangesDisplay = ({ log }: { log: ActivityLog }) => {
  const { before, after } = log.changes ?? { before: {}, after: {} };
  const beforeEntries = before ?? {};
  const afterEntries = after ?? {};

  const fields = Array.from(new Set([...Object.keys(beforeEntries), ...Object.keys(afterEntries)]));
  const changedFields = fields.filter(
    (field) => JSON.stringify(beforeEntries[field]) !== JSON.stringify(afterEntries[field]),
  );

  if (changedFields.length === 0) {
    return <span className='text-muted-foreground'>No changes recorded</span>;
  }

  return (
    <div className='space-y-1.5'>
      {changedFields.map((field) => (
        <div key={field} className='flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs'>
          <span className='font-medium text-muted-foreground'>{humanizeField(field)}</span>
          <span className='text-red-600 dark:text-red-400'>{formatFieldValue(beforeEntries[field])}</span>
          <ArrowRight className='h-3 w-3 text-muted-foreground' />
          <span className='text-green-600 dark:text-green-400'>{formatFieldValue(afterEntries[field])}</span>
        </div>
      ))}
    </div>
  );
};

export interface ActivityLogPageProps {
  /** Supervisors see every actor in the business, not just their own entries. */
  scope?: 'personal' | 'business';
  title?: string;
  subtitle?: string;
  /** Poll for new entries on an interval. */
  live?: boolean;
}

export const ActivityLogPage = ({
  scope = 'personal',
  title = 'My Activity',
  subtitle = 'Track your actions and changes',
  live = false,
}: ActivityLogPageProps) => {
  // Business scope (executives) defaults to "All business" — auth logins are hidden
  // behind the explicit "Security (logins)" category. Personal scope shows everything.
  const [category, setCategory] = useState<string>(scope === 'business' ? BUSINESS_CATEGORIES : ALL_CATEGORIES);
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');
  const [searchInput, setSearchInput] = useState('');
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [isLive, setIsLive] = useState(live);
  const [expandedRows, setExpandedRows] = useState<Set<number>>(new Set());

  // Debounce the free-text filter so each keystroke does not hit the API.
  useEffect(() => {
    const timer = setTimeout(() => {
      setSearch(searchInput);
      setPage(1);
    }, 350);
    return () => clearTimeout(timer);
  }, [searchInput]);

  // Changing a filter invalidates the current page offset.
  const applyFilter = <T,>(setter: (value: T) => void) => (value: T) => {
    setter(value);
    setPage(1);
  };

  const filters = useMemo(
    () => ({
      // Omit the sentinel entirely rather than filtering on a literal "all".
      log_name: category === ALL_CATEGORIES ? undefined : category,
      date_from: dateFrom || undefined,
      date_to: dateTo || undefined,
      search: search || undefined,
      page,
      per_page: 20,
    }),
    [category, dateFrom, dateTo, search, page]
  );

  const { data, isLoading, isFetching, isError, refetch } = useGetActivityLogsQuery(filters, {
    pollingInterval: isLive ? 10_000 : 0,
  });
  const { data: categories } = useGetActivityLogCategoriesQuery();

  const logs = data?.data ?? [];
  const total = data?.meta?.total ?? 0;
  const lastPage = data?.meta?.last_page ?? 1;
  const showActorColumn = scope === 'business';

  const toggleRow = (id: number) => {
    setExpandedRows((prev) => {
      const next = new Set(prev);
      if (next.has(id)) {
        next.delete(id);
      } else {
        next.add(id);
      }
      return next;
    });
  };

  const categoryOptions = categories?.data?.length
    ? categories.data
    : Object.keys(LOG_NAME_COLORS).filter((key) => key !== 'default');

  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-2xl font-bold'>{title}</h1>
        <p className='text-muted-foreground'>{subtitle}</p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle className='flex items-center gap-2'>
            <Filter className='h-4 w-4' />
            Filters
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className='flex flex-wrap gap-3'>
            <Select value={category} onValueChange={applyFilter(setCategory)}>
              <SelectTrigger className='w-48'>
                <SelectValue placeholder='All categories' />
              </SelectTrigger>
              <SelectContent>
                {scope === 'business' && (
                  <SelectItem value={BUSINESS_CATEGORIES}>All business</SelectItem>
                )}
                <SelectItem value={ALL_CATEGORIES}>All</SelectItem>
                {categoryOptions.map((name) => (
                  <SelectItem key={name} value={name}>
                    {CATEGORY_LABELS[name] ?? humanize(name)}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <Input
              type='date'
              value={dateFrom}
              onChange={(e) => applyFilter(setDateFrom)(e.target.value)}
              className='w-40'
            />
            <Input type='date' value={dateTo} onChange={(e) => applyFilter(setDateTo)(e.target.value)} className='w-40' />
            <div className='relative min-w-48 flex-1'>
              <Search className='absolute left-3 top-2.5 h-4 w-4 text-muted-foreground' />
              <Input
                value={searchInput}
                onChange={(e) => setSearchInput(e.target.value)}
                className='pl-9'
                placeholder='Search description...'
              />
            </div>
            <Button variant='outline' size='sm' onClick={() => refetch()} disabled={isFetching}>
              <RefreshCw className={`h-4 w-4 ${isFetching ? 'animate-spin' : ''}`} />
            </Button>
            {live && (
              <Button variant={isLive ? 'default' : 'outline'} size='sm' onClick={() => setIsLive((prev) => !prev)}>
                <Radio className={`h-4 w-4 ${isLive ? 'animate-pulse' : ''}`} />
                {isLive ? 'Live' : 'Go Live'}
              </Button>
            )}
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className='flex items-center gap-2'>
            <Activity className='h-4 w-4' />
            Activity
          </CardTitle>
          <CardDescription>
            {total} event{total === 1 ? '' : 's'}
          </CardDescription>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <PageLoadingState />
          ) : isError ? (
            <p className='py-8 text-center text-sm text-destructive'>Could not load activity logs. Please try again.</p>
          ) : logs.length === 0 ? (
            <p className='py-8 text-center text-sm text-muted-foreground'>No activity found.</p>
          ) : (
            <>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead className='w-8'></TableHead>
                    <TableHead>Category</TableHead>
                    <TableHead>Description</TableHead>
                    {showActorColumn && <TableHead>User</TableHead>}
                    <TableHead>IP Address</TableHead>
                    <TableHead>Date</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {logs.map((log) => (
                    <ActivityLogRows
                      key={log.id}
                      log={log}
                      expanded={expandedRows.has(log.id)}
                      onToggle={() => toggleRow(log.id)}
                      showActorColumn={showActorColumn}
                    />
                  ))}
                </TableBody>
              </Table>

              <div className='mt-4'>
                <PaginationComponent currentPage={page} totalPages={lastPage} onPageChange={setPage} showLabel />
              </div>
            </>
          )}
        </CardContent>
      </Card>
    </div>
  );
};

const ActivityLogRows = ({
  log,
  expanded,
  onToggle,
  showActorColumn,
}: {
  log: ActivityLog;
  expanded: boolean;
  onToggle: () => void;
  showActorColumn: boolean;
}) => {
  const columnCount = showActorColumn ? 6 : 5;

  return (
    <>
      <TableRow>
        <TableCell>
          <Button variant='ghost' size='sm' onClick={onToggle} aria-label={expanded ? 'Collapse details' : 'Expand details'}>
            {expanded ? <ChevronDown className='h-4 w-4' /> : <ChevronRight className='h-4 w-4' />}
          </Button>
        </TableCell>
        <TableCell>
          <Badge variant='outline' className={LOG_NAME_COLORS[log.log_name ?? ''] ?? LOG_NAME_COLORS.default}>
            {log.log_name ? humanize(log.log_name) : 'Uncategorised'}
          </Badge>
        </TableCell>
        <TableCell className='max-w-xs truncate'>{log.description ?? '-'}</TableCell>
        {showActorColumn && <TableCell>{log.causer.name ?? 'System'}</TableCell>}
        <TableCell className='text-sm text-muted-foreground'>{log.ip_address ?? '-'}</TableCell>
        <TableCell className='text-sm text-muted-foreground'>
          {log.created_at ? format(new Date(log.created_at), 'PPp') : '-'}
        </TableCell>
      </TableRow>

      {expanded && (
        <TableRow>
          <TableCell colSpan={columnCount} className='bg-muted/30'>
            <div className='space-y-4 p-4'>
              <div>
                <p className='mb-1 text-xs font-medium text-muted-foreground'>Event</p>
                <p className='text-xs'>{log.event ? humanize(log.event) : '-'}</p>
              </div>
              <div>
                <p className='mb-1 text-xs font-medium text-muted-foreground'>User Agent</p>
                <p className='text-xs'>{log.user_agent ?? '-'}</p>
              </div>
              <div>
                <p className='mb-1 text-xs font-medium text-muted-foreground'>Changes</p>
                <ChangesDisplay log={log} />
              </div>
              <div>
                <p className='mb-1 text-xs font-medium text-muted-foreground'>Subject</p>
                <p className='text-xs'>{log.subject.label ?? '-'}</p>
              </div>
              <div>
                <p className='mb-1 text-xs font-medium text-muted-foreground'>Causer</p>
                <p className='text-xs'>{log.causer.name ?? '-'}</p>
              </div>
            </div>
          </TableCell>
        </TableRow>
      )}
    </>
  );
};
