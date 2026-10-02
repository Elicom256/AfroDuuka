import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  Rocket,
  Package,
  ShoppingCart,
  BarChart3,
  Users,
  Truck,
  FileText,
  Bell,
  Shield,
  Smartphone,
  Zap,
  CheckCircle2,
  Clock,
  ArrowRight,
  Store,
  CreditCard,
  RefreshCw,
  Tag,
  Heart,
} from 'lucide-react';

const features = [
  {
    icon: ShoppingCart,
    title: 'Point of Sale',
    description: 'Fast checkout with split payments, hold/resume, barcode scanning, and receipt generation.',
    status: 'Live',
    statusVariant: 'success' as const,
  },
  {
    icon: Package,
    title: 'Inventory Management',
    description: 'Track stock levels, manage categories, set reorder alerts, and handle stock transfers.',
    status: 'Live',
    statusVariant: 'success' as const,
  },
  {
    icon: FileText,
    title: 'Quotations',
    description: 'Create professional quotes, send to customers, and convert to sales with one click.',
    status: 'Live',
    statusVariant: 'success' as const,
  },
  {
    icon: CreditCard,
    title: 'Payments',
    description: 'Accept mobile money and card payments directly at the point of sale.',
    status: 'In Progress',
    statusVariant: 'warning' as const,
  },
  {
    icon: Bell,
    title: 'Notifications',
    description: 'WhatsApp, email, and SMS alerts for low stock, expiries, and customer communication.',
    status: 'In Progress',
    statusVariant: 'warning' as const,
  },
  {
    icon: Truck,
    title: 'Warehouses',
    description: 'Multi-location stock management with batch and serial number tracking.',
    status: 'Planned',
    statusVariant: 'outline' as const,
  },
];

const workflows = [
  {
    icon: Store,
    title: 'Sales Workflow',
    steps: [
      'Create products with pricing and stock levels',
      'Process sales through POS or manual entry',
      'Generate receipts and send to customers',
      'Track revenue and cash flow in real-time',
    ],
  },
  {
    icon: RefreshCw,
    title: 'Purchase Workflow',
    steps: [
      'Add suppliers and set payment terms',
      'Create purchase orders and receive stock',
      'Update inventory automatically on receipt',
      'Track expenses and supplier balances',
    ],
  },
  {
    icon: BarChart3,
    title: 'Reporting Workflow',
    steps: [
      'View dashboard summaries and KPIs',
      'Generate detailed reports by date range',
      'Export data for accounting purposes',
      'Set up automated report delivery',
    ],
  },
];

const faqs = [
  {
    question: 'How do I set up my first branch?',
    answer:
      'Navigate to Settings > Branches and click "Add Branch". Enter your branch name, address, and contact details. You can assign specific staff members to each branch and set individual permissions.',
  },
  {
    question: 'Can I use DuukaFlow on multiple devices?',
    answer:
      'Yes! DuukaFlow is fully responsive and works on desktops, tablets, and smartphones. Your data syncs in real-time across all devices, so you can manage your shop from anywhere.',
  },
  {
    question: 'How does barcode scanning work?',
    answer:
      'Go to Products and add a barcode/SKU to each product. At POS, simply scan the barcode using a USB or Bluetooth scanner, or type it manually. The product will be added to the cart instantly.',
  },
  {
    question: 'What payment methods are supported?',
    answer:
      'Currently, manual payment recording is available. Mobile money (MTN MoMo, Airtel Money) and card payments are being integrated and will be available soon. You can record cash, mobile money, and card payments manually in the meantime.',
  },
  {
    question: 'How do I handle returns and refunds?',
    answer:
      'Navigate to Sales > Sale Returns or Purchases > Purchase Returns. Select the original transaction, choose the items to return, and process the refund. Stock levels are automatically adjusted.',
  },
  {
    question: 'Can I export my data?',
    answer:
      'Yes, you can export sales, purchases, inventory, and customer data in CSV format from the Reports section. This makes it easy to import into accounting software or spreadsheets.',
  },
];

const stats = [
  { label: 'Active Features', value: '25+' },
  { label: 'User Roles', value: '4' },
  { label: 'Report Types', value: '12+' },
  { label: 'Integrations', value: '3' },
];

