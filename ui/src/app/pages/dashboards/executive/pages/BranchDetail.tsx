import { useParams, Link } from 'react-router-dom';
import { useBranchQuery, useDeleteBranchMutation } from '@/app/store/features/business/branches/branchesQuery';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ArrowLeft, MapPin, Phone, Users, Package, TrendingUp, TrendingDown } from 'lucide-react';
import { PageLoadingState } from '@/utils/PageLoadingState';
import { EditBranch } from '../components/branches/EditBranch';
import { ConfirmDeleteButton } from '@/components/ConfirmDeleteButton';
import { toast } from 'sonner';
import { serverMessage } from '@/app/utils/errorMessage';

export const BranchDetail = () => {
  const { id } = useParams<{ id: string }>();
  const { data, isLoading, isError } = useBranchQuery(id ?? '');
  const [deleteBranch, { isLoading: isDeleting }] = useDeleteBranchMutation();

  if (isLoading) return <PageLoadingState />;
  if (isError || !data) {
    return (
      <div className='py-8 text-center text-sm text-destructive'>
        Could not load branch. Please try again.
      </div>
    );
  }

  const branch = data.branch ?? data;

  const handleDelete = async () => {
    try {
      const res = await deleteBranch(branch.id).unwrap();
      toast.success(res?.message ?? 'Branch deleted');
    } catch (error) {
      // The API refuses to delete a branch that has traded (it says why), so the
      // server's own explanation is what the user needs to see.
      toast.error(serverMessage(error, 'Failed to delete the branch!'));
      throw error;
    }
  };

  return (
    <div className="space-y-6 px-10">
      <div className="flex items-center gap-4">
        <Link to="/dashboard/branches">
          <Button variant="ghost" size="sm">
            <ArrowLeft className="h-4 w-4" />
          </Button>
        </Link>
        <div>
          <h1 className="text-2xl font-bold">{branch.name}</h1>
          <div className="flex items-center gap-2 text-sm text-muted-foreground">
            <MapPin className="h-3 w-3" />
            <span>{branch.address}</span>
            <Phone className="ml-2 h-3 w-3" />
            <span>{branch.phone}</span>
          </div>
        </div>
        <Badge className="ml-auto">{branch.status}</Badge>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Workers</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{branch.worker_count ?? 0}</div>
            <CardDescription>Active employees</CardDescription>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Products</CardTitle>
            <Package className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{branch.product_count ?? 0}</div>
            <CardDescription>Items in stock</CardDescription>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Income</CardTitle>
            <TrendingUp className="h-4 w-4 text-green-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{branch.total_sales ?? 0}</div>
            <CardDescription>Total sales</CardDescription>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Expenses</CardTitle>
            <TrendingDown className="h-4 w-4 text-red-600" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{branch.total_expenses ?? 0}</div>
            <CardDescription>Total expenses</CardDescription>
          </CardContent>
        </Card>
      </div>

      <div className="flex gap-3">
        <EditBranch branch={branch} />
        <ConfirmDeleteButton
          onConfirm={handleDelete}
          isDeleting={isDeleting}
          title="Delete this branch?"
          description="Branches that have traded cannot be deleted — the server will explain if that is the case here."
          trigger={<Button variant="destructive">Delete Branch</Button>}
        />
      </div>
    </div>
  );
};
