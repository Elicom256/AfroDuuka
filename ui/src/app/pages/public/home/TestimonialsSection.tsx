import { Star, Quote } from 'lucide-react';

const testimonials = [
  {
    quote:
      'DuukaFlow completely changed how I manage my shop. The WhatsApp alerts alone saved me from running out of stock during the holiday season.',
    name: 'Grace N.',
    role: 'Shop Owner, Kampala',
    rating: 5,
  },
  {
    quote:
      'I used to spend hours on spreadsheets. Now I just check my phone. The DuukaFlow Assistant helps me understand which products are selling best.',
    name: 'Samuel O.',
    role: 'Retail Store Manager, Jinja',
    rating: 5,
  },
  {
    quote:
      'Managing three branches was a nightmare until DuukaFlow. Now I see all my stock levels from one dashboard. Game changer.',
    name: 'Hajjat Aisha M.',
    role: 'Business Owner, Mbale',
    rating: 5,
  },
];

export const TestimonialsSection = () => {
  return (
    <section className='py-16 sm:py-20'>
      <div className='text-center'>
        <span className='inline-flex rounded-full bg-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.24em] text-primary'>
          Testimonials
        </span>
        <h2 className='mt-4 text-3xl font-bold tracking-tight text-foreground sm:text-4xl'>
          Loved by shop owners like you
        </h2>
        <p className='mx-auto mt-3 max-w-2xl text-muted-foreground'>
          Hear from business owners who have made the switch from notebooks and spreadsheets to DuukaFlow.
        </p>
      </div>

      <div className='mt-10 grid gap-6 md:grid-cols-3'>
        {testimonials.map((testimonial) => (
          <div
            key={testimonial.name}
            className='relative rounded-2xl border border-border/60 bg-card/50 p-6'
          >
            <Quote className='absolute right-4 top-4 h-8 w-8 text-primary/10' />
            <div className='flex gap-1'>
              {Array.from({ length: testimonial.rating }).map((_, i) => (
                <Star key={i} className='h-4 w-4 fill-amber-400 text-amber-400' />
              ))}
            </div>
            <p className='mt-4 text-sm leading-relaxed text-foreground/80'>{testimonial.quote}</p>
            <div className='mt-6 border-t border-border/40 pt-4'>
              <p className='font-semibold text-foreground'>{testimonial.name}</p>
              <p className='text-sm text-muted-foreground'>{testimonial.role}</p>
            </div>
          </div>
        ))}
      </div>
    </section>
  );
};