export const Documentation: React.FC = () => {
  return (
    <div className='min-h-screen bg-background'>
      <div className='container mx-auto px-4 py-12 sm:py-16'>
        <div className='mx-auto max-w-3xl text-center'>
          <Badge variant='secondary' className='mb-4'>
            <Rocket className='mr-1 h-3 w-3' />
            Documentation
          </Badge>
          <h1 className='font-heading text-4xl font-bold tracking-tight text-foreground sm:text-5xl'>
            Everything you need to know about DuukaFlow
          </h1>
          <p className='mt-4 text-lg text-muted-foreground'>
            A complete guide to setting up, managing, and growing your retail business with DuukaFlow.
          </p>
        </div>

        <div className='mx-auto mt-10 grid max-w-4xl grid-cols-2 gap-4 sm:grid-cols-4'>
          {stats.map((stat) => (
            <Card key={stat.label} className='rounded-2xl text-center'>
              <CardContent className='pt-4 pb-4'>
                <p className='text-2xl font-bold text-primary'>{stat.value}</p>
                <p className='text-xs text-muted-foreground'>{stat.label}</p>
              </CardContent>
            </Card>
          ))}
        </div>

        <div className='mx-auto mt-16 max-w-5xl'>
          <Tabs defaultValue='getting-started' className='w-full'>
            <TabsList className='grid w-full grid-cols-2 lg:grid-cols-4'>
              <TabsTrigger value='getting-started'>
                <Rocket className='mr-1.5 h-3.5 w-3.5' />
                Getting Started
              </TabsTrigger>
              <TabsTrigger value='features'>
                <Zap className='mr-1.5 h-3.5 w-3.5' />
                Features
              </TabsTrigger>
              <TabsTrigger value='workflows'>
                <RefreshCw className='mr-1.5 h-3.5 w-3.5' />
                Workflows
              </TabsTrigger>
              <TabsTrigger value='faq'>
                <FileText className='mr-1.5 h-3.5 w-3.5' />
                FAQ
              </TabsTrigger>
            </TabsList>

            <TabsContent value='getting-started' className='mt-8'>
              <div className='space-y-6'>
                <Card className='rounded-2xl'>
                  <CardHeader>
                    <CardTitle className='flex items-center gap-2'>
                      <CheckCircle2 className='h-5 w-5 text-emerald-500' />
                      Quick Start Guide
                    </CardTitle>
                  </CardHeader>
                  <CardContent>
                    <ol className='space-y-4'>
                      {[
                        {
                          title: 'Create your account',
                          desc: 'Sign up with your email and verify your account to get started.',
                        },
                        {
                          title: 'Set up your business',
                          desc: 'Add your business name, logo, and basic information in Settings.',
                        },
                        {
                          title: 'Add branches',
                          desc: 'Create one or more branches to organize your locations.',
                        },
                        {
                          title: 'Invite your team',
                          desc: 'Add staff members and assign roles (Admin, Manager, Staff).',
                        },
                        {
                          title: 'Add products',
                          desc: 'Create your product catalog with names, prices, and stock levels.',
                        },
                        {
                          title: 'Start selling',
                          desc: 'Use the POS to process your first sale and generate receipts.',
                        },
                      ].map((step, i) => (
                        <li key={i} className='flex gap-4'>
                          <span className='flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-bold text-primary'>
                            {i + 1}
                          </span>
                          <div>
                            <p className='font-medium text-foreground'>{step.title}</p>
                            <p className='text-sm text-muted-foreground'>{step.desc}</p>
                          </div>
                        </li>
                      ))}
                    </ol>
                  </CardContent>
                </Card>

                <div className='grid gap-4 sm:grid-cols-2'>
                  <Card className='rounded-2xl'>
                    <CardHeader>
                      <CardTitle className='flex items-center gap-2 text-base'>
                        <Shield className='h-4 w-4 text-blue-500' />
                        User Roles
                      </CardTitle>
                    </CardHeader>
                    <CardContent className='space-y-2 text-sm text-muted-foreground'>
                      <p><strong className='text-foreground'>Admin</strong> — Full access to all features and settings</p>
                      <p><strong className='text-foreground'>Manager</strong> — Manage inventory, sales, and staff</p>
                      <p><strong className='text-foreground'>Staff</strong> — Process sales and view assigned data</p>
                      <p><strong className='text-foreground'>Superadmin</strong> — Platform-level administration</p>
                    </CardContent>
                  </Card>

                  <Card className='rounded-2xl'>
                    <CardHeader>
                      <CardTitle className='flex items-center gap-2 text-base'>
                        <Smartphone className='h-4 w-4 text-purple-500' />
                        System Requirements
                      </CardTitle>
                    </CardHeader>
                    <CardContent className='space-y-2 text-sm text-muted-foreground'>
                      <p>Modern web browser (Chrome, Firefox, Safari, Edge)</p>
                      <p>Internet connection for real-time sync</p>
                      <p>USB or Bluetooth barcode scanner (optional)</p>
                      <p>Thermal receipt printer (optional)</p>
                    </CardContent>
                  </Card>
                </div>
              </div>
            </TabsContent>

            <TabsContent value='features' className='mt-8'>
              <div className='grid gap-4 sm:grid-cols-2 lg:grid-cols-3'>
                {features.map((feature) => {
                  const Icon = feature.icon;
                  return (
                    <Card key={feature.title} className='rounded-2xl transition-all hover:-translate-y-1 hover:shadow-lg'>
                      <CardHeader>
                        <div className='flex items-center justify-between'>
                          <div className='flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10'>
                            <Icon className='h-5 w-5 text-primary' />
                          </div>
                          <Badge variant={feature.statusVariant}>{feature.status}</Badge>
                        </div>
                        <CardTitle className='text-base'>{feature.title}</CardTitle>
                      </CardHeader>
                      <CardContent>
                        <p className='text-sm text-muted-foreground'>{feature.description}</p>
                      </CardContent>
                    </Card>
                  );
                })}
              </div>
            </TabsContent>

            <TabsContent value='workflows' className='mt-8'>
              <div className='space-y-6'>
                {workflows.map((workflow) => {
                  const Icon = workflow.icon;
                  return (
                    <Card key={workflow.title} className='rounded-2xl'>
                      <CardHeader>
                        <CardTitle className='flex items-center gap-2'>
                          <Icon className='h-5 w-5 text-primary' />
                          {workflow.title}
                        </CardTitle>
                      </CardHeader>
                      <CardContent>
                        <div className='grid gap-3 sm:grid-cols-2'>
                          {workflow.steps.map((step, i) => (
                            <div key={i} className='flex items-start gap-3 rounded-xl bg-muted/50 p-3'>
                              <span className='flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary'>
                                {i + 1}
                              </span>
                              <p className='text-sm text-muted-foreground'>{step}</p>
                            </div>
                          ))}
                        </div>
                      </CardContent>
                    </Card>
                  );
                })}
              </div>
            </TabsContent>

            <TabsContent value='faq' className='mt-8'>
              <Accordion type='single' collapsible className='w-full space-y-3'>
                {faqs.map((faq, i) => (
                  <Card key={i} className='rounded-2xl overflow-hidden'>
                    <AccordionItem value={`item-${i}`} className='border-0'>
                      <AccordionTrigger className='px-6 py-4 text-left font-medium hover:no-underline'>
                        {faq.question}
                      </AccordionTrigger>
                      <AccordionContent className='px-6 pb-4 text-sm text-muted-foreground'>
                        {faq.answer}
                      </AccordionContent>
                    </AccordionItem>
                  </Card>
                ))}
              </Accordion>
            </TabsContent>
          </Tabs>
        </div>

        <div className='mx-auto mt-16 max-w-5xl'>
          <Card className='rounded-2xl bg-linear-to-br from-primary/5 to-accent/5 border-primary/10'>
            <CardContent className='p-8 text-center'>
              <Heart className='mx-auto h-8 w-8 text-red-500' />
              <h3 className='mt-4 text-xl font-semibold text-foreground'>Ready to get started?</h3>
              <p className='mt-2 text-sm text-muted-foreground'>
                Join thousands of retailers managing their business with DuukaFlow.
              </p>
              <div className='mt-6 flex flex-wrap items-center justify-center gap-3'>
                <a
                  href='/onboarding'
                  className='inline-flex items-center gap-2 rounded-xl bg-primary px-6 py-3 text-sm font-medium text-primary-foreground transition-all hover:bg-primary/90 hover:shadow-lg'
                >
                  Start Free Trial
                  <ArrowRight className='h-4 w-4' />
                </a>
                <a
                  href='/pricing'
                  className='inline-flex items-center gap-2 rounded-xl border border-border bg-background px-6 py-3 text-sm font-medium text-foreground transition-all hover:bg-muted'
                >
                  View Pricing
                </a>
              </div>
            </CardContent>
          </Card>
        </div>

        <div className='mx-auto mt-12 max-w-5xl'>
          <div className='grid gap-4 sm:grid-cols-3'>
            <Card className='rounded-2xl text-center'>
              <CardContent className='pt-6 pb-6'>
                <Clock className='mx-auto h-6 w-6 text-amber-500' />
                <p className='mt-2 text-sm font-medium text-foreground'>24/7 Support</p>
                <p className='text-xs text-muted-foreground'>We are here to help</p>
              </CardContent>
            </Card>
            <Card className='rounded-2xl text-center'>
              <CardContent className='pt-6 pb-6'>
                <Tag className='mx-auto h-6 w-6 text-blue-500' />
                <p className='mt-2 text-sm font-medium text-foreground'>Regular Updates</p>
                <p className='text-xs text-muted-foreground'>New features monthly</p>
              </CardContent>
            </Card>
            <Card className='rounded-2xl text-center'>
              <CardContent className='pt-6 pb-6'>
                <Users className='mx-auto h-6 w-6 text-emerald-500' />
                <p className='mt-2 text-sm font-medium text-foreground'>Community</p>
                <p className='text-xs text-muted-foreground'>Join other retailers</p>
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </div>
  );
};
