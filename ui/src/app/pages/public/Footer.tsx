import { useEffect, useState } from "react";
import type { ComponentType, SVGProps } from "react";
import { Download, Link2, Mail, MessageCircle } from "lucide-react";
import { toast } from "sonner";

import {
  FacebookMark,
  GithubMark,
  LinkedInMark,
  XMark,
} from "@/app/components/BrandIcons";

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
type BeforeInstallPromptEvent = Event & {
  prompt: () => Promise<void>;
  userChoice: Promise<{ outcome: "accepted" | "dismissed" }>;
};

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
  const [deferredPrompt, setDeferredPrompt] =
    useState<BeforeInstallPromptEvent | null>(null);
  const [installed, setInstalled] = useState(
    () =>
      typeof window !== "undefined" &&
      (window.matchMedia("(display-mode: standalone)").matches ||
        (window.navigator as unknown as { standalone?: boolean }).standalone ===
          true),
  );
  const [isIos] = useState(
    () =>
      typeof window !== "undefined" &&
      /iPad|iPhone|iPod/.test(navigator.userAgent),
  );

  useEffect(() => {
    if (typeof window === "undefined") return;

    const onBeforeInstallPrompt = (event: Event) => {
      event.preventDefault();
      setDeferredPrompt(event as BeforeInstallPromptEvent);
    };
    const onAppInstalled = () => {
      setInstalled(true);
      setDeferredPrompt(null);
      toast.success("AfroDuuka installed.");
    };

    window.addEventListener("beforeinstallprompt", onBeforeInstallPrompt);
    window.addEventListener("appinstalled", onAppInstalled);
    return () => {
      window.removeEventListener("beforeinstallprompt", onBeforeInstallPrompt);
      window.removeEventListener("appinstalled", onAppInstalled);
    };
  }, []);

  const handleInstall = async () => {
    if (installed) {
      toast('AfroDuuka is already installed.');
      return;
    }
    if (deferredPrompt) {
      await deferredPrompt.prompt();
      const choice = await deferredPrompt.userChoice;
      if (choice.outcome === "accepted") setDeferredPrompt(null);
      return;
    }
    if (isIos) {
      toast('To install: tap Share, then "Add to Home Screen".', {
        duration: 6000,
      });
      return;
    }
    toast("Use your browser menu to install AfroDuuka.");
  };

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

          <button
              type="button"
              onClick={handleInstall}
              className="inline-flex items-center gap-2 rounded-full border border-primary/70 bg-primary/10 p-2 text-sm text-primary transition hover:bg-primary hover:text-primary-foreground"
            >
              <Download className="h-4 w-4" />
              <span>Install app</span>
            </button>
        </div>

        <div className="mt-8 border-t border-border/50 pt-6 text-sm text-muted-foreground text-center">
          <p>
            Made with care in Uganda. © {new Date().getFullYear()} AfroDuuka.
          </p>
        </div>
      </div>
    </footer>
  );
};
