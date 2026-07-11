import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
  useStockTransfersQuery,
  useCreateStockTransferMutation,
  useDispatchStockTransferMutation,
  useCancelStockTransferMutation,
} from '@/app/store/features/business/admin/stockTransfersQuery';
import { useBranchesQuery } from '@/app/store/features/business/branches/branchesQuery';
import { useProductsQuery } from '@/app/store/features/branch/products/branchProductsQuery';
import { ArrowLeftRight } from 'lucide-react';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { AddStockTransfer } from '../components/stock-transfers/AddStockTransfer';
import { StockTransfersTable } from '../components/stock-transfers/StockTransfersTable';

export const AdminStockTransfersPage = () => {
  const { data, isLoading } = useStockTransfersQuery();
  const [createTransfer] = useCreateStockTransferMutation();
  const [dispatch] = useDispatchStockTransferMutation();
  const [cancel] = useCancelStockTransferMutation();
  const { data: branchesData } = useBranchesQuery();
  const { data: productsData } = useProductsQuery();
  const transfers = data?.data || [];
  const branches = branchesData?.branches || [];
  const products = productsData?.products || [];
  // console.log('products==>', products);

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <div className='flex items-center justify-between'>
        <div>
          <h1 className='text-3xl font-bold tracking-tight flex items-center gap-3'>
            <ArrowLeftRight className='h-8 w-8' /> Stock Transfers
          </h1>
          <p className='text-muted-foreground'>Inter-branch stock movement tracking</p>
        </div>
        <AddStockTransfer createTransfer={createTransfer} branches={branches} products={products} />
      </div>
      <Card>
        <CardHeader>
          <CardTitle>All Transfers ({transfers.length})</CardTitle>
        </CardHeader>
        <CardContent>
          {transfers.length === 0 ? (
            <p className='text-muted-foreground text-sm text-center py-8'>No stock transfers yet.</p>
          ) : (
            <StockTransfersTable transfers={transfers} onDispatch={dispatch} onCancel={cancel} />
          )}
        </CardContent>
      </Card>
    </div>
  );
};
