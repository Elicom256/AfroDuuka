import { configureStore } from '@reduxjs/toolkit';
import { authListenerMiddleware } from './authListener';
import { apiErrorMiddleware } from '../apiErrorMiddleware';
import { baseApi } from '../baseApi';

export const store = configureStore({
  reducer: {
    [baseApi.reducerPath]: baseApi.reducer,
  },

  middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware().concat(
      // One middleware for every feature slice: all endpoints are injected into
      // baseApi, so a single reducer and a single middleware now serve the whole
      // app. Individual feature files are imported by the components that use
      // them, which is when their endpoints are registered.
      baseApi.middleware,
      // Must come last so it sees the rejection actions dispatched by every slice
      // above. This middleware was written but never added to this chain, which is
      // why nothing ever cleared a dead token: see authListener.ts.
      authListenerMiddleware.middleware,
      // Reports failures no component handled, so a failed request is not mistaken for an
      // empty one. Must stay after every slice's middleware to see their rejections.
      apiErrorMiddleware,
    ),
});

// Infer the `RootState` and `AppDispatch` types from the store itself
export type RootState = ReturnType<typeof store.getState>;
// Inferred type: {posts: PostsState, comments: CommentsState, users: UsersState}
export type AppDispatch = typeof store.dispatch;
