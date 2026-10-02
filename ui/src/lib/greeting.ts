import { useEffect, useState } from 'react';

/**
 * Time-of-day greeting for the dashboard headers.
 *
 * Deliberately local browser time rather than the business timezone: this is a
 * salutation for the person looking at the screen, not a business-clock figure.
 */
export const greetingFor = (date: Date): string => {
  const hour = date.getHours();

  if (hour < 12) return 'Good morning';
  if (hour < 17) return 'Good afternoon';

  return 'Good evening';
};

/**
 * The current greeting, re-read once a minute.
 *
 * Without the tick the header is frozen at whatever time the page happened to
 * render, so a dashboard left open from breakfast reads "Good morning" all
 * evening. One minute is frequent enough for an hourly boundary and cheap
 * enough to leave on permanently — but the state only changes when the greeting
 * actually changes, so the interval re-renders nothing on every tick.
 */
export const useTimeGreeting = (): string => {
  const [greeting, setGreeting] = useState(() => greetingFor(new Date()));

  useEffect(() => {
    const id = window.setInterval(() => {
      const next = greetingFor(new Date());

      setGreeting((current) => (current === next ? current : next));
    }, 60_000);

    return () => window.clearInterval(id);
  }, []);

  return greeting;
};