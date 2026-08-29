import { useParams, Link } from 'react-router-dom';
import { useGetProductAuditReportQuery } from '@/app/store/features/audit/productAuditQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { Button } from '@/components/ui/button';
import { ArrowLeft, Printer } from 'lucide-react';
import { ProductAuditDetail } from '../components/product-audits/ProductAuditDetail';

export const AdminProductAuditReportPage = () => {
  const { id } = useParams<{ id: string }>();
  const { data, isLoading } = useGetProductAuditReportQuery(id!);

  if (isLoading) return <PageLoadingState />;
  if (!data?.audit) return <p>Audit not found.</p>;

  const handlePrint = () => window.print();

  return (
    <div className='space-y-6'>
      <div className='flex items-center justify-between'>
        <div className='flex items-center gap-4'>
          <Button variant='ghost' size='icon' asChild>
            <Link to='/admin/product-audits'><ArrowLeft className='h-5 w-5' /></Link>
          </Button>
          <h1 className='text-2xl font-semibold'>Audit Report</h1>
        </div>
        <Button onClick={handlePrint} variant='outline' className='rounded-2xl'>
          <Printer className='mr-2 h-4 w-4' /> Print
        </Button>
      </div>
      <ProductAuditDetail audit={data.audit} />
    </div>
  );
};
