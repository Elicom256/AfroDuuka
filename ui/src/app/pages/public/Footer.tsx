import type { ComponentType, SVGProps } from "react";
import { Download, Link2, Mail, MessageCircle } from "lucide-react";
import { Button } from "@/components/ui/button";

import {
  FacebookMark,
  GithubMark,
  LinkedInMark,
  XMark,
} from "@/app/components/BrandIcons";
import { usePwaInstall } from "@/lib/pwaInstall";

const socials: {
  name: string;
  href: string;
  Icon: ComponentType<SVGProps<SVGSVGElement>>;
}[] = [
  {
    name: "GitHub",
    href: "https://github.com/Elijah-ug",
    Icon: GithubMark,
  },
  {
    name: "X",
    href: "https://x.com/ElicomElijah?t=9gAYJg6agmVW0GCKtYwPSA&s=08",
    Icon: XMark,
  },
  {
    name: "LinkedIn",
    href: "https://www.linkedin.com/in/mugisha-elijah-88a291239/",
    Icon: LinkedInMark,
  },
  {
    name: "Email",
    href: "elicomelijah330@gmail.com",
    Icon: Mail,
  },
  {
    name: "Facebook",
    href: "https://www.facebook.com/profile.php?id=61594724313686",
    Icon: FacebookMark,
  },
  {
    name: "WhatsApp",
    href: "https://wa.me/256731794401/?text=Hello%20Elicom%2C%20auto%20inserted/",
    Icon: MessageCircle,
  },
  {
    name: "Portfolio",
    href: "https://elicomelijah.vercel.app/",
    Icon: Link2,
  },
];

export const Footer: React.FC = () => {
  const { promptInstall } = usePwaInstall();

  return (
    <footer className="border-t border-border/60 bg-slate-950 py-8">
      <div className="container mx-auto px-4">
        <div className="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
          <div>
            <p className="text-sm font-semibold tracking-tight text-foreground">
              AfroDuuka
            </p>
            <p className="mt-1 text-sm text-muted-foreground">
              A modern inventory management experience for Ugandan shops.
            </p>
            <div className="text-sm text-muted-foreground flex items-center gap-1">
              <span>Checkout other amazing software systems from</span>
              <a
                className="text-green-400 hover:underline"
                href="https://elicomelijah.vercel.app/"
                target="_blank"
              >
                AfroBlocks
              </a>
            </div>
          </div>

          <div className="flex flex-wrap gap-3">
            {socials.map((social) => (
              <a
                key={social.name}
                href={social.href}
                target="_blank"
                rel="noreferrer"
                className="inline-flex items-center gap-2 rounded-full border border-border/70 bg-card/70 px-4 py-2 text-sm text-muted-foreground transition hover:border-primary hover:text-foreground hover:bg-background"
              >
                <social.Icon className="h-4 w-4" />
                <span>{social.name}</span>
              </a>
            ))}
          </div>

          <Button
            type="button"
            onClick={promptInstall}
            className="self-start rounded-full"
          >
            <Download className="h-4 w-4" />
            Install app
          </Button>
        </div>

        <div className="mt-8 border-t border-border/50 pt-6 text-sm text-muted-foreground text-center">
          <p>Made with care in Uganda. © {new Date().getFullYear()} AfroDuuka.</p>
        </div>
      </div>
    </footer>
  );
};
