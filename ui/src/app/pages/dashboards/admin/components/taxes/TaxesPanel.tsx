import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { format } from 'date-fns';

type TaxRecord = {
  id?: string | number;
  amount?: number;
  paid_amount?: number;
  balance?: number;
  branch?: {
    name?: any;
  };
  due_date?: string;
  payment_date?: string;
  status?: string;
};

type TaxesPanelProps = {
  taxes: TaxRecord[];
  totalTax: number;
  outstanding: number;
  openFilings: number;
  headers: any[];
};

export const TaxesPanel = ({ taxes, totalTax, outstanding, openFilings, headers }: TaxesPanelProps) => {
  const dateFormat = (date: any) => {
    return format(new Date(date), 'dd MMM yyyy');
  };
  return (
    <div className='space-y-6'>
      <Card className='overflow-hidden rounded-3xl border border-border/70 bg-card'>
        <CardHeader>
          <CardTitle>Tax breakdown</CardTitle>
          <CardDescription>Recent tax filings and payment history.</CardDescription>
        </CardHeader>

        <CardContent className='p-0'>
          <Table>
            <TableHeader>
              <TableRow>
                {headers.map((header) => (
                  <TableHead key={header}>{header}</TableHead>
                ))}
              </TableRow>
            </TableHeader>

            <TableBody>
              {taxes.length <= 0 ? (
                <TableRow>
                  <TableCell colSpan={7} className='py-10 text-center text-muted-foreground'>
                    No tax records found.
                  </TableCell>
                </TableRow>
              ) : (
                taxes?.map((tax: any) => (
                  <TableRow key={tax.id}>
                    <TableCell>{tax.amount?.toLocaleString()}</TableCell>
                    <TableCell>{tax.paid_amount?.toLocaleString()}</TableCell>
                    <TableCell>{tax.balance?.toLocaleString()}</TableCell>
                    <TableCell>{tax?.business_branch?.name ?? '—'}</TableCell>
                    <TableCell>{dateFormat(tax.due_date)}</TableCell>
                    <TableCell>{dateFormat(tax.payment_date)}</TableCell>
                    <TableCell>
                      <Badge variant={tax.balance ? 'secondary' : 'default'}>
                        {tax.status}
                      </Badge>
                    </TableCell>
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
};
