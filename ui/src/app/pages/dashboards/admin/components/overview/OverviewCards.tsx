import { useGetWorkersInfoQuery } from '@/app/store/features/business/workers/workersQuery';
import { useProductsQuery, useProductCategoriesQuery } from '@/app/store/features/business/products/productsQuery';
import { useBranchesQuery } from '@/app/store/features/business/branches/branchesQuery';
import { useCustomersQuery } from '@/app/store/features/business/customers/customersQuery';
import { useSuppliersQuery } from '@/app/store/features/business/suppliers/supplierQuery';
import { useSalesQuery } from '@/app/store/features/branch/sales/salesQuery';
import { usePurchasesQuery } from '@/app/store/features/branch/purchases/purchasesQuery';
import { useBranchProductExpiringQuery } from '@/app/store/features/branch/products/branchProductsQuery';
import { useGetTodosQuery } from '@/app/store/features/todos/todoQuery';
import { useCurrency } from '@/app/hooks/useCurrency';
import { Users, Package, Tags, Building2, UserCheck, Truck, DollarSign, ShoppingCart, AlertTriangle, CheckSquare } from 'lucide-react';
import { StatsCard } from './StatsCard';

export const OverviewCards = () => {
  const { currencySymbol } = useCurrency();
  const { data: workersData, isLoading: workersLoading } = useGetWorkersInfoQuery();
  const { data: productsData, isLoading: productsLoading } = useProductsQuery();
  const { data: categoriesData, isLoading: categoriesLoading } = useProductCategoriesQuery();
  const { data: branchesData, isLoading: branchesLoading } = useBranchesQuery();
  const { data: customersData, isLoading: customersLoading } = useCustomersQuery();
  const { data: suppliersData, isLoading: suppliersLoading } = useSuppliersQuery();
  const { data: salesData, isLoading: salesLoading } = useSalesQuery();
  const { data: purchasesData, isLoading: purchasesLoading } = usePurchasesQuery();
  const { data: expiringData, isLoading: expiringLoading } = useBranchProductExpiringQuery();

  const workers = workersData?.workers;
  const products = productsData?.products;
  const categories = categoriesData?.categories;
  const branches = branchesData?.branches;
  const customers = customersData?.customers;
  const suppliers = suppliersData?.suppliers;
  const sales = salesData?.sales ?? salesData ?? [];
  const purchases = purchasesData?.purchases ?? purchasesData ?? [];

  const totalSalesAmount = sales.reduce((sum: number, sale: any) => sum + Number(sale.total_amount ?? 0), 0);
  const totalPurchasesAmount = purchases.reduce(
    (sum: number, purchase: any) => sum + Number(purchase.total_amount ?? 0),
    0,
  );

  const expiring = expiringData?.data;
  const expiringCount = (expiring?.expiring_count ?? 0) + (expiring?.expired_count ?? 0);
  const dangerCount = expiring?.danger_count ?? 0;

  const { data: todosData, isLoading: todosLoading } = useGetTodosQuery();
  const todos = todosData?.data ?? [];
  const pendingTodos = todos.filter((t: any) => t.status === 'undone').length;

  const formatAmount = (amount: number) => `${currencySymbol} ${Math.round(amount).toLocaleString()}`;
  return (
    <div className='grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4'>
      <StatsCard
        title='Workers'
        value={workers?.length ?? 0}
        icon={Users}
        isLoading={workersLoading}
        description='Total workforce'
      />
      <StatsCard
        title='Business Products'
        value={products?.length ?? 0}
        icon={Package}
        isLoading={productsLoading}
        description='Product types offered'
      />
      <StatsCard
        title='Categories'
        value={categories?.length ?? 0}
        icon={Tags}
        isLoading={categoriesLoading}
        description='Product categories'
      />
      <StatsCard
        title='Branches'
        value={branches?.length ?? 0}
        icon={Building2}
        isLoading={branchesLoading}
        description='Active locations'
      />
      <StatsCard
        title='Customers'
        value={customers?.length ?? 0}
        icon={UserCheck}
        isLoading={customersLoading}
        description='Registered customers'
      />
      <StatsCard
        title='Suppliers'
        value={suppliers?.length ?? 0}
        icon={Truck}
        isLoading={suppliersLoading}
        description='Partner suppliers'
      />
      <StatsCard
        title='Total Sales'
        value={formatAmount(totalSalesAmount)}
        icon={DollarSign}
        isLoading={salesLoading}
        description='Revenue since inception'
        iconClassName='bg-emerald-500/10 text-emerald-600'
      />
      <StatsCard
        title='Total Purchases'
        value={formatAmount(totalPurchasesAmount)}
        icon={ShoppingCart}
        isLoading={purchasesLoading}
        description='Cost since inception'
        iconClassName='bg-amber-500/10 text-amber-600'
      />
      <StatsCard
        title='Expiring Products'
        value={expiringCount}
        icon={AlertTriangle}
        isLoading={expiringLoading}
        description={dangerCount > 0 ? `${dangerCount} in danger zone` : 'Products nearing expiry'}
        iconClassName={dangerCount > 0 ? 'bg-red-500/10 text-red-600' : 'bg-orange-500/10 text-orange-600'}
      />
      <StatsCard
        title='Pending Tasks'
        value={pendingTodos}
        icon={CheckSquare}
        isLoading={todosLoading}
        description='Tasks awaiting completion'
        iconClassName='bg-blue-500/10 text-blue-600'
      />
    </div>
  );
};
