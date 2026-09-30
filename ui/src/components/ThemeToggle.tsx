import { useTheme } from 'next-themes';
import { Moon, Sun } from 'lucide-react';
import { Button } from '@/components/ui/button';

export const ThemeToggle = ({ compact = false }: { compact?: boolean }) => {
  const { resolvedTheme, setTheme } = useTheme();
  const isDark = resolvedTheme === 'dark';
  const nextTheme = isDark ? 'light' : 'dark';

  return (
    <Button
      type='button'
      variant='outline'
      size={compact ? 'icon' : 'sm'}
      className={compact ? 'h-9 w-auto px-2' : 'gap-2'}
      aria-label={`Switch to ${nextTheme} mode`}
      aria-pressed={isDark}
      title={`Switch to ${nextTheme} mode`}
      onClick={() => setTheme(nextTheme)}
    >
      {isDark ? (
        <Sun key='light-mode' aria-hidden='true' className='h-4 w-4 text-amber-500 transition-transform duration-200' />
      ) : (
        <Moon key='dark-mode' aria-hidden='true' className='h-4 w-4 text-primary transition-transform duration-200' />
      )}
      {!compact && <span>{isDark ? 'Light mode' : 'Dark mode'}</span>}
    </Button>
  );
};
