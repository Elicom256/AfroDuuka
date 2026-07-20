import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useCurrency } from '@/app/hooks/useCurrency';

type IncomeRow = {
  month: string;
  revenue: number;
  expenses: number;
  net_profit: number;
  profit_margin: number;
};

type IncomeSummaryTableProps = {
  data: IncomeRow[];
  year: string;
  onYearChange: (year: string) => void;
};

export const IncomeSummaryTable = ({ data, year, onYearChange }: IncomeSummaryTableProps) => {
  const { currency } = useCurrency();

  const currentYear = new Date().getFullYear();
  const years = Array.from({ length: 5 }, (_, i) => String(currentYear - i));

  const totals = data.reduce(
    (acc, row) => ({
      revenue: acc.revenue + Number(row.revenue),
      expenses: acc.expenses + Number(row.expenses),
      net_profit: acc.net_profit + Number(row.net_profit),
      profit_margin: acc.profit_margin + Number(row.profit_margin),
    }),
    { revenue: 0, expenses: 0, net_profit: 0, profit_margin: 0 },
  );

  const avgMargin = data.length > 0 ? totals.profit_margin / data.length : 0;

  return (
    <Card>
      <CardHeader>
        <div className='flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4'>
          <CardTitle>Income Summary</CardTitle>
          <select
            value={year}
            onChange={(e) => onYearChange(e.target.value)}
            className='rounded-md border border-input bg-background px-3 py-1 text-sm'
          >
            {years.map((y) => (
              <option key={y} value={y}>
                {y}
              </option>
            ))}
          </select>
        </div>
      </CardHeader>
      <CardContent className='p-0'>
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Month</TableHead>
              <TableHead className='text-right'>Revenue</TableHead>
              <TableHead className='text-right'>Expenses</TableHead>
              <TableHead className='text-right'>Net Profit</TableHead>
              <TableHead className='text-right'>Profit Margin</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {data.length === 0 ? (
              <TableRow>
                <TableCell colSpan={5} className='py-10 text-center text-muted-foreground'>
                  No income data available for {year}.
                </TableCell>
              </TableRow>
            ) : (
              data.map((row, i) => (
                <TableRow key={i}>
                  <TableCell className='font-medium'>{row.month}</TableCell>
                  <TableCell className='text-right'>
                    {currency} {Number(row.revenue).toLocaleString()}
                  </TableCell>
                  <TableCell className='text-right'>
                    {currency} {Number(row.expenses).toLocaleString()}
                  </TableCell>
                  <TableCell
                    className={`text-right font-medium ${
                      Number(row.net_profit) >= 0 ? 'text-green-600' : 'text-red-600'
                    }`}
                  >
                    {Number(row.net_profit) >= 0 ? '+' : ''}
                    {currency} {Number(row.net_profit).toLocaleString()}
                  </TableCell>
                  <TableCell
                    className={`text-right font-medium ${
                      Number(row.profit_margin) >= 0 ? 'text-green-600' : 'text-red-600'
                    }`}
                  >
                    {Number(row.profit_margin).toFixed(1)}%
                  </TableCell>
                </TableRow>
              ))
            )}
            {data.length > 0 && (
              <TableRow className='border-t-2 border-border font-semibold bg-muted/50'>
                <TableCell>Total</TableCell>
                <TableCell className='text-right'>
                  {currency} {totals.revenue.toLocaleString()}
                </TableCell>
                <TableCell className='text-right'>
                  {currency} {totals.expenses.toLocaleString()}
                </TableCell>
                <TableCell
                  className={`text-right ${totals.net_profit >= 0 ? 'text-green-600' : 'text-red-600'}`}
                >
                  {totals.net_profit >= 0 ? '+' : ''}
                  {currency} {totals.net_profit.toLocaleString()}
                </TableCell>
                <TableCell
                  className={`text-right ${avgMargin >= 0 ? 'text-green-600' : 'text-red-600'}`}
                >
                  {avgMargin.toFixed(1)}%
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </CardContent>
    </Card>
  );
};
