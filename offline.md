# Task: Implement the Frontend Foundation for Offline-First Sync in AfroDuuka

You are working on **AfroDuuka**, a multi-tenant inventory management SaaS with a React + Vite + TypeScript frontend and a Laravel API backend.

## Objective

Implement the frontend foundation for offline-first functionality so users can eventually continue recording POS sales during internet outages and synchronize them with the Laravel API when connectivity returns. Generally, they can continue doing CRUD of the currently available and accessible routes

**This task focuses on the frontend foundation and local persistence.** Do not attempt to implement the complete backend synchronization protocol yet.

## 1. Inspect the existing codebase first

Before modifying files:

- Inspect `package.json`, `vite.config.ts`, the frontend entry point, routing, API client, authentication implementation, POS sales flow, inventory services, customer services, and existing UI conventions.
- Identify existing service workers, PWA configuration, caching libraries, state management, and API interceptors.
- Reuse existing patterns and dependencies wherever possible.
- Do not assume filenames, endpoints, API response formats, or authentication mechanisms.
- Provide a brief implementation plan in todayswork.md, then proceed with implementation.

## 2. Configure the PWA

Use the already-installed `vite-plugin-pwa`.

- Integrate `VitePWA` into the existing Vite configuration without removing existing plugins, aliases, or configuration.
- Configure an appropriate web app manifest for AfroDuuka.
- Configure Workbox to precache the production application shell and suitable static assets.
- Preserve existing application branding and use existing icons where available. Do not invent asset paths.
- Ensure the production build works correctly with the project's deployment configuration and SPA routing.
- Use a deliberate update strategy that does not unexpectedly interrupt an active POS session.
- Register the service worker using the plugin's supported approach.
- Avoid caching authenticated API responses, sensitive business information, or mutation requests in the service worker's runtime cache.
- Do not introduce a second, conflicting service worker.

Do not claim the entire application is offline-capable merely because the app shell loads offline.

## 3. Implement IndexedDB persistence using Dexie

Use the already-installed `dexie` and `dexie-react-hooks` packages.

Create a maintainable offline module in an appropriate location, following existing project conventions.

Implement a versioned IndexedDB database with a durable outbox for pending operations. Each operation should support:

- A unique, client-generated `operationId` using `crypto.randomUUID()` or an appropriate compatible alternative. But dexie.js already handles ids as I need
- Operation type, initially supporting `sale`, `inventory`, and `customer`.
- The serialized operation payload.
- Creation timestamp.
- Synchronization status, such as `pending`, `syncing`, `synced`, or `failed`.
- Retry count and relevant error information.
- Business and branch context where required by the existing domain model.
- An optional server identifier and synchronization timestamp.

Create typed database models and reusable helpers for enqueuing, listing, updating, retrying, and removing operations.

Add React hooks using `dexie-react-hooks` where they provide useful reactive access to pending-operation counts and statuses.

Requirements:

- Make enqueueing durable before reporting that an operation has been saved locally.
- Prevent duplicate outbox entries for the same operation ID.
- Use IndexedDB transactions when multiple local records must be updated together.
- Support database schema upgrades safely.
- Handle storage errors and quota failures visibly.
- Never store passwords, access tokens, or unnecessary sensitive information in IndexedDB.
- Treat local browser storage as device-specific and potentially evictable, not as a guaranteed backup.

## 4. Add connectivity monitoring

Create a reusable React hook or service that tracks:

- Browser-reported online/offline state.
- Actual API reachability, using an existing health endpoint if one is available.
- Number of pending operations.
- Whether synchronization is in progress.
- Last successful synchronization time, when available.

Use browser `online` and `offline` events as signals, not proof of API availability.

Do not invent an API health endpoint. If none exists, implement browser connectivity monitoring separately and document the limitation.

Clean up event listeners and avoid unnecessary polling.

## 5. Integrate carefully with the existing POS

Inspect the existing sale,pos,purchase,todo,etc creation flow and determine the safest integration point.

Do not replace the existing API submission path with a mock implementation.

For this phase:

- Prepare a reusable offline-aware operation layer that can be integrated with the actual sales service.
- Preserve current online behavior.
- Avoid silently losing a sale when the API is unreachable.
- Ensure the UI can distinguish a locally saved, pending sale from a server-confirmed sale.
- If full POS integration requires the backend's idempotency contract or inventory semantics, implement the safe frontend foundation and document the missing dependency instead of pretending the flow is complete.

**Important:** A sale, its line items, local stock adjustments, and its outbox entry must be persisted atomically when the required local data model is implemented. Do not implement a misleading partial transaction. This applies to all other routes

Never assume an offline sale is accepted by the server. The backend must eventually validate and apply it.

## 6. Add a minimal offline status UI

Follow AfroDuuka's existing component library, styling conventions, and design system.

Provide a small, reusable status indicator showing:

- Online or offline state.
- Pending operations count.
- Synchronization in progress.
- Failed operations requiring attention.

Use accessible labels and avoid disruptive banners or unnecessary layout changes.

Do not redesign the dashboard or POS.
meaning on routes that have CRUD or aka user-owned, they must have an status to enable the server identify if the row is synced / pending. So, on all those migrations, add a column called sync_status by editing migrations directly. So when a row is created, it must have a pending status when offline, and after syncing, it becomes synced

## 7. Synchronization architecture preparation

Prepare clean interfaces for a future sync engine, but do not invent Laravel endpoints or implement a fake synchronization service.

The eventual sync engine must support:

- Stable idempotency keys.
- Safe retries.
- Partial failure handling.
- Per-operation status tracking.
- Server-confirmed completion.
- Recoverable failed operations.
- Tenant and branch isolation.
- Server-authoritative inventory and financial calculations.

Do not use last-write-wins indiscriminately for sales, stock movements, payments, or financial transactions. These operations require domain-specific reconciliation.

Do not rely exclusively on the Background Sync API. Include a reliable app-open/reconnect fallback for browsers that do not support it.

## 8. Testing and quality

- Add tests using the project's existing test framework where practical.
- Test enqueueing, duplicate operation IDs, status transitions, database transaction behavior, and connectivity-state changes.
- Test production builds.
- Run available lint, type-check, and test commands.
- Fix regressions introduced by the implementation.
- Avoid unrelated refactoring and new dependencies unless necessary.

## 9. Deliverables

After implementation, summarize:

1. Files created and modified.
2. PWA configuration and service-worker behavior.
3. IndexedDB schema and outbox API.
4. React hooks and UI components added.
5. Tests and build commands executed, including their actual results.
6. What works offline now versus what still requires backend work.
7. Remaining integration points for the Laravel API, especially idempotency, sync endpoints, conflict resolution, and inventory reconciliation.

## Constraints

- React + Vite + TypeScript.
- Use the already-installed `dexie`, `dexie-react-hooks`, and `vite-plugin-pwa`.
- Preserve existing architecture, authentication, API client, routing, and UI conventions.
- No invented API endpoints.
- No fake sync success.
- No destructive database migrations or unrelated changes.
- Do not claim offline sales synchronization is complete until the actual end-to-end flow has been implemented and tested.

Start by inspecting the repository, then implement the smallest reliable foundation that fits the existing application.
