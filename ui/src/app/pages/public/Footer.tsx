import type { ComponentType, SVGProps } from 'react';
import { Link2, Mail, MessageCircle } from 'lucide-react';

import {
  FacebookMark,
  GithubMark,
  LinkedInMark,
  XMark,
  type IconProps,
} from '@/app/components/BrandIcons';

/**
 * lucide-react rather than react-icons: react-icons/fa6 alone pulls roughly 1,500
 * brand glyphs into the bundle to render the six below, and the project already
 * standardises on lucide everywhere else. The four real brand marks are inlined in
 * BrandIcons.tsx, since lucide deliberately ships none.
 *
 * Icon is typed as a plain SVG component rather than LucideIcon because the list
 * mixes the two: LucideIcon's return type is ReactNode, which does not satisfy the
 * narrower signature the inline marks have.
 */
const socials: { name: string; href: string; Icon: ComponentType<SVGProps<SVGSVGElement>> }[] = [
  {
    name: 'GitHub',
    href: 'https://github.com/Elijah-ug',
    Icon: GithubMark,
  },
  {
    name: 'X',
    href: 'https://x.com/ElicomElijah?t=9gAYJg6agmVW0GCKtYwPSA&s=08',
    Icon: XMark,
  },
  {
    name: 'LinkedIn',
    href: 'https://www.linkedin.com/in/mugisha-elijah-88a291239/',
    Icon: LinkedInMark,
  },
  {
    name: 'Email',
    href: 'elicomelijah330@gmail.com',
    Icon: Mail,
  },
  {
    name: 'Facebook',
    href: 'https://www.facebook.com/profile.php?id=61594724313686',
    Icon: FacebookMark,
  },
  {
    name: 'WhatsApp',
    href: 'https://wa.me/256731794401/?text=Hello%20Elicom%2C%20auto%20inserted/',
    Icon: MessageCircle,
  },
  {
    name: 'Portfolio',
    href: 'https://elicomelijah.vercel.app/',
    Icon: Link2,
  },
];

export const Footer: React.FC = () => {
  return (
    <footer className='border-t border-border/60 bg-slate-950 py-8'>
      <div className='container mx-auto px-4'>
        <div className='flex flex-col gap-6 md:flex-row md:items-center md:justify-between'>
          <div>
            <p className='text-sm font-semibold tracking-tight text-foreground'>AfroDuuka</p>
            <p className='mt-1 text-sm text-muted-foreground'>
              A modern inventory management experience for Ugandan shops.
            </p>
            <div className='text-sm text-muted-foreground flex items-center gap-1'>
              <span>Checkout other amazing software systems from</span>
              <a className='text-green-400 hover:underline' href='https://elicomelijah.vercel.app/' target='_blank'>
                AfroBlocks
              </a>
            </div>
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
                <social.Icon className='h-4 w-4' />
                <span>{social.name}</span>
              </a>
            ))}
          </div>
        </div>

        <div className='mt-8 border-t border-border/50 pt-6 text-sm text-muted-foreground text-center'>
          <p>Made with care in Uganda. © {new Date().getFullYear()} AfroDuuka.</p>
        </div>
      </div>
    </footer>
  );
};
