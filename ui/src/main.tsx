import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import App from './App.tsx';
import { store } from './app/store/app/store.ts';
import { Provider } from 'react-redux';
import { BrowserRouter } from 'react-router-dom';
import { ThemeProvider } from 'next-themes';
import { ErrorBoundary } from './components/ErrorBoundary';
import { collapseSlashes } from './lib/url';
import { setupServiceWorker } from './lib/pwa';

// A verification link from an inbox can carry a doubled leading slash: a FRONTEND_URL
// that ended in "/" made the backend emit https://host//verify-email. nginx still
// served the SPA (it merges slashes for the file lookup, then falls back to
// index.html), but the browser kept the doubled slash and the router — matching on the
// literal pathname — never matched //verify-email against its /verify-email route, so
// the click landed on the catch-all 404. The backend no longer emits those links;
// rewriting the address bar here rescues the ones already in people's inboxes before
// the router reads the location.
const normalizedPath = collapseSlashes(window.location.pathname);
if (normalizedPath !== window.location.pathname) {
  window.history.replaceState(
    window.history.state,
    '',
    normalizedPath + window.location.search + window.location.hash
  );
}

// Register the service worker before the app mounts so the offline shell is available
// on the first load after a deploy (no-op in dev).
setupServiceWorker();

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <BrowserRouter>
      <Provider store={store}>
        <ThemeProvider attribute='class' defaultTheme='dark' enableSystem>
          {/*
            A render throw used to unmount the entire tree and leave a blank page with
            no message and no way back (checked.md P2-25). The boundary keeps the blast
            radius to the screen that failed.
          */}
          <ErrorBoundary>
            <App />
          </ErrorBoundary>
        </ThemeProvider>
      </Provider>
    </BrowserRouter>
  </StrictMode>,
);
