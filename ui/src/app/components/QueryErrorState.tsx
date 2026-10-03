import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { RefreshCw, TriangleAlert } from 'lucide-react';

type QueryErrorStateProps = {
  title: string;
  description: string;
  onRetry?: () => unknown;
  retrying?: boolean;
};

export const QueryErrorState = ({ title, description, onRetry, retrying = false }: QueryErrorStateProps) => (
  <div className='space-y-3'>
    <Alert variant='destructive'>
      <TriangleAlert aria-hidden='true' />
      <AlertTitle>{title}</AlertTitle>
      <AlertDescription>{description}</AlertDescription>
    </Alert>
    {onRetry && (
      <Button type='button' variant='outline' size='sm' onClick={() => void onRetry()} disabled={retrying}>
        <RefreshCw className={`mr-2 h-4 w-4 ${retrying ? 'animate-spin' : ''}`} />
        {retrying ? 'Retrying' : 'Retry'}
      </Button>
    )}
  </div>
);

type QueryEmptyStateProps = {
  title: string;
  description?: string;
};

export const QueryEmptyState = ({ title, description }: QueryEmptyStateProps) => (
  <div className='py-10 text-center' role='status'>
    <p className='font-medium'>{title}</p>
    {description && <p className='mt-1 text-sm text-muted-foreground'>{description}</p>}
  </div>
);
