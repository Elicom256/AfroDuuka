import { MapPin, Users, Heart } from 'lucide-react';
import { SectionHeader } from '@/app/pages/public/components/SectionHeader';

export const AboutSection = () => {
  return (
    <section className='py-16 sm:py-20'>
      <div className='grid gap-8 rounded-[2rem] border border-border/60 bg-card/60 p-8 shadow-sm md:grid-cols-2 md:p-12'>
        <div>
          <SectionHeader
            badge='Built for Uganda'
            title='Inventory management designed for local businesses'
            description='DuukaFlow gives you real-time inventory visibility, automated alerts, and simple workflows — without the complexity of enterprise software.'
          />
          <div className='mt-6 flex flex-wrap gap-4'>
            <div className='flex items-center gap-2 text-sm text-muted-foreground'>
              <MapPin className='h-4 w-4 text-primary' />
              <span>Made in Uganda</span>
            </div>
            <div className='flex items-center gap-2 text-sm text-muted-foreground'>
              <Users className='h-4 w-4 text-primary' />
              <span>Built for local businesses</span>
            </div>
            <div className='flex items-center gap-2 text-sm text-muted-foreground'>
              <Heart className='h-4 w-4 text-primary' />
              <span>Supported by real people</span>
            </div>
          </div>
        </div>
        <div className='space-y-4 text-sm leading-7 text-muted-foreground'>
          <p>
            From market stalls to established retailers, DuukaFlow is designed to keep your stock organized, easy to
            update, and ready for customer demand. No complicated setup — just a clean, intuitive interface that works
            on any device.
          </p>
          <p>
            Whether you run a single shop or multiple branches, DuukaFlow helps you track inventory in real time, receive
            WhatsApp alerts when stock runs low, and make smarter decisions with the DuukaFlow Assistant.
          </p>
          <p>
            Stop guessing. Start growing. DuukaFlow is the modern inventory partner for Ugandan businesses.
          </p>
        </div>
      </div>
    </section>
  );
};
