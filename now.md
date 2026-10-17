# Offline plan — closed

The cache-then-network brief is implemented at the store layer. Pages keep RTK Query. `baseQuery` writes a successful GET into IndexedDB and serves that copy when the browser is offline or the request fails in transit. A 4xx or 5xx is not replaced with stale data.

Writes for the domains in `plan.md` §4 queue on transport failure and replay through `POST /api/sync/batch`. The device outbox is the pending marker. Server `sync_status` stays `synced` until a handler commits. POS checkout stays on `submitSale`.

Pending and failed rows show above dashboard pages (`OfflinePendingRows`). Opening one shows the local id and saved fields, with dates through `formatDate`. A failed row retries through `retryOperation`. List rows show `RowSyncStatus` when the status is not `synced`.

## Automated

- `cd ui && npm test -- --run src/lib/offline src/components/offline` — 95 passed, including `domainMatrix.test.ts` (one create per queued domain: a transport failure stores one pending operation, a repeat does not, a 422 stays unqueued; POS checkout, messages, and the refused deletes are not queued).
- `cd ui && npm run build` — green after the offline queue error was typed as `CUSTOM_ERROR`.
- `docker compose -f docker-compose.dev.yml exec -T backend php artisan test` — 1006 passed (3500 assertions). `SyncDomainHandlersTest` covers replay and a rejected body for each new handler, and a product update cannot rename another tenant's product.

## Not run here

The manual matrix (load online, go offline, read, create, edit, delete, reconnect, confirm the row leaves the device list) was not exercised in a browser.
