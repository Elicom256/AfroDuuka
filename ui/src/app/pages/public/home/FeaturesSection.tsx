import {
  PackageSearch,
  BarChart3,
  Bell,
  MessageSquareText,
  Users,
  Building2,
  Truck,
  Barcode,
  FileText,
  Cloud,
  ShieldCheck,
  BrainCircuit,
} from 'lucide-react';

const features = [
  { icon: <PackageSearch className='h-5 w-5' />, title: 'Real-time inventory', description: 'Track every product across all branches. Know exactly what you have, where it is, and when to reorder.' },
  { icon: <BarChart3 className='h-5 w-5' />, title: 'Sales analytics', description: 'Understand trends, top products, and peak periods. Make data-driven restocking decisions.' },
  { icon: <MessageSquareText className='h-5 w-5' />, title: 'WhatsApp notifications', description: 'Low-stock alerts, daily summaries, and purchase updates sent directly to your WhatsApp.' },
  { icon: <BrainCircuit className='h-5 w-5' />, title: 'DuukaFlow Assistant', description: 'Ask questions in plain language. Get inventory insights, sales guidance, and recommendations instantly.' },
  { icon: <Bell className='h-5 w-5' />, title: 'Low-stock alerts', description: 'Set custom thresholds. Get notified before you run out of best-selling items.' },
  { icon: <FileText className='h-5 w-5' />, title: 'Profit & loss reports', description: 'Know your margins, track expenses, and see exactly where your money is going.' },
  { icon: <Building2 className='h-5 w-5' />, title: 'Multi-branch management', description: 'Manage multiple shop locations from a single dashboard. Compare performance across branches.' },
  { icon: <Users className='h-5 w-5' />, title: 'Employee management', description: 'Assign roles, track performance, and control access across your team.' },
  { icon: <Truck className='h-5 w-5' />, title: 'Supplier management', description: 'Store supplier details, track purchase orders, and never lose a contact.' },
  { icon: <Barcode className='h-5 w-5' />, title: 'Barcode support', description: 'Scan barcodes to add and sell products. Fast, accurate, and error-free.' },
  { icon: <Cloud className='h-5 w-5' />, title: 'Cloud-based access', description: 'Access your data from anywhere, on any device. Real-time sync across all your devices.' },
  { icon: <ShieldCheck className='h-5 w-5' />, title: 'Secure data storage', description: 'Your business data is encrypted and safely stored. Regular backups included.' },
];

export const FeaturesSection = () => {
  return (
    <section className='py-16 sm:py-20'>
      <div className='text-center'>
        <span className='inline-flex rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-primary'>
          Everything you need
        </span>
        <h2 className='mt-4 text-3xl font-bold tracking-tight text-foreground sm:text-4xl'>
          Powerful features for growing businesses
        </h2>
        <p className='mx-auto mt-3 max-w-2xl text-muted-foreground'>
          From inventory tracking to intelligent insights — DuukaFlow equips your business with tools that save time,
          reduce losses, and increase profits.
        </p>
      </div>

      <div className='mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4'>
        {features.map((feature) => (
          <div
            key={feature.title}
            className='group rounded-xl border border-border/60 bg-card/50 p-5 transition hover:border-primary/30 hover:bg-card hover:shadow-sm'
          >
            <div className='inline-flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10 text-primary group-hover:bg-primary/15'>
              {feature.icon}
            </div>
            <h3 className='mt-4 font-semibold text-foreground'>{feature.title}</h3>
            <p className='mt-1 text-sm text-muted-foreground'>{feature.description}</p>
          </div>
        ))}
      </div>
    </section>
  );
};
