import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Server, Database, Cpu, HardDrive, CheckCircle2, AlertTriangle } from 'lucide-react';

export const SystemStatus = () => {
  const services = [
    { name: 'API Server', status: 'operational', icon: Server, uptime: '99.9%' },
    { name: 'Database', status: 'operational', icon: Database, uptime: '99.8%' },
    { name: 'Queue Worker', status: 'operational', icon: Cpu, uptime: '99.5%' },
    { name: 'Storage', status: 'operational', icon: HardDrive, uptime: '100%' },
  ];

  const issues = [
    { id: 1, message: 'Backup completed successfully', severity: 'info' as const, time: '2 hours ago' },
    { id: 2, message: 'Queue worker restarted', severity: 'warning' as const, time: '5 hours ago' },
    { id: 3, message: 'Database migration applied', severity: 'info' as const, time: '1 day ago' },
  ];

  return (
    <Card>
      <CardHeader className='pb-3'>
        <CardTitle className='flex items-center gap-2 text-sm'>
          <Server className='h-4 w-4 text-emerald-500' />
          System Status
        </CardTitle>
      </CardHeader>
      <CardContent className='space-y-3'>
        <div className='space-y-2'>
          {services.map((service) => {
            const Icon = service.icon;
            return (
              <div key={service.name} className='flex items-center justify-between rounded-lg border border-border/50 px-3 py-2'>
                <div className='flex items-center gap-2.5'>
                  <Icon className='h-4 w-4 text-muted-foreground' />
                  <span className='text-sm font-medium'>{service.name}</span>
                </div>
                <div className='flex items-center gap-2'>
                  <span className='text-xs text-muted-foreground'>{service.uptime}</span>
                  <Badge variant='outline' className='border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-400'>
                    <CheckCircle2 className='mr-1 h-3 w-3' />
                    {service.status}
                  </Badge>
                </div>
              </div>
            );
          })}
        </div>

        <div className='border-t pt-3'>
          <p className='mb-2 text-xs font-medium text-muted-foreground'>Recent Events</p>
          <div className='space-y-1.5'>
            {issues.map((issue) => (
              <div key={issue.id} className='flex items-start gap-2 text-xs'>
                {issue.severity === 'warning' ? (
                  <AlertTriangle className='mt-0.5 h-3 w-3 shrink-0 text-amber-500' />
                ) : (
                  <CheckCircle2 className='mt-0.5 h-3 w-3 shrink-0 text-emerald-500' />
                )}
                <div className='min-w-0 flex-1'>
                  <p className='text-sm'>{issue.message}</p>
                  <p className='text-muted-foreground'>{issue.time}</p>
                </div>
              </div>
            ))}
          </div>
        </div>
      </CardContent>
    </Card>
  );
};
