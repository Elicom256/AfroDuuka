<?php

namespace App\Support\Auth;

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

    public static function roleName(?User $user): string
    {
        return strtolower(trim((string) $user?->role?->name));
    }

    public static function isRestricted(?User $user): bool
    {
        return in_array(static::roleName($user), static::RESTRICTED_ROLES, true);
    }

    public static function isElevated(?User $user): bool
    {
        return in_array(static::roleName($user), static::ELEVATED_ROLES, true);
    }

    /**
     * May the user remove records? Restricted roles may not, on any model.
     */
    public static function canDelete(?User $user): bool
    {
        return ! static::isRestricted($user);
    }

    /**
     * May the user author new catalogue records (products, categories, tax config)?
     */
    public static function canCreateCatalog(?User $user): bool
    {
        return ! static::isRestricted($user);
    }

    /**
     * May the user change a product's identity, pricing or classification?
     *
     * Stock levels are excluded on purpose: moving quantity is the one write a
     * restricted role is here to make, and it is routed through InventoryService so
     * it leaves a stock_movements row behind.
     */
    public static function canEditCatalog(?User $user): bool
    {
        return ! static::isRestricted($user);
    }
}
