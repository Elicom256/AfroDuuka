import { useParams, Link } from 'react-router-dom';
import { useGetFinancialAuditQuery } from '@/app/store/features/audit/financialAuditQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { Button } from '@/components/ui/button';
import { ArrowLeft } from 'lucide-react';
import { FinancialAuditDetail } from '../components/financial-audits/FinancialAuditDetail';

export const AdminFinancialAuditPage = () => {
  const { id } = useParams<{ id: string }>();
  const { data, isLoading } = useGetFinancialAuditQuery(id!);

  if (isLoading) return <PageLoadingState />;
  if (!data?.audit) return <p>Audit not found.</p>;

  return (
    <div className='space-y-6'>
      <div className='flex items-center gap-4'>
        <Button variant='ghost' size='icon' asChild>
          <Link to='/admin/financial-audits'><ArrowLeft className='h-5 w-5' /></Link>
        </Button>
        <h1 className='text-2xl font-semibold'>Financial Audit Detail</h1>
      </div>
      <FinancialAuditDetail audit={data.audit} />
    </div>
  );
};
