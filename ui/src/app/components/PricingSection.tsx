import { useState } from 'react';
import { Link } from 'react-router-dom';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { SectionHeader } from '@/app/pages/public/components/SectionHeader';
import { useGetPlansQuery } from '@/app/store/features/plans/plansQuery';
import { Skeleton } from '@/components/ui/skeleton';
import { Separator } from '@/components/ui/separator';
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from '@/components/ui/accordion';
import { 
  Check, 
  Percent, 
  Sparkles, 
  Smartphone, 
  ShieldCheck, 
  Building2, 
  Bot, 
  Receipt, 
  X,
  HelpCircle,
  ArrowRight,
  Database,
  LineChart,
  ShieldAlert,
  Clock
} from 'lucide-react';

interface Limits {
  maxProducts?: number;
  maxBranches?: number;
  maxUsers?: number;
}

interface Plan {
  id: number;
  name: string;
  slug: string;
  mark: string;
  description?: string;
  monthly_price: string;
  yearly_price: string;
  discount_percentage?: number;
  billing_cycle?: string;
  features?: string[];
  limits?: Limits;
  is_active?: boolean;
  sort_order?: number;
  currency: string;
}

const computeDiscounted = (price: number, discountPercent: number) => {
  if (!discountPercent) return price;
  return price - (price * discountPercent) / 100;
};

const PlanCard = ({ plan }: { plan: Plan }) => {
  const isMostPopular = plan.mark.toLowerCase().includes('popular');
  const monthlyPrice = Number(plan.monthly_price);
  const yearlyPrice = Number(plan.yearly_price);
  const discountPct = plan.discount_percentage ?? 0;
  const hasDiscount = discountPct > 0;

  const discountedMonthly = computeDiscounted(monthlyPrice, discountPct);
  const discountedYearly = computeDiscounted(yearlyPrice, discountPct);

  return (
    <div className='mt-6 h-full overflow-visible'>
      <Card
        className={`relative flex h-full flex-col pt-8 overflow-visible transition-all duration-300 ${
          isMostPopular
            ? 'border-primary/50 bg-linear-to-br from-primary/5 to-primary/0 shadow-xl ring-1 ring-primary/20'
            : 'border-border/70 hover:border-border hover:shadow-md'
        }`}
      >
        {hasDiscount && (
          <div className='absolute -top-4 left-1/2 -translate-x-1/2 z-50'>
            <Badge className='flex items-center gap-1 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold px-3 py-0.5 shadow-md'>
              <Percent className='h-3.5 w-3.5' />
              {Math.round(discountPct)}% OFF
            </Badge>
          </div>
        )}

        <CardHeader className='pb-4'>
          <div className='flex items-start justify-between gap-4'>
            <div className='flex-1'>
              <CardTitle className='text-xl font-bold flex items-center justify-between'>
                <span>{plan.name}</span>
                {isMostPopular && (
                  <div className='flex items-center justify-end'>
                    <Badge className='bg-purple-50 text-purple-700 dark:bg-purple-950 dark:text-purple-300'>
                      <Sparkles className='h-3.5 w-3.5 text-white' />
                      {plan.mark}
                    </Badge>
                  </div>
                )}
              </CardTitle>
              <CardDescription className='mt-1 text-sm line-clamp-2'>{plan.description}</CardDescription>
            </div>
          </div>
        </CardHeader>

        <CardContent className='flex-1 space-y-6'>
          <div className='space-y-2'>
            {hasDiscount ? (
              <div className='space-y-1'>
                <div className='flex items-baseline gap-2'>
                  <p className='text-2xl sm:text-3xl font-bold tracking-tight text-primary'>
                    {formatPrice(discountedMonthly, plan.currency)}
                  </p>
                  <span className='text-sm font-medium text-muted-foreground'>/mo</span>
                </div>
                <div className='flex items-center gap-2'>
                  <span className='text-sm text-muted-foreground line-through'>
                    {formatPrice(monthlyPrice, plan.currency)}
                  </span>
                  <span className='text-xs font-medium text-green-600 dark:text-green-400'>
                    Save {formatPrice(monthlyPrice - discountedMonthly, plan.currency)}/mo
                  </span>
                </div>
              </div>
            ) : (
              <div className='flex items-baseline gap-2'>
                <p className='text-2xl sm:text-3xl font-bold tracking-tight'>
                  {formatPrice(monthlyPrice, plan.currency)}
                </p>
                <span className='text-sm font-medium text-muted-foreground'>/mo</span>
              </div>
            )}

            {yearlyPrice > 0 && (
              <div>
                {hasDiscount ? (
                  <div className='flex items-center gap-2'>
                    <span className='text-xs font-medium text-green-600 dark:text-green-400'>
                      {formatPrice(discountedYearly, plan.currency)}
                    </span>
                    <span className='text-xs text-muted-foreground line-through'>
                      {formatPrice(yearlyPrice, plan.currency)}
                    </span>
                    <span className='text-xs text-muted-foreground'>billed yearly</span>
                  </div>
                ) : (
                  <p className='text-xs text-muted-foreground'>
                    {formatPrice(yearlyPrice, plan.currency)} billed yearly
                  </p>
                )}
              </div>
            )}
          </div>

          {plan.features && plan.features.length > 0 && (
            <ul className='space-y-3'>
              {plan.features.map((feature: string, i: number) => (
                <li key={i} className='flex items-start gap-3 text-sm'>
                  <Check className='h-4 w-4 mt-0.5 shrink-0 text-primary' />
                  <span className='text-foreground/80'>{feature}</span>
                </li>
              ))}
            </ul>
          )}
        </CardContent>

        <CardFooter className='mt-auto pt-6'>
          <Button className='w-full' variant={isMostPopular ? 'default' : 'outline'} asChild>
            <Link to='/signup'>Get Started</Link>
          </Button>
        </CardFooter>
      </Card>
    </div>
  );
};

