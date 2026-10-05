import { Component, type ErrorInfo, type ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { RefreshCw } from 'lucide-react';

/**
 * The last thing standing between a render throw and a blank page.
 *
 * A component that throws unmounts the whole React tree by default, so the browser
 * shows white with no message, no way back and nothing in the console a user could
 * report. That is what checked.md P2-25 describes, and it is the failure mode that makes
 * every other frontend bug hard to diagnose.
 *
 * `fallback` overrides the default screen for the rare case a section wants its own.
 */
type Props = { children: ReactNode; fallback?: (reset: () => void, error: Error) => ReactNode };

type State = { error: Error | null };

export class ErrorBoundary extends Component<Props, State> {
  state: State = { error: null };

  static getDerivedStateFromError(error: Error): State {
    return { error };
  }

  componentDidCatch(error: Error, info: ErrorInfo) {
    // Kept in the console because there is no error reporting service yet (checked.md
    // §9). Once one exists this is the single place it should be wired in.
    console.error('Unhandled UI error', error, info.componentStack);
  }

  reset = () => this.setState({ error: null });

  render() {
    const { error } = this.state;

    if (!error) return this.props.children;
    if (this.props.fallback) return this.props.fallback(this.reset, error);

    return (
      <div className='flex min-h-[60vh] items-center justify-center p-6'>
        <div className='max-w-md space-y-4 text-center'>
          <h1 className='text-xl font-semibold'>Something went wrong on this page</h1>
          <p className='text-sm text-muted-foreground'>
            The rest of the app is still usable. Try again, and if it keeps happening the details are in the
            browser console.
          </p>
          <pre className='max-h-32 overflow-auto rounded-md bg-muted p-3 text-left text-xs text-muted-foreground'>
            {error.message}
          </pre>
          <Button onClick={this.reset}>
            <RefreshCw className='mr-2 h-4 w-4' />
            Try again
          </Button>
        </div>
      </div>
    );
  }
}
