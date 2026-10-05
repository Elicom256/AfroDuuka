import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import App from './App.tsx';
import { store } from './app/store/app/store.ts';
import { Provider } from 'react-redux';
import { BrowserRouter } from 'react-router-dom';
import { ThemeProvider } from 'next-themes';
import { ErrorBoundary } from './components/ErrorBoundary';

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