const formatPrice = (price: number, currency: string) => {
  const rounded = Math.round(price);
  if (currency === 'UGX') {
    return `UGX ${rounded.toLocaleString()}`;
  }
  return `${currency} ${rounded.toLocaleString()}`;
};

const PricingCardSkeleton = () => (
  <Card className='border-border/70'>
    <CardHeader className='pb-4'>
      <div className='space-y-3'>
        <Skeleton className='h-6 w-24' />
        <Skeleton className='h-4 w-full max-w-xs' />
      </div>
    </CardHeader>
    <CardContent className='space-y-6'>
      <Skeleton className='h-10 w-32' />
      <div className='space-y-3'>
        {[...Array(5)].map((_, i) => (
          <Skeleton key={i} className='h-4 w-full' />
        ))}
      </div>
    </CardContent>
    <CardFooter>
      <Skeleton className='h-10 w-full' />
    </CardFooter>
  </Card>
);

export const PricingSection = () => {
  const { data: planData, isLoading: planLoading } = useGetPlansQuery();
  const plans = planData?.plans ?? [];

  // ROI Calculator State
  const [currency, setCurrency] = useState<'UGX' | 'USD'>('UGX');
  const [revenue, setRevenue] = useState<number>(3000000); // 3M UGX default
  const [branches, setBranches] = useState<number>(1);

  const formatCalcCurrency = (amount: number) => {
    return currency === 'UGX' 
      ? `UGX ${Math.round(amount).toLocaleString()}` 
      : `$${Math.round(amount).toLocaleString()}`;
  };

  const handleCurrencyToggle = (curr: 'UGX' | 'USD') => {
    setCurrency(curr);
    if (curr === 'UGX') {
      setRevenue(3000000);
    } else {
      setRevenue(1000);
    }
  };

  // ROI Computations:
  // 1. Stock Leakage/Fraud prevention (typically 8% of revenue is lost to stock leakage or unrecorded transactions in manual shops)
  const leakageSaved = revenue * 0.08 * branches;
  // 2. Bookkeeping time (12 hours saved per month per branch valued at regional wage estimation)
  const hoursSaved = 12 * branches;
  const wageValue = currency === 'UGX' ? 10000 : 3; // Est. 10,000 UGX or $3 per hour
  const timeSavedValue = hoursSaved * wageValue;
  // 3. Efficiency dividend (stock replenishment optimization adds 5% sales conversion boost)
  const salesBoost = revenue * 0.05 * branches;

  const totalMonthlyROI = leakageSaved + timeSavedValue + salesBoost;
  const annualROI = totalMonthlyROI * 12;

  return (
    <section id='pricing-section' className='mt-20 scroll-mt-24'>
      <SectionHeader
        badge='Pricing'
        title='Simple, transparent pricing'
        description='Choose the plan that fits your business. All plans include a 14-day free trial.'
      />
      
      {/* Plans Grid */}
      <div className='mt-10 grid gap-8 sm:grid-cols-2 lg:grid-cols-4'>
        {planLoading
          ? [1, 2, 3, 4].map((i) => <PricingCardSkeleton key={i} />)
          : plans.map((plan: any) => <PlanCard key={plan.id} plan={plan} />)}
      </div>

      {/* ROI Stats High-Converting Band */}
      <div className='mt-24 rounded-3xl border border-primary/20 bg-linear-to-br from-primary/5 to-primary/0 p-8 md:p-12 shadow-inner'>
        <div className='text-center max-w-3xl mx-auto'>
          <Badge className='bg-primary/10 hover:bg-primary/25 text-primary border-none px-4 py-1 text-xs font-semibold uppercase tracking-wider mb-4'>
            Enterprise Value
          </Badge>
          <h3 className='text-2xl md:text-3xl font-bold text-foreground tracking-tight'>
            Built to Propel Africa's Retail Renaissance
          </h3>
          <p className='mt-4 text-muted-foreground text-sm md:text-base leading-relaxed'>
            We engineered DuukaFlow because generic enterprise software is built for western markets, while African business owners face unique operational challenges. From informal roadside kiosks (<span className='italic font-medium text-primary'>duukas</span>) to multi-city retail chains, DuukaFlow transforms local commerce with robust, real-world utility.
          </p>
        </div>

        <div className='mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4'>
          {[
            {
              value: "98.5%",
              label: "Anti-Fraud Compliance",
              desc: "Instantly reconciles expected ledger items with register cash at shift handovers. Discrepancies trigger instant alerts.",
              icon: <ShieldAlert className='h-5 w-5 text-red-500' />
            },
            {
              value: "32.4%",
              label: "Margin Growth Boost",
              desc: "Intelligent reorder rules and expirations warning prevent lost sales, empty shelves, and dead stock.",
              icon: <LineChart className='h-5 w-5 text-emerald-500' />
            },
            {
              value: "0 Hours",
              label: "Manual Bookkeeping",
              desc: "Automate cash flow charts, sales charts, VAT summaries, and branch reports. Say goodbye to messy paper notebooks.",
              icon: <Clock className='h-5 w-5 text-blue-500' />
            },
            {
              value: "100%",
              label: "Mobile-Money Native",
              desc: "Accept MTN MoMo and Airtel Money directly on standard smartphones. Real-time API confirmation and zero hardware costs.",
              icon: <Smartphone className='h-5 w-5 text-purple-500' />
            }
          ].map((stat, idx) => (
            <div key={idx} className='bg-card border border-border/40 p-6 rounded-2xl shadow-xs hover:border-primary/20 transition-all duration-300'>
              <div className='flex items-center gap-3 mb-2'>
                <div className='p-2 rounded-lg bg-muted'>{stat.icon}</div>
                <span className='text-2xl font-black text-primary tracking-tight'>{stat.value}</span>
              </div>
              <h4 className='font-bold text-sm text-foreground mb-1'>{stat.label}</h4>
              <p className='text-xs text-muted-foreground leading-relaxed'>{stat.desc}</p>
            </div>
          ))}
        </div>
      </div>

      {/* Advanced SaaS Features & Core Value Props */}
      <div className='mt-24'>
        <div className='text-center max-w-2xl mx-auto'>
          <span className='inline-flex rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-primary'>
            SaaS Features
          </span>
          <h3 className='mt-4 text-3xl font-bold tracking-tight text-foreground sm:text-4xl'>
            Professional Core Engine Built for Scaling
          </h3>
          <p className='mt-3 text-muted-foreground'>
            Every line of code in DuukaFlow is tuned to deliver complete control, military-grade auditability, and modern efficiency.
          </p>
        </div>

        <div className='mt-12 grid gap-8 md:grid-cols-2 lg:grid-cols-3'>
          {[
            {
              icon: <Smartphone className='h-6 w-6 text-purple-500' />,
              title: "Direct Mobile Money POS Checkouts",
              description: "Over 70% of transactions in Sub-Saharan Africa occur via mobile wallets. Accept MTN MoMo, Airtel Money, Flutterwave, and Pesapal directly at your checkout. Customers get a push notification on their phone instantly, and the system logs payment confirmation securely."
            },
            {
              icon: <ShieldCheck className='h-6 w-6 text-emerald-500' />,
              title: "Digital Shift Handover & Anti-Fraud",
              description: "Retail shrinkage and cash leakage are the primary reasons retail outlets collapse. DuukaFlow creates strict audit trails. On sign-out, cashiers must enter cash-in-drawer. Discrepancies generate real-time WhatsApp or SMS alerts, giving you full control even when off-site."
            },
            {
              icon: <Bot className='h-6 w-6 text-blue-500' />,
              title: "Gemini 2.0 AI Retail Advisor",
              description: "Experience advanced artificial intelligence built into your core inventory. Speak or type in plain natural language to DuukaFlow AI to immediately track expiring items, fetch top-performing brands, draft reports, or check restock rules without navigating complicated menu layouts."
            },
            {
              icon: <Building2 className='h-6 w-6 text-amber-500' />,
              title: "Multi-Branch Live Sync",
              description: "Scale from a single boutique to regional chain operations. Manage branches across town or across countries with real-time sync. Record branch-level stock, execute high-security stock-transfers, and monitor real-time gross margins from a central control hub."
            },
            {
              icon: <Receipt className='h-6 w-6 text-red-500' />,
              title: "Tax Authority Compliant Invoicing",
              description: "Streamline tax filings. Generate automated VAT-compliant receipts, audit logs, and transaction structures aligned with local revenue authorities (such as Uganda Revenue Authority - URA). Save weeks of accounting headaches and stay audit-proof."
            },
            {
              icon: <Database className='h-6 w-6 text-indigo-500' />,
              title: "Offline-Resilient Architecture",
              description: "Power outages and fluctuating network coverage shouldn't stop your business. DuukaFlow runs an advanced browser-first synchronization database. Sell products, manage stock, and handle cash registers completely offline. All changes sync smoothly when the network returns."
            }
          ].map((prop, idx) => (
            <div key={idx} className='group bg-card border border-border/60 hover:border-primary/40 rounded-2xl p-6 transition-all duration-300 hover:shadow-lg flex flex-col'>
              <div className='p-3 bg-muted w-fit rounded-xl mb-4 group-hover:scale-110 transition-transform duration-300'>{prop.icon}</div>
              <h4 className='font-bold text-lg text-foreground mb-2'>{prop.title}</h4>
              <p className='text-sm text-muted-foreground leading-relaxed flex-1'>{prop.description}</p>
            </div>
          ))}
        </div>
      </div>

      {/* Dynamic ROI Savings Calculator Widget */}
      <div className='mt-24 grid gap-12 lg:grid-cols-12 items-center bg-card border border-border/80 rounded-3xl p-8 md:p-12 shadow-sm'>
        <div className='lg:col-span-5 space-y-4'>
          <Badge className='bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 border-none px-3 py-0.5 text-xs font-semibold'>
            ROI Calculator
          </Badge>
          <h3 className='text-2xl md:text-3xl font-bold text-foreground tracking-tight'>
            Calculate Your DuukaFlow Returns
          </h3>
          <p className='text-muted-foreground text-sm leading-relaxed'>
            See exactly how digitizing your business saves you cash, recaptures lost staff hours, and elevates your operational margins. Drag the sliders to match your monthly volume.
          </p>
          
          <div className='flex gap-2 p-1 bg-muted rounded-lg w-fit mt-4'>
            <button 
              className={`px-3 py-1.5 rounded-md text-xs font-semibold transition-all ${currency === 'UGX' ? 'bg-background text-foreground shadow-xs' : 'text-muted-foreground hover:text-foreground'}`}
              onClick={() => handleCurrencyToggle('UGX')}
            >
              UGX (Shillings)
            </button>
            <button 
              className={`px-3 py-1.5 rounded-md text-xs font-semibold transition-all ${currency === 'USD' ? 'bg-background text-foreground shadow-xs' : 'text-muted-foreground hover:text-foreground'}`}
              onClick={() => handleCurrencyToggle('USD')}
            >
              USD ($)
            </button>
          </div>
        </div>

        <div className='lg:col-span-7 bg-muted/30 border border-border/50 rounded-2xl p-6 space-y-6'>
          {/* Inputs */}
          <div className='space-y-4'>
            <div className='space-y-2'>
              <div className='flex justify-between text-sm'>
                <span className='font-medium text-muted-foreground'>Est. Monthly Revenue (per branch)</span>
                <span className='font-black text-foreground'>{formatCalcCurrency(revenue)}</span>
              </div>
              <input 
                type="range" 
                min={currency === 'UGX' ? 500000 : 200} 
                max={currency === 'UGX' ? 50000000 : 15000} 
                step={currency === 'UGX' ? 500000 : 200}
                value={revenue} 
                onChange={(e) => setRevenue(Number(e.target.value))}
                className='w-full accent-primary h-1.5 rounded-lg bg-border cursor-pointer'
              />
            </div>

            <div className='space-y-2'>
              <div className='flex justify-between text-sm'>
                <span className='font-medium text-muted-foreground'>Number of Branches</span>
                <span className='font-black text-foreground'>{branches} {branches === 1 ? 'Branch' : 'Branches'}</span>
              </div>
              <input 
                type="range" 
                min={1} 
                max={10} 
                step={1}
                value={branches} 
                onChange={(e) => setBranches(Number(e.target.value))}
                className='w-full accent-primary h-1.5 rounded-lg bg-border cursor-pointer'
              />
            </div>
          </div>

          <Separator />

          {/* Outputs */}
          <div className='grid gap-4 sm:grid-cols-3'>
            <div className='p-4 bg-background border border-border/40 rounded-xl'>
              <span className='text-xs text-muted-foreground block mb-1'>Prevented Leakage</span>
              <span className='text-lg font-bold text-red-500 block'>{formatCalcCurrency(leakageSaved)}</span>
              <span className='text-[10px] text-muted-foreground'>8% saved from theft</span>
            </div>
            <div className='p-4 bg-background border border-border/40 rounded-xl'>
              <span className='text-xs text-muted-foreground block mb-1'>Labor Value Saved</span>
              <span className='text-lg font-bold text-purple-500 block'>{formatCalcCurrency(timeSavedValue)}</span>
              <span className='text-[10px] text-muted-foreground'>{hoursSaved} admin hours/mo</span>
            </div>
            <div className='p-4 bg-background border border-border/40 rounded-xl'>
              <span className='text-xs text-muted-foreground block mb-1'>Smart Replenishment</span>
              <span className='text-lg font-bold text-emerald-500 block'>{formatCalcCurrency(salesBoost)}</span>
              <span className='text-[10px] text-muted-foreground'>5% sales boost</span>
            </div>
          </div>

          <div className='bg-primary/5 border border-primary/20 p-4 rounded-xl flex items-center justify-between'>
            <div>
              <span className='text-xs text-muted-foreground block'>Estimated Net Return</span>
              <span className='text-2xl font-black text-primary block'>{formatCalcCurrency(totalMonthlyROI)}<span className='text-xs text-muted-foreground font-normal'> / month</span></span>
            </div>
            <div className='text-right'>
              <span className='text-[11px] uppercase tracking-wider text-muted-foreground block'>Annual Saved</span>
              <span className='text-lg font-bold text-primary block'>{formatCalcCurrency(annualROI)}<span className='text-xs text-muted-foreground font-normal'> / yr</span></span>
            </div>
          </div>
        </div>
      </div>

      {/* Elegant Paper Ledger vs DuukaFlow Comparison Grid */}
      <div className='mt-24'>
        <div className='text-center max-w-2xl mx-auto mb-12'>
          <Badge className='bg-primary/10 text-primary border-none mb-3'>Platform Comparison</Badge>
          <h3 className='text-3xl font-bold text-foreground tracking-tight'>The Duuka Renaissance</h3>
          <p className='text-muted-foreground mt-2 text-sm'>
            See how DuukaFlow stacks up against ancient bookkeeping tools and complex offshore ERP options.
          </p>
        </div>

        <div className='overflow-x-auto rounded-2xl border border-border/80 bg-card shadow-xs'>
          <table className='w-full text-left border-collapse'>
            <thead>
              <tr className='border-b border-border bg-muted/40'>
                <th className='p-4 font-bold text-sm text-foreground md:w-1/4'>Capability</th>
                <th className='p-4 font-bold text-sm text-muted-foreground md:w-1/4'>Paper Ledger Book</th>
                <th className='p-4 font-bold text-sm text-muted-foreground md:w-1/4'>Generic Offshore ERP</th>
                <th className='p-4 font-bold text-sm text-primary md:w-1/4 bg-primary/5'>DuukaFlow Suite</th>
              </tr>
            </thead>
            <tbody className='divide-y divide-border/60 text-sm'>
              {[
                {
                  cap: "Theft & Stock Discrepancy Prevention",
                  paper: { status: false, text: "None. Discrepancies go unnoticed." },
                  erp: { status: false, text: "Slow. Requires advanced IT logs." },
                  duuka: { status: true, text: "Real-time shift verification & instant alerts." }
                },
                {
                  cap: "Local Payment Support",
                  paper: { status: false, text: "Manual logging, error-prone." },
                  erp: { status: false, text: "Designed for international credit cards." },
                  duuka: { status: true, text: "POS-integrated MTN MoMo & Airtel Money." }
                },
                {
                  cap: "AI Business Advice",
                  paper: { status: false, text: "Manual calculator needed." },
                  erp: { status: false, text: "Requires manual query scripting." },
                  duuka: { status: true, text: "Voice/text chat via Gemini 2.0 Agent." }
                },
                {
                  cap: "Multi-branch Inventory Sync",
                  paper: { status: false, text: "Physical ledger transport." },
                  erp: { status: false, text: "Prohibitive installation server costs." },
                  duuka: { status: true, text: "Instant background cloud synchronization." }
                },
                {
                  cap: "Offline Resilience",
                  paper: { status: true, text: "Unplugged (but easily lost or ruined)." },
                  erp: { status: false, text: "Crashes on connection drop." },
                  duuka: { status: true, text: "Local storage architecture with auto-sync." }
                }
              ].map((row, idx) => (
                <tr key={idx} className='hover:bg-muted/10 transition-colors'>
                  <td className='p-4 font-semibold text-foreground leading-tight'>{row.cap}</td>
                  
                  <td className='p-4 text-muted-foreground text-xs leading-relaxed'>
                    <div className='flex items-start gap-1.5'>
                      <X className='h-4 w-4 text-red-500 shrink-0 mt-0.5' />
                      <span>{row.paper.text}</span>
                    </div>
                  </td>
                  
                  <td className='p-4 text-muted-foreground text-xs leading-relaxed'>
                    <div className='flex items-start gap-1.5'>
                      <X className='h-4 w-4 text-red-500 shrink-0 mt-0.5' />
                      <span>{row.erp.text}</span>
                    </div>
                  </td>
                  
                  <td className='p-4 text-foreground text-xs font-medium leading-relaxed bg-primary/5 border-x border-primary/10'>
                    <div className='flex items-start gap-1.5'>
                      <Check className='h-4 w-4 text-emerald-500 shrink-0 mt-0.5' />
                      <span className='text-primary-foreground dark:text-foreground'>{row.duuka.text}</span>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {/* Interactive FAQ Section utilizing Accordion */}
      <div className='mt-24 max-w-4xl mx-auto'>
        <div className='text-center mb-10'>
          <span className='inline-flex rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-primary'>
            FAQ
          </span>
          <h3 className='mt-3 text-3xl font-bold tracking-tight text-foreground'>
            Frequently Asked Questions
          </h3>
          <p className='text-muted-foreground mt-2 text-sm'>
            Have more questions about deploying DuukaFlow for your retail business? We have the answers.
          </p>
        </div>

        <Accordion type="single" collapsible className="w-full space-y-4">
          {[
            {
              id: "faq-1",
              q: "How does DuukaFlow prevent employee theft and stock leakage?",
              a: "In Africa, employee cash discrepancies and unrecorded sales are the leading reasons retail duukas fail. DuukaFlow targets this by establishing structured shift reconciliations. On logout, attendants enter the physical cash/mobile money in drawer. The system matches this with expected ledger transactions, instantly highlighting any variance. Any discrepancy triggers a real-time summary notification sent directly to the business owner, preventing fraud."
            },
            {
              id: "faq-2",
              q: "Will DuukaFlow function when cellular or fiber internet is offline?",
              a: "Absolutely. We engineered DuukaFlow with African infrastructure in mind. It uses a local-first service design where sales, cashier commands, and stock-transfers operate fully offline inside browser and app storage. The moment internet is re-established, the records automatically and seamlessly synchronize back to the cloud."
            },
            {
              id: "faq-3",
              q: "What is the Mobile Money POS integration and how do I set it up?",
              a: "Instead of managing multiple phones and manual ledger records of transaction IDs, DuukaFlow allows you to initiate mobile money payments directly on the POS screen using MTN MoMo, Airtel Money, Flutterwave, and Pesapal. The POS prompts a secure API push notification (USSD) directly to the customer's phone, requiring only their PIN. Once completed, DuukaFlow receives instantaneous verification, triggers the cash drawer/printer, and secures the transaction record."
            },
            {
              id: "faq-4",
              q: "Do I need expensive barcode scanners or high-end computers?",
              a: "No. DuukaFlow is extremely lightweight and is optimized to run on standard Android or iOS smartphones, basic tablets, laptops, or older PC models. For receipt printing, it connects effortlessly to budget-friendly USB and Bluetooth thermal receipt printers, keeping setup costs exceptionally low."
            },
            {
              id: "faq-5",
              q: "Can I manage multiple branches in different cities?",
              a: "Yes. DuukaFlow is built for enterprise scalability. From a single admin login, you can create branches, manage distinct products at each branch, execute secure stock transfers, and monitor comparative sales analytics. It is perfect for managing operations across town or across country lines."
            },
            {
              id: "faq-6",
              q: "What makes DuukaFlow different from standard accounting tools?",
              a: "Standard tools are built for countries with widespread credit-card usage and stable fiber internet. DuukaFlow is tailor-made for African commerce. We integrate Mobile Money, build in strict anti-theft shift reconciliations, implement offline resilience, support local tax invoices, and offer a natural language Gemini AI agent that lets business owners control everything with simple, conversational English."
            }
          ].map((faq) => (
            <AccordionItem key={faq.id} value={faq.id} className='bg-card border border-border/80 rounded-xl px-5 py-1.5 hover:border-primary/40 transition-colors'>
              <AccordionTrigger className='text-sm md:text-base font-bold text-foreground hover:no-underline'>
                <div className='flex items-center gap-2.5 text-left'>
                  <HelpCircle className='h-4 w-4 text-primary shrink-0' />
                  <span>{faq.q}</span>
                </div>
              </AccordionTrigger>
              <AccordionContent className='text-sm text-muted-foreground leading-relaxed pt-2 pb-4 border-t border-border/40 mt-2'>
                {faq.a}
              </AccordionContent>
            </AccordionItem>
          ))}
        </Accordion>
      </div>

      {/* final CTA section */}
      <div className='mt-28 mb-12 rounded-3xl bg-linear-to-r from-primary to-primary-foreground text-primary-foreground p-8 md:p-12 text-center relative overflow-hidden shadow-xl'>
        <div className='absolute inset-0 bg-[radial-gradient(circle_at_30%_30%,rgba(255,255,255,0.1),transparent)] pointer-events-none' />
        <div className='relative z-10 max-w-2xl mx-auto space-y-6'>
          <h3 className='text-3xl md:text-4xl font-extrabold tracking-tight text-white'>
            Ready to Digitally Transform Your Retail Operations?
          </h3>
          <p className='text-white/80 text-sm md:text-base leading-relaxed'>
            Join thousands of modern merchants who have eliminated manual ledger errors, stopped stock theft, accepted mobile money checkout, and grown their business margins with DuukaFlow.
          </p>
          <div className='flex flex-col sm:flex-row gap-4 justify-center pt-4'>
            <Button size="lg" className='bg-amber-500 hover:bg-amber-600 text-white font-bold px-8 shadow-md border-none group transition-all duration-300' asChild>
              <Link to="/signup" className='flex items-center gap-2'>
                Start Your 14-Day Free Trial
                <ArrowRight className='h-4 w-4 group-hover:translate-x-1 transition-transform' />
              </Link>
            </Button>
            <Button size="lg" variant="outline" className='border-white/30 text-white bg-transparent hover:bg-white/10 hover:border-white/50 font-semibold px-8' asChild>
              <Link to="/documentation">Read Docs</Link>
            </Button>
          </div>
        </div>
      </div>
    </section>
  );
};
