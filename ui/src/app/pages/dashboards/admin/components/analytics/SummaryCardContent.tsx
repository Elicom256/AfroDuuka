interface Analytics {
  analytics: any;
}
export const SummaryCardContent = ({ analytics }: Analytics) => {
  const stats = [
    {
      label: 'Total Sales',
      value: `UGX ${Number(analytics.total_sales).toLocaleString()}`,
    },
    {
      label: 'Average Sale',
      value: `UGX ${Number(analytics.avg_sale).toLocaleString()}`,
    },
    {
      label: 'Transactions',
      value: analytics.total_transactions,
    },
  ];
  return (
    <div className='grid grid-cols-1 sm:grid-cols-3 gap-2'>
      {stats.map((stat) => (
        <div key={stat.label} className='rounded-xl bg-muted p-2'>
          <p className='text-sm text-muted-foreground'>{stat.label}</p>

          <p className='font-semibold mt-2'>{stat.value}</p>
        </div>
      ))}
    </div>
  );
};
