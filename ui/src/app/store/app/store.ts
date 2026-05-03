import { configureStore } from '@reduxjs/toolkit';
import { authQuery } from '../features/auth/authQuery';
import { workersQuery } from '../features/business/workers/workersQuery';
import { productsQuery } from '../features/business/products/productsQuery';
import { salesQuery } from '../features/business/sales/salesQuery';
import { customersQuery } from '../features/business/customers/customersQuery';
import { inventoryQuery } from '../features/business/inventory/inventoryQuery';
import { businessQuery } from '../features/business/setup/businessQuery';

export const store = configureStore({
  reducer: {
    [authQuery.reducerPath]: authQuery.reducer,
    [workersQuery.reducerPath]: workersQuery.reducer,
    [productsQuery.reducerPath]: productsQuery.reducer,
    [salesQuery.reducerPath]: salesQuery.reducer,
    [customersQuery.reducerPath]: customersQuery.reducer,
    [inventoryQuery.reducerPath]: inventoryQuery.reducer,
    [businessQuery.reducerPath]: businessQuery.reducer,
  },

  middleware: (getDefaultMiddleware) =>
    getDefaultMiddleware().concat(
      authQuery.middleware,
      workersQuery.middleware,
      productsQuery.middleware,
      salesQuery.middleware,
      customersQuery.middleware,
      inventoryQuery.middleware,
      businessQuery.middleware,
    ),
});

// Infer the `RootState` and `AppDispatch` types from the store itself
export type RootState = ReturnType<typeof store.getState>;
// Inferred type: {posts: PostsState, comments: CommentsState, users: UsersState}
export type AppDispatch = typeof store.dispatch;
