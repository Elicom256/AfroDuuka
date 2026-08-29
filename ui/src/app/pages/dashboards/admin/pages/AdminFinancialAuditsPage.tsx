import { useState, useMemo } from 'react';
import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
  useGetFinancialAuditsQuery,
  useDeleteFinancialAuditMutation,
  useCancelFinancialAuditMutation,
} from '@/app/store/features/audit/financialAuditQuery';
import { useBranchesQuery } from '@/app/store/features/business/branches/branchesQuery';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { CreateFinancialAudit } from '../components/financial-audits/CreateFinancialAudit';
import { EditFinancialAudit } from '../components/financial-audits/EditFinancialAudit';
import { FinancialAuditFilters } from '../components/financial-audits/FinancialAuditFilters';
import { FinancialAuditTable } from '../components/financial-audits/FinancialAuditTable';
import { ApproveFinancialAuditDialog } from '../components/financial-audits/ApproveFinancialAuditDialog';
import { toast } from 'sonner';

export const AdminFinancialAuditsPage = () => {
  const [page, setPage] = useState(1);
  const [filters, setFilters] = useState<Record<string, string>>({});
  const [search, setSearch] = useState('');
  const [editOpen, setEditOpen] = useState(false);
  const [approveOpen, setApproveOpen] = useState(false);
  const [selected, setSelected] = useState<any>(null);

  const queryParams = useMemo(() => {
    const params: Record<string, any> = { page };
    if (filters.business_branch_id) params.business_branch_id = filters.business_branch_id;
    if (filters.status) params.status = filters.status;
    if (filters.date_from) params.date_from = filters.date_from;
    if (filters.date_to) params.date_to = filters.date_to;
    if (search) params.search = search;
    return params;
  }, [page, filters, search]);

  const { data, isLoading } = useGetFinancialAuditsQuery(queryParams);
  const { data: branchesData } = useBranchesQuery();
  const [deleteAudit] = useDeleteFinancialAuditMutation();
  const [cancelAudit] = useCancelFinancialAuditMutation();

  const audits = data?.audits?.data ?? [];
  const currentPage = data?.audits?.current_page ?? 1;
  const totalPages = data?.audits?.last_page ?? 1;
  const branches = branchesData?.branches ?? [];

  const handleFilterChange = (key: string, value: string) => {
    setFilters((prev) => ({ ...prev, [key]: value }));
    setPage(1);
  };

  const clearFilters = () => {
    setFilters({});
    setSearch('');
    setPage(1);
  };

  const handleEdit = (item: any) => {
    setSelected(item);
    setEditOpen(true);
  };

  const handleApprove = (item: any) => {
    setSelected(item);
    setApproveOpen(true);
  };

  const handleDelete = async (id: number) => {
    if (!confirm('Are you sure?')) return;
    try {
      await deleteAudit(id).unwrap();
      toast.success('Financial audit deleted');
    } catch {
      toast.error('Failed to delete');
    }
  };

  const handleCancel = async (id: number) => {
    if (!confirm('Are you sure?')) return;
    try {
      await cancelAudit(id).unwrap();
      toast.success('Financial audit cancelled');
    } catch {
      toast.error('Failed to cancel');
    }
  };

  if (isLoading) return <PageLoadingState />;

  return (
    <div className='space-y-6'>
      <Card className='rounded-3xl border border-border/70 bg-card/80 shadow-sm'>
        <CardHeader className='flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between'>
          <div className='space-y-2'>
            <CardTitle className='text-2xl'>Financial Audits</CardTitle>
            <CardDescription className='max-w-2xl'>Verify recorded financial balances against actual balances.</CardDescription>
          </div>
          <CreateFinancialAudit branches={branches} />
        </CardHeader>
      </Card>

      <FinancialAuditFilters
        branches={branches}
        filters={filters}
        search={search}
        onFilterChange={handleFilterChange}
        onSearchChange={(v) => { setSearch(v); setPage(1); }}
        onClear={clearFilters}
      />

      <FinancialAuditTable
        audits={audits}
        currentPage={currentPage}
        totalPages={totalPages}
        onPageChange={setPage}
        onApprove={handleApprove}
        onEdit={handleEdit}
        onDelete={handleDelete}
        onCancel={handleCancel}
      />

      <EditFinancialAudit
        open={editOpen}
        onOpenChange={(o) => { setEditOpen(o); if (!o) setSelected(null); }}
        audit={selected}
      />

      <ApproveFinancialAuditDialog
        open={approveOpen}
        onOpenChange={(o) => { setApproveOpen(o); if (!o) setSelected(null); }}
        audit={selected}
      />
    </div>
  );
};
