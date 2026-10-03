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

}
