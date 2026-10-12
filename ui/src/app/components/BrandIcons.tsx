import type { SVGProps } from 'react';

/**
 * Brand marks for the footer.
 *
 * lucide-react dropped its brand icons, and react-icons/fa6 was the reason we were
 * pulling ~1,500 glyphs into the bundle for six links. Brand logos have no
 * generic equivalent — MessageCircle is a speech bubble, not WhatsApp — so these four
 * are inlined. At roughly 300 bytes each that is a few kilobytes against the several
 * hundred the icon library cost.
 *
 * All four use fill="currentColor" on a 24x24 box so they inherit the link colour and
 * the surrounding text size like any other icon.
 */

type IconProps = SVGProps<SVGSVGElement>;

export type { IconProps };

const base = {
  xmlns: 'http://www.w3.org/2000/svg',
  viewBox: '0 0 24 24',
  width: '1em',
  height: '1em',
  fill: 'currentColor',
  'aria-hidden': true,
} satisfies IconProps;

export const GithubMark = (props: IconProps) => (
  <svg {...base} {...props}>
    <path d="M12 .5C5.37.5 0 5.87 0 12.5c0 5.3 3.44 9.8 8.21 11.39.6.11.82-.26.82-.58 0-.29-.01-1.05-.02-2.06-3.34.73-4.04-1.61-4.04-1.61-.55-1.39-1.34-1.76-1.34-1.76-1.09-.75.08-.73.08-.73 1.21.08 1.84 1.24 1.84 1.24 1.07 1.84 2.81 1.31 3.5 1 .11-.78.42-1.31.76-1.61-2.67-.3-5.47-1.34-5.47-5.95 0-1.31.47-2.38 1.24-3.22-.13-.3-.54-1.52.11-3.18 0 0 1.01-.32 3.3 1.23a11.5 11.5 0 0 1 6.01 0c2.29-1.55 3.3-1.23 3.3-1.23.65 1.66.24 2.88.12 3.18.77.84 1.23 1.91 1.23 3.22 0 4.62-2.8 5.64-5.48 5.94.43.37.81 1.1.81 2.22 0 1.6-.02 2.89-.02 3.28 0 .32.22.7.83.58A12.01 12.01 0 0 0 24 12.5C24 5.87 18.63.5 12 .5z" />
  </svg>
);

export const XMark = (props: IconProps) => (
  <svg {...base} {...props}>
    <path d="M18.9 2.3h3.5l-7.65 8.74L23.7 22h-6.86l-5.37-7.02L5.4 22H1.9l8.2-9.37L1.3 2.3h7.03l4.86 6.42L18.9 2.3zm-1.23 17.9h1.94L7.4 4.02H5.34L17.67 20.2z" />
  </svg>
);

export const LinkedInMark = (props: IconProps) => (
  <svg {...base} {...props}>
    <path d="M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.85 0-2.14 1.45-2.14 2.94v5.67H9.35V9h3.41v1.56h.05c.48-.9 1.63-1.85 3.36-1.85 3.6 0 4.27 2.37 4.27 5.45v6.29zM5.34 7.43a2.06 2.06 0 1 1 0-4.13 2.06 2.06 0 0 1 0 4.13zM7.12 20.45H3.55V9h3.57v11.45zM22.22 0H1.77C.79 0 0 .77 0 1.72v20.55C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.73V1.72C24 .77 23.2 0 22.22 0z" />
  </svg>
);

export const FacebookMark = (props: IconProps) => (
  <svg {...base} {...props}>
    <path d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.09 10.13 24v-8.44H7.08v-3.49h3.04V9.41c0-3.02 1.8-4.7 4.54-4.7 1.31 0 2.68.24 2.68.24v2.97h-1.5c-1.5 0-1.96.93-1.96 1.89v2.26h3.32l-.53 3.49h-2.8V24C19.62 23.09 24 18.1 24 12.07z" />
  </svg>
);