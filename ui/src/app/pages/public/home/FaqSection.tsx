import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';

const faqs = [
  {
    question: 'What is DuukaFlow and how can it help my business?',
    answer:
      'DuukaFlow is an inventory management system built for Ugandan small and medium businesses. It helps you track stock in real time, get WhatsApp alerts when items run low, generate profit & loss reports, and access your data from anywhere. It replaces manual notebooks and spreadsheets with an automated, cloud-based system.',
  },
  {
    question: 'How do WhatsApp notifications work?',
    answer:
      'DuukaFlow sends instant low-stock alerts, daily sales summaries, purchase notifications, and important business updates directly to your WhatsApp. You do not need to install any extra apps — just connect your phone number during setup.',
  },
  {
    question: 'What is the DuukaFlow Assistant?',
    answer:
      'The DuukaFlow Assistant is an intelligent inventory assistant that understands natural language. You can ask it questions like "Which products are selling fastest this month?" or "What should I restock?" and get instant answers, insights, and recommendations.',
  },
  {
    question: 'Can I manage multiple shop branches?',
    answer:
      'Yes. DuukaFlow supports multi-branch management, allowing you to track inventory, sales, and performance across all your locations from a single dashboard.',
  },
  {
    question: 'How long does it take to set up?',
    answer:
      'Most businesses are up and running in under 15 minutes. Create your account, add your products, and start tracking inventory immediately. No technical skills required.',
  },
  {
    question: 'Is my data safe?',
    answer:
      'Absolutely. Your business data is encrypted, securely stored in the cloud, and backed up regularly. You can access it from any device with an internet connection.',
  },
  {
    question: 'What kind of reports can I generate?',
    answer:
      'DuukaFlow generates profit & loss reports, sales analytics, inventory summaries, and financial reports. You can see exactly what is selling, what is not, and where your money is going.',
  },
  {
    question: 'Is there a free trial?',
    answer:
      'Yes. All plans come with a 14-day free trial — no credit card required. You can explore all features and see how DuukaFlow works for your business before committing.',
  },
];

export const FaqSection = () => {
  return (
    <section className='py-16 sm:py-20'>
      <div className='text-center'>
        <span className='inline-flex rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-primary'>
          FAQ
        </span>
        <h2 className='mt-4 text-3xl font-bold tracking-tight text-foreground sm:text-4xl'>
          Frequently asked questions
        </h2>
        <p className='mx-auto mt-3 max-w-2xl text-muted-foreground'>
          Everything you need to know about DuukaFlow. Still have questions? Contact our support team.
        </p>
      </div>

      <div className='mx-auto mt-10 max-w-3xl'>
        <Accordion type='single' collapsible className='w-full'>
          {faqs.map((faq, index) => (
            <AccordionItem key={index} value={`item-${index}`}>
              <AccordionTrigger className='text-left'>{faq.question}</AccordionTrigger>
              <AccordionContent className='text-muted-foreground'>{faq.answer}</AccordionContent>
            </AccordionItem>
          ))}
        </Accordion>
      </div>
    </section>
  );
};
