import { lazy, Suspense, type ComponentType, type ReactNode } from 'react';

/**
 * Defers a named component export — and, transitively, everything it imports —
 * until it is first rendered (findings §8.3).
 *
 * The chart widgets pull in chart.js, a ~190 KB chunk. Importing them statically
 * from the dashboard page put that chunk on the initial dashboard paint even
 * though the figures below the fold do not need it yet. This keeps them off the
 * critical path and shows `fallback` while the chunk loads.
 *
 * Call it at module scope: the lazy component must be created once, not on every
 * render.
 */
export const deferNamed = <P extends object>(
  loader: () => Promise<Record<string, unknown>>,
  name: string,
  fallback: ReactNode,
): ComponentType<P> => {
  const Deferred = lazy(() => loader().then((m) => ({ default: m[name] as ComponentType<P> })));
  const Wrapped = (props: P) => (
    <Suspense fallback={fallback}>
      <Deferred {...props} />
    </Suspense>
  );
  Wrapped.displayName = `Deferred(${name})`;
  return Wrapped;
};
