import { useParams, Link } from 'react-router-dom';
import { useGetProductAuditQuery } from '@/app/store/features/audit/productAuditQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { Button } from '@/components/ui/button';
import { ArrowLeft } from 'lucide-react';
import { ProductAuditDetail } from '../components/product-audits/ProductAuditDetail';

export const AdminProductAuditPage = () => {
  const { id } = useParams<{ id: string }>();
  const { data, isLoading } = useGetProductAuditQuery(id!);

  if (isLoading) return <PageLoadingState />;
  if (!data?.audit) return <p>Audit not found.</p>;

  return (
    <div className='space-y-6'>
      <div className='flex items-center gap-4'>
        <Button variant='ghost' size='icon' asChild>
          <Link to='/admin/product-audits'><ArrowLeft className='h-5 w-5' /></Link>
        </Button>
        <h1 className='text-2xl font-semibold'>Product Audit Detail</h1>
      </div>
      <ProductAuditDetail audit={data.audit} />
    </div>
  );
};
