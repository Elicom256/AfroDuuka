<?php

namespace App\Support\Auth;

use App\Models\Role;
use App\Models\User;

/**
 * Single source of truth for what a role name is allowed to do.
 *
 * The policies in app/Policies and the abort_unless() calls sprinkled through the
 * controllers all used to re-derive the role name themselves, which meant the same
 * rule could be written three ways and enforced in none of them. This class holds the
 * capability map; everything else asks it.
 *
 * Role names are stored in the database with inconsistent casing ("Executive" vs
 * "editor"), so every comparison here goes through roleName() and is case-insensitive.
 */
class RolePermissions
{
    /**
     * Roles allowed to make destructive changes and to author catalogue records.
     */
    public const ELEVATED_ROLES = ['executive', 'coresupport', 'siteadmin'];

    /**
     * Roles held back to operational work only.
     *
     * Operations runs the day-to-day floor: selling, stock counts, adjustments. It
     * does not author the catalogue and it does not remove records — a product that
     * has been sold cannot be deleted from under the sale history, and a bad delete
     * cascades through stock_movements, sale_items and price_histories with it.
     */
    public const RESTRICTED_ROLES = ['operations'];

    /**
     * Branch-scoped managers: near-executive powers within their own branch only.
     *
     * A BranchManager can do almost everything an Executive can — author the catalogue,
     * adjust stock, approve purchase orders, remove records — but EffectiveBranchScope
     * confines every one of those actions to the branch they are assigned to. They
     * cannot see or touch other branches, and they cannot change business-level
     * settings that belong to the Executive.
     */
    public const BRANCH_MANAGER_ROLES = ['branchmanager'];

    /**
     * Roles trusted with the finance area.
     *
     * Spelled the way callers write it; hasAnyRole() normalises both sides before
     * comparing, so the underscore here matches a role stored as 'BranchManager'.
     * Membership alone is not enough — see canManageSensitiveFinance(), which also
     * requires canManageBranch() and so keeps Operations out.
     */
    public const SENSITIVE_FINANCE_ROLES = ['executive', 'branch_manager', 'operations', 'coresupport', 'siteadmin'];

    /**
     * Platform operators: the staff who own the product itself.
     *
     * A narrower set than ELEVATED_ROLES. Everything else in this class is a
     * statement about what a role may do *inside one business*, which is what
     * executive and branchmanager mean. A plan's price list, by contrast, has
     * no business_id at all — Plan extends Eloquent\Model rather than BaseModel
     * precisely because one price list is sold to every tenant — so no tenant
     * scope can contain it and an Executive must not be able to reprice it.
     */
    public const PLATFORM_OPERATOR_ROLES = ['coresupport', 'siteadmin'];

    public static function isPlatformOperator(?User $user): bool
    {
        return in_array(static::roleName($user), static::PLATFORM_OPERATOR_ROLES, true);
    }

    public static function roleName(?User $user): string
    {
        // Role names are stored inconsistently ("BranchManager", "branch_manager",
        // "Branch Manager"), so every comparison here lowercases first, then strips
        // separators entirely.
        //
        // The role is read by primary key from the user's own record, unscoped. Role
        // extends BaseModel, so `roles` carries a business_id and the relation goes
        // through the same global scope that asks for the tenant id — and for a user
        // who has no business yet that scope resolves to `whereRaw('0 = 1')`, hiding
        // even the role that belongs to them. Every capability check then read an empty
        // role name and refused the request: a CoreSupport account, which has no
        // business by design, was locked out of everything, and so was anyone part-way
        // through onboarding. This is the same rule BusinessContext::roleNameFor()
        // follows for the same reason.
        $name = $user?->role?->name
            ?? ($user?->role_id !== null
                ? Role::withoutGlobalScopes()->whereKey($user->role_id)->value('name')
                : null);

        return preg_replace('/[^a-z0-9]/', '', strtolower((string) $name));
    }

    public static function isRestricted(?User $user): bool
    {
        return in_array(static::roleName($user), static::RESTRICTED_ROLES, true);
    }

