import {
  AlertTriangle,
  TrendingDown,
  ClipboardX,
  EyeOff,
  Calculator,
  Smartphone,
  BarChart3,
  MessageSquareText,
  TrendingUp,
} from 'lucide-react';

const problems = [
  {
    icon: <TrendingDown className='h-5 w-5' />,
    text: 'Stockouts occur unexpectedly, resulting in lost sales and revenue.',
  },
  {
    icon: <ClipboardX className='h-5 w-5' />,
    text: 'Manual record-keeping leads to frequent and costly errors.',
  },
  {
    icon: <EyeOff className='h-5 w-5' />,
    text: 'Limited visibility prevents effective remote monitoring of operations.',
  },
  {
    icon: <Calculator className='h-5 w-5' />,
    text: 'Accurate profit calculation remains difficult without reliable data.',
  },
  {
    icon: <AlertTriangle className='h-5 w-5' />,
    text: 'Inventory theft and discrepancies often go undetected.',
  },
  {
    icon: <BarChart3 className='h-5 w-5' />,
    text: 'Business decisions are made using incomplete or unreliable information.',
  },
];

const solutions = [
  {
    icon: <Smartphone className='h-5 w-5' />,
    title: 'Real-time Inventory Tracking',
    description: 'Monitor every item across your business from any device — phone, tablet, or desktop.',
  },
  {
    icon: <MessageSquareText className='h-5 w-5' />,
    title: 'Automated WhatsApp Alerts',
    description: 'Receive instant low-stock notifications and daily sales summaries directly on WhatsApp.',
  },
  {
    icon: <BarChart3 className='h-5 w-5' />,
    title: 'Instant Profit & Loss Reports',
    description: 'Generate accurate margin reports with one click. Eliminate manual calculations.',
  },
  {
    icon: <TrendingUp className='h-5 w-5' />,
    title: 'DuukaFlow Assistant',
    description: 'Your AI-powered business advisor — delivering actionable insights and answers in natural language.',
  },
];

export const ProblemSolutionSection = () => {
  return (
    <section className='py-16 sm:py-20'>
      <div className='grid gap-16 lg:gap-20'>
        {/* Problems */}
        <div>
          <span className='inline-flex rounded-full bg-destructive/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-destructive'>
            The Problem
          </span>
          <h2 className='mt-4 text-3xl font-bold tracking-tight text-foreground sm:text-4xl'>
            Inventory challenges are eroding your profits
          </h2>
          <p className='mt-3 max-w-2xl text-muted-foreground'>
            Businesses of all sizes — from small shops to large enterprises — lose substantial revenue every year due to
            preventable stockouts, overstocking, and operational inefficiencies.
          </p>

          <div className='mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-3'>
            {problems.map((problem) => (
              <div
                key={problem.text}
                className='flex items-start gap-3 rounded-xl border border-destructive/20 bg-destructive/5 p-4'
              >
                <div className='mt-0.5 shrink-0 text-destructive'>{problem.icon}</div>
                <p className='text-sm text-foreground/80'>{problem.text}</p>
              </div>
            ))}
          </div>
        </div>

        {/* Solutions */}
        <div>
          <span className='inline-flex rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-primary'>
            The Solution
          </span>
          <h2 className='mt-4 text-3xl font-bold tracking-tight text-foreground sm:text-4xl'>
            DuukaFlow puts you back in control
          </h2>
          <p className='mt-3 max-w-2xl text-muted-foreground'>
            Real-time visibility, automated workflows, and intelligent insights — everything you need to manage your
            inventory professionally and profitably.
          </p>

          <div className='mt-8 grid gap-4 sm:grid-cols-2'>
            {solutions.map((solution) => (
              <div
                key={solution.title}
                className='group rounded-2xl border border-border/60 bg-card/50 p-5 transition hover:border-primary/30 hover:bg-card'
              >
                <div className='flex items-start gap-4'>
                  <div className='inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary'>
                    {solution.icon}
                  </div>
                  <div>
                    <h3 className='font-semibold text-foreground'>{solution.title}</h3>
                    <p className='mt-1 text-sm text-muted-foreground'>{solution.description}</p>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
};
