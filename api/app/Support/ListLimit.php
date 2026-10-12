<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

/**
 * Applies a bounded limit to list endpoints that are not paginated.
 *
 * Context: a number of controllers return `->get()` — the whole collection. For
 * reference data that is harmless, but for anything that grows with trading activity it
 * is not. `SaleController::index` and its siblings materialised every sale, purchase,
 * return, transfer and user a tenant has ever had, in one array, in one request. A
 * branch with three years of trading answered that from memory proportional to its whole
 * history, and there was nothing in the request that could cap it.
 *
 * These endpoints keep returning arrays. Changing them to `paginate()` would replace the
 * array with a `{data, meta}` envelope, and every table component on the client indexes
 * the result directly — so this is the half that can be done without the client being
 * rewritten alongside it.
 *
 * What this does and does not buy:
 *
 *   - it does bound the server's memory, which is the part that can take the API down;
 *   - it does not shrink the payload for a tenant with more rows than the cap, who now
 *     silently sees a truncated list rather than a slow one.
 *
 * So a client hitting the cap is told so, rather than being left to believe it had seen
 * everything. The honest fix is real pagination with the table components updated to
 * match; this is the mitigation until that happens.
 */
class ListLimit
{
    /**
     * Ceiling for reference and configuration data.
     *
     * Countries, business categories, roles, tax rates and the like are small and bounded
     * by what the platform itself defines, so this is generous and effectively never hit.
     */
    public const REFERENCE = 200;

    /**
     * Ceiling for records that grow with trading activity — sales, purchases, returns,
     * transfers, workers, prices. High enough to render a busy year, low enough that one
     * request cannot exhaust memory.
     */
    public const RECORDS = 500;

    /**
     * Resolve the caller's requested limit, clamped to a ceiling.
     *
     * Reads `limit` from the query string rather than trusting the caller to leave it
     * alone: without a maximum, `?limit=999999999` restores exactly the problem this
     * exists to remove, by another route.
     *
     * @param  string  $parameter  Query parameter to read.
     * @param  int  $max  Ceiling for this endpoint.
     */
    public static function for(Request $request, int $max = self::RECORDS, string $parameter = 'limit'): int
    {
        $requested = $request->query($parameter);

        if (! is_numeric($requested)) {
            return $max;
        }

        return max(1, min((int) $requested, $max));
    }

    /**
     * Apply the limit and report truncation on the response.
     *
     * Applied to a query rather than a collection so the ceiling does the work in
     * SQL — collection->take() would still have loaded everything it was trimming.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Collection<int, TModel>
     */
    public static function apply(Builder $query, Request $request, int $max = self::RECORDS)
    {
        $limit = static::for($request, $max);

        $results = $query->limit($limit + 1)->get();

        // One extra row is enough to know whether anything was left behind, and is far
        // cheaper than a COUNT(*).
        if ($results->count() > $limit) {
            $results = $results->take($limit);

            // Flagged on the response so a client can tell a truncated list from a
            // complete one. Silently returning the first N rows reads as "this is all
            // of them", which is the failure this whole change is about.
            request()->attributes->set('list_truncated', true);
        }

        return $results;
    }

    /**
     * Response fragment announcing that a list was cut short.
     *
     * Spread into the JSON body so the shape stays additive — clients reading
     * `sales` or `data` are unaffected, and a client that cares can look for `truncated`.
     *
     * This exists because silently returning the first N rows reads as "this is all of
     * them". A branch manager who sees 500 sales would reasonably conclude the business
     * had 500 sales.
     *
     * @return array<string, mixed>
     */
    public static function truncationNotice(): array
    {
        if (! request()->attributes->get('list_truncated', false)) {
            return [];
        }

        return [
            'truncated' => true,
            'limit' => static::for(request()),
            'notice' => 'This list was truncated. Narrow it with filters, or use the export endpoint for the full range.',
        ];
    }
}
