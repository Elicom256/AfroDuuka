# Task 5 — Quality (P2), ordered easiest → hardest

## Chunk 1: (b) Lint budget for `no-explicit-any`
- Add `@typescript-eslint/no-explicit-any` as a **warning** (not error) to `eslint.config.js`
- Add an `eslint-disable` ratchet comment with current count so future PRs can only decrease it
- Zero code changes — tooling only
- **Push, mark done, next**

## Chunk 2: (c) Accessibility sweep
- Add `overflow-x-auto` to all `<Table>` wrapper divs (1150 usages, only 9 today)
- Add `id` to all `<Input>` components missing one (214 today)
- Add `htmlFor` to all `<Label>` components missing one (179 today)
- Add `role="dialog"` + focus trap to hand-rolled overlays (8 `fixed inset-0` instances)
- Mechanical, large, but no logic changes
- **Push, mark done, next**

## Chunk 3: (a) Route-level code splitting
- Wrap each role tree (`ExecutiveRoutes`, `BranchManagerRoutes`, etc.) in `React.lazy` + `Suspense`
- Wrap public pages (`Login`, `SignUp`, `Onboarding`, etc.) in `React.lazy` + `Suspense`
- Keep `AppRoutes` itself eager (it holds the auth gate)
- Verify build passes and bundle splits
- **Push, mark done, next**

## Chunk 4: (d) POS responsive
- Rework `PosPage.tsx` layout for mobile/tablet: collapsible product grid, touch-friendly buttons, responsive receipt modal
- Design change, not mechanical — hardest
- **Push, mark done, next**
