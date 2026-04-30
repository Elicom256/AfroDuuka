import { Link, Mail } from 'lucide-react';
import { FaGithub, FaLinkedinIn, FaWhatsapp, FaXTwitter } from 'react-icons/fa6';

const socials = [
  {
    name: 'GitHub',
    href: 'https://github.com/Elijah-ug',
    icon: <FaGithub />,
  },
  {
    name: 'X',
    href: 'https://x.com/ElicomElijah?t=9gAYJg6agmVW0GCKtYwPSA&s=08',
    icon: <FaXTwitter className='h-4 w-4' />,
  },
  {
    name: 'LinkedIn',
    href: 'https://www.linkedin.com/in/mugisha-elijah-88a291239/',
    icon: <FaLinkedinIn className='h-4 w-4' />,
  },
  {
    name: 'Email',
    href: 'elicomelijah330@gmail.com',
    icon: <Mail className='h-4 w-4' />,
  },
  {
    name: 'WhatsApp',
    href: 'https://wa.me/256781490899/?text=Hello%20Elicom%2C%20auto%20inserted/',
    icon: <FaWhatsapp className='h-4 w-4' />,
  },
  {
    name: 'Portfolio',
    href: 'https://elicomelijah.vercel.app/',
    icon: <Link className='h-4 w-4' />,
  },
];

export const Footer: React.FC = () => {
  return (
    <footer className='border-t border-border/50 bg-background/95 py-8'>
      <div className='container mx-auto px-4'>
        <div className='flex flex-col gap-6 md:flex-row md:items-center md:justify-between'>
          <div>
            <p className='text-sm font-semibold tracking-tight text-foreground'>DuukaFlow</p>
            <p className='mt-1 text-sm text-muted-foreground'>
              A modern inventory management experience for Ugandan shops.
            </p>
          </div>

          <div className='flex flex-wrap gap-3'>
            {socials.map((social) => (
              <a
                key={social.name}
                href={social.href}
                target='_blank'
                rel='noreferrer'
                className='inline-flex items-center gap-2 rounded-full border border-border/70 bg-card/70 px-4 py-2 text-sm text-muted-foreground transition hover:border-primary hover:text-foreground hover:bg-background'
              >
                {social.icon}
                <span>{social.name}</span>
              </a>
            ))}
          </div>
        </div>

        <div className='mt-8 border-t border-border/50 pt-6 text-sm text-muted-foreground'>
          <p>Made with care in Uganda. © {new Date().getFullYear()} DuukaFlow.</p>
        </div>
      </div>
    </footer>
  );
};
