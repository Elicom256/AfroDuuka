import type { ReactNode } from 'react';
import { useLocation } from 'react-router-dom';
import { ErrorBoundary } from './ErrorBoundary';

/**
 * Route-level recovery boundary (findings §8.2).
 *
 * The shell already mounts one `ErrorBoundary`, but it sits at the app root, so
 * a render throw anywhere still replaces the entire page — navbar, sidebar and
 * all. This one is mounted *inside* each layout around its `<Outlet/>`, so the
 * chrome that owns the failing screen stays on screen and the rest of the app
 * remains navigable.
 *
 * The pathname is the reset key: navigating to another route clears the error
 * without a manual "Try again", because the screen that failed is no longer the
 * one being rendered.
 */
export const RouteErrorBoundary = ({ children }: { children: ReactNode }) => {
  const { pathname } = useLocation();
  return <ErrorBoundary resetKey={pathname}>{children}</ErrorBoundary>;
};
