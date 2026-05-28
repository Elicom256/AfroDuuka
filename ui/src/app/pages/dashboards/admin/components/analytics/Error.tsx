import { Alert, AlertDescription } from '@/components/ui/alert';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DollarSign } from 'lucide-react';
interface ErrorType {
  error: any;
}
export const Error = ({ error }: ErrorType) => {
  return (
    <Card>
      <CardHeader>
        <CardTitle className='flex items-center gap-2'>
          <DollarSign className='h-6 w-6' /> Sales Analytics
        </CardTitle>
      </CardHeader>
      <CardContent>
        <Alert variant='destructive'>
          <AlertDescription>
            Failed to load sales analytics. {error?.data?.message && `(${error.data.message})`}
          </AlertDescription>
        </Alert>
      </CardContent>
    </Card>
  );
};
