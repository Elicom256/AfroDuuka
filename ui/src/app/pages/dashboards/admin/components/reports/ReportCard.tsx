import React from 'react';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';

type Props = {
  title: string;
  loading?: boolean;
  children?: React.ReactNode;
};

export const ReportCard = ({ title, loading, children }: Props) => {
  return (
    <Card>
      <CardHeader>
        <CardTitle>{title}</CardTitle>
      </CardHeader>
      <CardContent>
        {loading ? (
          <div className='flex items-center justify-center py-6'>
            <Spinner />
          </div>
        ) : (
          children
        )}
      </CardContent>
    </Card>
  );
};

export default ReportCard;
