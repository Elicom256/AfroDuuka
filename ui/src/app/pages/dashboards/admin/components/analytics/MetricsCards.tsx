interface MetricsType {
  metrics: any;
}
export const MetricsCards = ({ metrics }: MetricsType) => {
  const metricCards = [
    {
      title: 'Total Sales',
      value: metrics.sales,
      prefix: 'UGX ',
    },
    {
      title: 'Total Purchases',
      value: metrics.purchases,
      prefix: 'UGX ',
    },
    {
      title: 'Gross Profit Margin',
      value: metrics.gpm,
      suffix: '%',
      className: 'text-blue-600',
    },
  ];

  return (
    <div className='grid grid-cols-1 md:grid-cols-3 gap-4'>
      {metricCards.map((item) => (
        <div key={item.title} className='rounded-2xl bg-muted p-3'>
          <p className='text-sm text-muted-foreground'>{item.title}</p>

          <p className={` font-bold mt-2 italic ${item.className ?? ''}`}>
            {item.prefix}
            {typeof item.value === 'number' && item.title !== 'Gross Profit Margin'
              ? item.value.toLocaleString()
              : item.value}
            {item.suffix}
          </p>
        </div>
      ))}
    </div>
  );
};
