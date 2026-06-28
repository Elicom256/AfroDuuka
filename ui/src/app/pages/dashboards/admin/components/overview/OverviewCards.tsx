import { useGetWorkersInfoQuery } from '@/app/store/features/business/workers/workersQuery';
import { useProductsQuery, useProductCategoriesQuery } from '@/app/store/features/business/products/productsQuery';
import { useBranchesQuery } from '@/app/store/features/business/branches/branchesQuery';
import { useCustomersQuery } from '@/app/store/features/business/customers/customersQuery';
import { useSuppliersQuery } from '@/app/store/features/business/suppliers/supplierQuery';
import { Users, Package, Tags, Building2, UserCheck, Truck } from 'lucide-react';
import { StatsCard } from './StatsCard';

export const OverviewCards = () => {
  const { data: workersData, isLoading: workersLoading } = useGetWorkersInfoQuery();
  const { data: productsData, isLoading: productsLoading } = useProductsQuery();
  const { data: categoriesData, isLoading: categoriesLoading } = useProductCategoriesQuery();
  const { data: branchesData, isLoading: branchesLoading } = useBranchesQuery();
  const { data: customersData, isLoading: customersLoading } = useCustomersQuery();
  const { data: suppliersData, isLoading: suppliersLoading } = useSuppliersQuery();

  const workers = workersData?.workers;
  const products = productsData?.products;
  const categories = categoriesData?.categories;
  const branches = branchesData?.branches;
  const customers = customersData?.customers;
  const suppliers = suppliersData?.suppliers;

  return (
    <div className='grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3'>
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
    </div>
  );
};
