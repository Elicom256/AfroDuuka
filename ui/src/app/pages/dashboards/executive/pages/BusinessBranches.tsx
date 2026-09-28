import { useBranchesQuery } from '@/app/store/features/business/branches/branchesQuery';
import { Card, CardAction, CardContent, CardDescription, CardFooter, CardHeader } from '@/components/ui/card';
import { AddBranch } from '../components/branches/AddBranch';
import { Badge } from '@/components/ui/badge';

export const BusinessBranches = () => {
  const { data } = useBranchesQuery();

  const branches = data?.branches;
  console.log('branches==>', branches);
  return (
    <div>
      <div className='px-10 '>
        <Card className='rounded-3xl border border-border/70 bg-card p-6'>
          <CardHeader className='flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between'>
            <CardDescription>Manage All Business Branches You Own.</CardDescription>
            {/* <AddSale addSale={addSale} products={products} /> */}
          </CardHeader>
          <CardContent>
            {/* dialogue to render a form */}
            <AddBranch />
          </CardContent>
        </Card>

        {/* branches */}
        <div className='grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 py-10'>
          {branches?.map((branch: any) => (
            <Card key={branch.id} className='rounded-xl border '>
              <CardHeader className='flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between'>
                <CardDescription>{branch.name}</CardDescription>
                <CardAction>
                  <Badge>{branch.status}</Badge>
                </CardAction>
              </CardHeader>
              <CardContent>
                <div className='flex flex-col text-sm'>
                  <div className='flex items-center gap-2'>
                    {/* <span>Address:</span> */}
                    <span>{branch.address}</span>
                  </div>
                </div>
              </CardContent>
              <CardFooter className='w-full h-full flex items-center justify-center text-gray-400 gap-2'>
                <span>Tel</span>
                <span>{branch.phone}</span>
              </CardFooter>
            </Card>
          ))}
        </div>
      </div>
    </div>
  );
};
