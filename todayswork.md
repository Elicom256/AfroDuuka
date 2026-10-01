# Today's Work — P3 Problem 1: StoreUserRequest role_id unscoped exists

**Issue:** `StoreUserRequest.php:63` — `role_id` uses an unscoped `exists` rule (`exists:roles,id`). A cross-tenant role named `Executive` would grant elevated rights.

**Evidence:**
- `StoreUserRequest.php:63`: `'role_id' => 'nullable|exists:roles,id'`
- This only checks that a role with that ID exists globally, without scoping to the user's business
- `UserService.php:73`: `$role = Role::find($data['role_id'])` — fetches role globally
- If a role named `Executive` exists in another tenant's context, it could be selected and grant elevated permissions
- This connects to Finding #1 (cross-tenant account) and Finding #2 (cross-tenant ban) — role scoping is part of the same auth boundary problem

**Action:**
1. Change `exists:roles,id` to a scoped exists that checks the role belongs to the same business
2. The `role_id` on users is nullable and linked to `business_id` via the users table
3. The exists clause should be: `exists:roles,id,business_id,${user->business_id}` (Laravel 11+ syntax) or use a raw subquery
4. In `UserService.php`, when setting the role, verify the role belongs to the same business

**Files to modify:**
- `api/app/Http/Requests/StoreUserRequest.php` — fix the exists rule for role_id
- `api/app/Services/UserService.php` — add business scoping when assigning role

**Effort:** 20 minutes
**Priority:** P3 — Low (cross-tenant role escalation, lower impact than P0/P1 but still a security concern)
**Blocking:** None — independent fix

**Verification:**
- A user cannot assign a role that belongs to another business/tenant
- The exists validation correctly rejects role_ids that don't belong to the user's business