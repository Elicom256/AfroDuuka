import { useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useGetActivityLogsQuery } from '@/app/store/features/business/executive/activityLogQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { Activity, Filter, RefreshCw, Search } from 'lucide-react';
import { format } from 'date-fns';

const LOG_NAME_COLORS: Record<string, string> = {
  auth: 'bg-blue-500/10 text-blue-600',
  permission: 'bg-purple-500/10 text-purple-600',
  settings: 'bg-amber-500/10 text-amber-600',
  data_export: 'bg-green-500/10 text-green-600',
  default: 'bg-gray-500/10 text-gray-600',
};

export const ActivityLogPage = () => {
  const [filters, setFilters] = useState({
    log_name: 'all',
    date_from: '',
    date_to: '',
    search: '',
  });

  const { data, isLoading, refetch } = useGetActivityLogsQuery(filters);

  if (isLoading) return <PageLoadingState />;

  const logs = data?.data?.data ?? [];
  const total = data?.data?.total ?? 0;

  return (
    <div className='space-y-6'>
      <div>
        <h1 className='text-2xl font-bold'>My Activity</h1>
        <p className='text-muted-foreground'>Track your actions and changes</p>
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
            <Select value={filters.log_name} onValueChange={(v) => setFilters((f) => ({ ...f, log_name: v }))}>
              <SelectTrigger className='w-48'>
                <SelectValue placeholder='All categories' />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value='all'>All</SelectItem>
                <SelectItem value='auth'>Auth</SelectItem>
                <SelectItem value='permission'>Permission</SelectItem>
                <SelectItem value='settings'>Settings</SelectItem>
                <SelectItem value='data_export'>Data Export</SelectItem>
              </SelectContent>
            </Select>
            <Input
              type='date'
              value={filters.date_from}
              onChange={(e) => setFilters((f) => ({ ...f, date_from: e.target.value }))}
              className='w-40'
              placeholder='From'
            />
            <Input
              type='date'
              value={filters.date_to}
              onChange={(e) => setFilters((f) => ({ ...f, date_to: e.target.value }))}
              className='w-40'
              placeholder='To'
            />
            <div className='relative flex-1 min-w-48'>
              <Search className='absolute left-3 top-2.5 h-4 w-4 text-muted-foreground' />
              <Input
                value={filters.search}
                onChange={(e) => setFilters((f) => ({ ...f, search: e.target.value }))}
                className='pl-9'
                placeholder='Search description...'
              />
            </div>
            <Button variant='outline' size='sm' onClick={() => refetch()}>
              <RefreshCw className='h-4 w-4' />
            </Button>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle className='flex items-center gap-2'>
            <Activity className='h-4 w-4' />
            Activity
          </CardTitle>
          <CardDescription>{logs.length} events</CardDescription>
        </CardHeader>
        <CardContent>
          {logs.length === 0 ? (
            <p className='text-sm text-muted-foreground text-center py-8'>No activity found.</p>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Category</TableHead>
                  <TableHead>Description</TableHead>
                  <TableHead>IP Address</TableHead>
                  <TableHead>Date</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {logs.map((log: any) => (
                  <TableRow key={log.id}>
                    <TableCell>
                      <Badge variant='outline' className={LOG_NAME_COLORS[log.log_name] ?? LOG_NAME_COLORS.default}>
                        {log.log_name}
                      </Badge>
                    </TableCell>
                    <TableCell className='max-w-xs truncate'>{log.description}</TableCell>
                    <TableCell className='text-sm text-muted-foreground'>{log.ip_address ?? '-'}</TableCell>
                    <TableCell className='text-sm text-muted-foreground'>
                      {log.created_at ? format(new Date(log.created_at), 'PPp') : '-'}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>
    </div>
  );
};
