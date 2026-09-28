# Executive + Operations Access and Plan Model

## Review

The current product direction names the owner as **Executive** and day-to-day staff as **Operations**, but the roles are being treated as if they are mutually exclusive. That does not fit a single-location business: the owner may be the person opening the shop, recording sales, receiving stock, and closing the day. An owner should not need to switch identity or lose executive controls to do operational work.

There is also a plan-capacity mismatch. The current Essentials plan allows one branch and two users total, while the expected customer is an owner plus one or more operational staff. The two-seat limit may block the intended workflow before permissions become relevant.

The implementation reinforces the ambiguity: `User` stores one `role_id`; `Role` currently has a name and business association but no permission mapping; and registration provisions an `admin` role plus several role names. Plan features and limits are stored separately as JSON, but the notes do not define an authoritative, machine-enforced entitlement contract.

## Recommendation

Model **who a person is**, **what they can do**, and **what the subscription allows** as separate concerns:

1. **Executive is the business owner/account authority.** The executive manages subscription, business settings, and team access. On a single-shop plan, the executive also receives the operational capability bundle needed to run that shop. They remain one user and one identity; do not require dual login or dual `role_id` values.
2. **Operations users are staff accounts.** The executive invites/registers them into the business. They receive an operational profile with only the permissions needed for their job. Being invited must never grant owner, billing, plan, or team-management privileges by default.
3. **Permissions authorize actions; plan entitlements constrain resources/features.** A permission answers “may this user do this action?” A plan entitlement answers “does this business subscription include this feature or resource capacity?” Both checks are required where applicable. A plan must not silently turn every user into an administrator.
4. **Use presets before custom role builders.** Start with a small permission catalog and maintained operational profiles (for example, cashier, inventory operator, and supervisor). Allow the Executive profile to combine executive authority with the single-shop operational bundle. Defer customer-defined roles and complex policy composition until the larger plans need them.

This supports the small owner-operated clinic/shop without constraining medium and large organizations to the same staffing model. “Clinic” should be treated as a business vertical/configuration, not as a role. If clinic workflows include patient records or other regulated health data, define and review that data/security scope separately before presenting DuukaFlow as a clinical-record system; inventory access alone does not imply clinical compliance.

## Proposed Plan Behavior

| Plan segment             | Business shape                         | Executive experience                                                                      | Operations experience                                                                                        |
| ------------------------ | -------------------------------------- | ----------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------ |
| Essentials / single shop | One business, one branch               | One Executive account can perform included operational work and manage the account        | Invite staff as Operations profiles; use preset job permissions; no access to owner-only settings            |
| Professional / medium    | Several branches and a larger team     | Executive retains account-wide control and can delegate branch/team administration        | Managers and Operations users can be scoped to assigned branches and permitted modules                       |
| Enterprise / large       | Many branches, teams, and integrations | Executive controls organization-wide policy, access, integrations, and audit requirements | Custom role/permission administration, granular branch scope, and enterprise identity controls as contracted |

Do not make separate Executive and Operations dashboards a prerequisite for authorization. The same user may see the operational navigation/actions granted to them alongside executive-only controls. Navigation visibility is a usability layer, not a security boundary.

## Seat-Limit Decision

The current Essentials limit is `max_users = 2`. Confirm whether the intended price and support costs can sustain more seats; do not assume that “single shop” means “two people.” The product decision should answer:

- Does the owner count as a seat? Recommended: yes, consistently across plans and billing.
- What is the minimum viable team size for the target shop/clinic? Set Essentials capacity to support that owner-plus-operations workflow, or provide a clearly priced additional-seat option.
- What happens at the limit? Block new invitations with a clear upgrade/add-seat path; never deactivate existing staff or interrupt an active sale.
- Are pending invitations counted? Recommended: yes, with expiration and cancellation so seats cannot be reserved indefinitely.

Keep the branch limit independent from the user limit: one branch can still have several staff members. Enforce limits server-side on invitations/activation and expose the same effective entitlement in the UI.

## Access and Data-Scope Rules

- The business owner is provisioned as Executive; do not rely on the string `admin` as proof of ownership or authorization.
- Staff creation and role/profile assignment are owner-authorized actions. New staff default to the narrowest useful Operations profile.
- Executive operational access is an explicit permission bundle, not a broad “skip authorization” exception. It remains subject to tenant, branch, and plan checks.
- Scope users and records to their business first, then to authorized branch(es). Essentials users are limited to the one branch; medium/enterprise users receive only their assigned branch scope unless granted organization-wide access.
- Protect sensitive actions separately: user/role administration, subscription and billing, business ownership changes, destructive operations, refunds/adjustments, and exports should have explicit permissions and audit events.
- Keep plan entitlement evaluation centralized and fail closed for unknown/missing limits. Treat `-1` as unlimited only if that convention is documented and consistently enforced.
- A user must not gain rights because a frontend route, menu item, role label, or request body says they have them. APIs/policies/services must enforce authorization.