    public static function isElevated(?User $user): bool
    {
        return in_array(static::roleName($user), static::ELEVATED_ROLES, true);
    }

    public static function isBranchManager(?User $user): bool
    {
        return in_array(static::roleName($user), static::BRANCH_MANAGER_ROLES, true);
    }

    /**
     * May the user exercise executive-level powers within their branch scope?
     *
     * True for Elevated roles (unrestricted) and BranchManager (branch-scoped).
     * Used for stock modifications, purchase-order approval, and catalogue authoring.
     */
    public static function canManageBranch(?User $user): bool
    {
        return static::isElevated($user) || static::isBranchManager($user);
    }

    /**
     * May the user move money in the finance area?
     *
     * Both conditions have to hold, which is why this is not just a role list:
     * canManageBranch() keeps Operations out, while SENSITIVE_FINANCE_ROLES is the set
     * of roles finance considers trustworthy at all.
     */
    public static function canManageSensitiveFinance(?User $user): bool
    {
        return static::canManageBranch($user)
            && static::hasAnyRole($user, static::SENSITIVE_FINANCE_ROLES);
    }

    /**
     * Does the user hold any of the named roles?
     *
     * Both sides are compared through the same normalisation, so a caller can write
     * 'branch_manager', 'BranchManager' or 'Branch Manager' and get the same answer.
     * Comparing a raw lowercased role name against a list is what made branch managers
     * invisible to the finance gate: the role is stored as 'BranchManager', and
     * strtolower() leaves it 'branchmanager', which never equals 'branch_manager'.
     *
     * @param  array<int, string>  $roles
     */
    public static function hasAnyRole(?User $user, array $roles): bool
    {
        $name = static::roleName($user);

        foreach ($roles as $role) {
            if ($name === static::normalise($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Reduce a role name to the same shape roleName() produces, so the constants in
     * this class and the literals callers pass can be spelled either way.
     */
    public static function normalise(string $role): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower($role));
    }

    /**
     * May the user remove records? Restricted roles may not, on any model.
     * BranchManager may delete within their branch scope.
     */
    public static function canDelete(?User $user): bool
    {
        return static::canManageBranch($user);
    }

    /**
     * May the user author new catalogue records (products, categories, tax config)?
     * BranchManager may author within their branch scope.
     */
    public static function canCreateCatalog(?User $user): bool
    {
        return static::canManageBranch($user);
    }

    /**
     * May the user change a product's identity, pricing or classification?
     *
     * Stock levels are excluded on purpose: moving quantity is the one write a
     * restricted role is here to make, and it is routed through InventoryService so
     * it leaves a stock_movements row behind.
     *
     * BranchManager may edit catalogue within their branch scope.
     */
    public static function canEditCatalog(?User $user): bool
    {
        return static::canManageBranch($user);
    }

    /**
     * May the user modify stock levels?
     *
     * Elevated roles and BranchManager may adjust stock. Operations may only
     * adjust through InventoryService (stock counts, adjustments). Procurement
     * may only increase stock through receiving purchase orders.
     */
    public static function canModifyStock(?User $user): bool
    {
        return static::canManageBranch($user);
    }

    /**
     * May the user approve purchase orders?
     *
     * Only Elevated roles and BranchManager may approve. Procurement may create
     * and receive orders but not approve them.
     */
    public static function canApprovePurchaseOrder(?User $user): bool
    {
        return static::canManageBranch($user);
    }

    /**
     * May the user create purchase orders?
     *
     * Elevated roles, BranchManager, and Procurement may create orders.
     */
    public static function canCreatePurchaseOrder(?User $user): bool
    {
        return static::canManageBranch($user)
            || static::roleName($user) === 'procurement';
    }

    /**
     * May the user receive purchase orders (add stock)?
     *
     * Elevated roles, BranchManager, and Procurement may receive orders.
     */
    public static function canReceivePurchaseOrder(?User $user): bool
    {
        return static::canManageBranch($user)
            || static::roleName($user) === 'procurement';
    }

    /**
     * May the user create, edit or delete subscriptions?
     *
     * A subscription is what a business pays to exist on the platform, so this is
     * not branch work. BranchManager is excluded deliberately: the route group has
     * no 'role' middleware, and canManageBranch would otherwise admit them to
     * rewrite the plan and expiry dates of the whole business.
     *
     * Cross-tenant creation is still blocked separately — StoreSubscriptionRequest
     * pins business_id to the caller's own business unless they have none, and
     * Subscription extends BaseModel so the tenant scope hides other businesses'
     * rows from update and delete.
     */
    public static function canManageSubscriptions(?User $user): bool
    {
        return static::isElevated($user);
    }

    /**
     * May the user author or change discounts — coupons and promotions?
     *
     * Pricing is money, and Operations is the one role that stands to gain from
     * loosening it: a coupon is pure margin given away at the till, so an
     * Operations account able to write one can discount its own sales with no
     * second pair of eyes. Restricting writes to canManageBranch keeps that out
     * while still letting a BranchManager run promotions for its own branch,
     * which is ordinary branch-level commercial work.
     *
     * Reads stay open. The till has to resolve a coupon code mid-transaction, and
     * an Operations user cannot check a discount it is not allowed to author.
     */
    public static function canManageDiscounts(?User $user): bool
    {
        return static::canManageBranch($user);
    }

    /**
     * May the user author or edit scheduled report definitions?
     *
     * Reports are business-level configuration — they carry business_id and no
     * branch column — so this is Executive territory, not branch-scoped work:
     * BranchManager must not rewrite how the whole business reports.
     */
    public static function canManageReports(?User $user): bool
    {
        return static::isElevated($user);
    }

    /**
     * May the user create, edit or delete suppliers?
     *
     * A supplier is who the *business* buys from, not something a branch owns:
     * SupplierService never stamps business_branch_id on create, so the row lands
     * with a NULL branch and EffectiveBranchScope keeps it visible to every branch
     * of the business. A BranchManager may read that list — their purchases need it
     * to render a supplier name — but authoring it is Executive territory, for the
     * same reason as canManageReports: one counterparty and one supplier_code for the
     * whole business, which a branch manager must not fork per branch.
     */
    public static function canManageSuppliers(?User $user): bool
    {
        return static::isElevated($user);
    }

    /**
     * May the user author the credentials that route and authenticate money?
     *
     * This is the highest-consequence write in the product. A payment gateway row
     * holds the MTN MoMo / Airtel / Flutterwave / Pesapal api_key, api_secret and
     * webhook_secret — that is, where live mobile-money payments are sent and how
     * an inbound webhook proves it came from the provider rather than from anyone
     * who guessed the URL. Currency rates decide the value of every multi-currency
     * total and report; a WhatsApp config holds the provider token and the business
     * number that outbound, metered messages are sent from.
     *
     * All three are per-business configuration, not per-branch, but they resolve to
     * canManageBranch() rather than isElevated() for the same reason the catalogue
     * rules do: EffectiveBranchScope plus the BaseModel tenant scope confine a
     * BranchManager to their own branch, and running your own branch's payment
     * configuration is ordinary branch work. What no role below manager may do is
     * rewrite it — an Operations account able to write here could re-point where
     * the business's money goes, which is the opposite of what the till role is for.
     */
    public static function canManagePaymentConfig(?User $user): bool
    {
        return static::canManageBranch($user);
    }

    /**
     * May the user open or close a cash session?
     *
     * Opening a drawer floats cash and closing one declares a variance, so this is
     * the pair of writes that decides whether the till reconciles. Both resolve to
     * canManageBranch(): the branch scope already confines a BranchManager to the
     * drawer they are responsible for, and CashDrawerService::assertAccess() checks
     * the session's branch again on read.
     *
     * Named separately from canManageBranch() because the floor genuinely needs the
     * *other* side of this trade to stay open — BusinessDebitController@pay records
     * money already committed and is deliberately ungated. If drawer open/close and
     * debt settlement were both folded into canManageBranch(), that deliberate
     * exception would have no honest name to point at.
     */
    public static function canManageCashDrawer(?User $user): bool
    {
        return static::canManageBranch($user);
    }
}
