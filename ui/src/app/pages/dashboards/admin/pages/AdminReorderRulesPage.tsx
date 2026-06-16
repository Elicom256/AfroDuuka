import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useReorderRulesQuery, useCreateReorderRuleMutation, useDeleteReorderRuleMutation } from '@/app/store/features/business/admin/reorderRulesQuery';
import { useBranchProductsQuery } from '@/app/store/features/branch/products/branchProductsQuery';
import { useSuppliersQuery } from '@/app/store/features/business/suppliers/supplierQuery';
import { PackageSearch } from 'lucide-react';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { AddReorderRule } from '../components/reorder-rules/AddReorderRule';
import { ReorderRulesTable } from '../components/reorder-rules/ReorderRulesTable';

export const AdminReorderRulesPage = () => {
  const { data, isLoading } = useReorderRulesQuery();
  const [createRule] = useCreateReorderRuleMutation();
  const [deleteRule] = useDeleteReorderRuleMutation();
  const { data: productsData } = useBranchProductsQuery();
  const { data: suppliersData } = useSuppliersQuery();
  const rules = data?.data || [];
  const products = productsData?.data || [];
  const suppliers = suppliersData?.data || [];

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <div className='flex items-center justify-between'>
        <div>
          <h1 className='text-3xl font-bold tracking-tight flex items-center gap-3'>
            <PackageSearch className='h-8 w-8' /> Reorder Rules
          </h1>
          <p className='text-muted-foreground'>Automatic inventory reorder triggers</p>
        </div>
        <AddReorderRule createRule={createRule} products={products} suppliers={suppliers} />
      </div>
      <Card>
        <CardHeader><CardTitle>All Rules ({rules.length})</CardTitle></CardHeader>
        <CardContent>
          {rules.length === 0 ? (
            <p className='text-muted-foreground text-sm text-center py-8'>No reorder rules configured.</p>
          ) : (
            <ReorderRulesTable rules={rules} onDelete={deleteRule} />
          )}
        </CardContent>
      </Card>
    </div>
  );
};