## Naming Refactor Guidance

Do the module/model naming refactor only after agreeing on this access vocabulary. Keep **Executive** (account owner/authority), **Operations** (operational staff capability/profile family), and **Procurement** (business domain) distinct; avoid changing every occurrence of `admin` mechanically because it may be an API namespace or framework concept.

Before renaming persisted role values or routes, inventory existing role assignments, route consumers, API clients, tests, and seeders. Prefer stable permission identifiers and role/profile display names that can change without data migrations. If a persisted identifier must change, use a compatibility migration that updates existing records without duplicating roles, plus route aliases where URLs are public contracts.

## Delivery Plan

### Phase 1: Confirm Product Decisions

- Approve the Executive/Operations definitions and owner-inclusive seat counting.
- Choose the Essentials seat allowance or an additional-seat policy based on target-customer economics.
- Decide the first operational profiles and permissions; validate them with a real single-shop workflow, including a clinic inventory workflow if that is a target vertical.
- Define whether the first release supports inventory/business operations only or any patient/clinical records.

### Phase 2: Specify the Authorization Contract

- Document stable permission identifiers and the allowed actions for Executive and each Operations preset.
- Document branch-scope behavior for each profile.
- Document plan entitlements separately from authorization, including user, branch, and module limits.
- Identify all backend authorization points and ensure frontend navigation only reflects, rather than determines, access.

### Phase 3: Implement the Small-Shop Path

- Provision the business creator as Executive and give that account the included operational capabilities for the one-branch plan.
- Let the Executive invite Operations users and assign a supported preset; keep account-level permissions owner-only.
- Enforce seat and branch limits in the API, including invitation lifecycle and limit-change behavior.
- Add audit events for invitations, profile changes, access changes, and owner-sensitive actions.
- Preserve existing API/routes during the naming transition unless compatibility behavior is provided.

### Phase 4: Expand for Medium and Enterprise

- Add branch assignment and manager delegation without changing the Essentials mental model.
- Add custom roles, finer permission administration, organization-wide scopes, and enterprise integrations only where the higher tiers require them.
- Keep upgrades additive: existing staff retain valid access, while newly available controls are explicitly assigned rather than silently granted.

## Acceptance Checks

- An Essentials Executive can complete the normal single-shop operational workflow and can still manage subscription/business settings.
- An Essentials Executive can invite an Operations user within the seat allowance; the staff member can perform only the assigned operational actions.
- Operations users cannot manage billing, subscription, ownership, or other users unless an explicit higher-tier permission grants a specific action.
- A one-branch limit does not prevent multiple users from working at that branch; attempts to exceed enforced limits are rejected clearly by the API.
- Direct API calls are denied when unauthorized even if a user manually changes the UI or request payload.
- Cross-business and out-of-scope branch access are denied for both Executive and Operations users.
- Professional and Enterprise can add branches, users, and delegation without weakening tenant isolation or changing the Essentials behavior.
- Existing users, role records, routes, and subscriptions survive the naming/access migration without duplicated roles, lost access, or broken clients.

## Main Risks and Open Decisions

- **Seat economics:** the current two-user Essentials limit may not match owner-plus-team expectations. Set the number from customer evidence and pricing, not from the single-branch limit.
- **Authorization retrofit:** current roles are labels without permission relationships. A role rename alone will not deliver access control; the permission contract and API enforcement are the security-critical work.
- **Owner lockout:** account ownership and Executive access need recovery/transfer procedures, tested independently from ordinary staff role changes.
- **Clinic scope:** inventory/operations software and clinical-record software have materially different privacy and compliance obligations. Keep that boundary explicit.
- **Migration safety:** role names are currently provisioned per business and users point to a single role. Audit existing assignments before changing identifiers or registration behavior.

## Decision Summary

For the lowest plan, make the owner an **Executive who can also operate**, and make additional accounts **Operations users with limited, assignable work permissions**. Treat one branch, seat count, and feature access as separate subscription entitlements. This solves the small-shop workflow now while leaving branch-scoped delegation and custom access controls as a natural expansion for medium and large enterprises.
