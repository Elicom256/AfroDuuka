interface SummaryCardContentProps {
  analytics: any;
  currency?: string;
}

export const SummaryCardContent = ({ analytics, currency = 'UGX' }: SummaryCardContentProps) => {
  console.log('analytics here==>', analytics);
  const stats = [
    {
      label: analytics.lable === 'sales' ? 'Total Sales' : 'Total Purchases',
      value: `${currency} ${Number(analytics.total_sales ?? analytics.total_purchases).toLocaleString()}`,
    },
    {
      label: analytics.lable === 'sales' ? 'Average Sale' : 'Average Purchases',
      value: `${currency} ${Number(analytics.avg_sale ?? analytics.avg_purchase).toLocaleString()}`,
    },
    {
      label: 'Transactions',
      value: analytics.total_transactions.toLocaleString(),
    },
  ];

  return (
    <div className='grid grid-cols-1 sm:grid-cols-3 gap-4'>
      {stats.map((stat, index) => (
        <div key={index} className='rounded-xl bg-muted p-2'>
          <p className='text-sm text-muted-foreground'>{stat.label}</p>
          <p className='font-semibold mt-2'>{stat.value}</p>
        </div>
      ))}
    </div>
  );
};
