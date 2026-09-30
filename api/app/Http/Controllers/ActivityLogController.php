<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexActivityLogRequest;
use App\Http\Resources\ActivityLogResource;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ActivityLogController extends Controller
{
    /**
     * Roles that may review activity across the whole business.
     */
    private const SUPERVISORY_ROLES = ['executive', 'siteadmin', 'coresupport'];

    public function index(IndexActivityLogRequest $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $query = ActivityLog::query()
            ->with(['causer', 'subject'])
            ->latest();

        $this->scopeVisibility($query, $user);

        $query
            ->inLogNames($request->validated('log_name'))
            ->when($request->filled('causer_id'), fn ($q) => $q
                ->where('causer_type', $user->getMorphClass())
                ->where('causer_id', $request->integer('causer_id')))
            ->when($request->filled('subject_type'), fn ($q) => $q->where('subject_type', $request->string('subject_type')))
            ->when($request->filled('subject_id'), fn ($q) => $q->where('subject_id', $request->integer('subject_id')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('date_to')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = '%'.$request->string('search').'%';
                $q->where(fn ($inner) => $inner
                    ->where('description', 'like', $search)
                    // properties is a json column, so it needs an explicit text cast to be LIKE-able.
                    ->orWhereRaw('CAST(properties AS TEXT) LIKE ?', [$search]));
            });

        $logs = $query->paginate($request->integer('per_page', 20));

        return ActivityLogResource::collection($logs);
    }

    /**
     * Distinct categories actually present for the caller, so the UI filter is never stale.
     */
    public function categories(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = ActivityLog::query();
        $this->scopeVisibility($query, $user);

        $categories = $query
            ->whereNotNull('log_name')
            ->distinct()
            ->orderBy('log_name')
            ->pluck('log_name');

        return response()->json(['data' => $categories]);
    }

    public function show(Request $request, ActivityLog $activityLog): ActivityLogResource
    {
        $this->authorizeVisibility($request->user(), $activityLog);

        return new ActivityLogResource($activityLog->load(['causer', 'subject']));
    }

    public function destroy(Request $request, ActivityLog $activityLog): JsonResponse
    {
        $this->authorizeVisibility($request->user(), $activityLog);

        $activityLog->delete();

        return response()->json(['message' => 'Activity log deleted']);
    }

    private function scopeVisibility($query, User $user): void
    {
        if ($this->isSupervisory($user)) {
            $query->forBusiness($user->business_id);

            return;
        }

        $query->causedByUser($user);
    }

    private function authorizeVisibility(User $user, ActivityLog $activityLog): void
    {
        if ($this->isSupervisory($user)) {
            abort_unless(
                $activityLog->business_id === $user->business_id,
                403,
                'You do not have access to this log entry.'
            );

            return;
        }

        abort_unless(
            $activityLog->causer_type === $user->getMorphClass()
                && (int) $activityLog->causer_id === (int) $user->getKey(),
            403,
            'You do not have access to this log entry.'
        );
    }

    private function isSupervisory(User $user): bool
    {
        return in_array(strtolower((string) $user->role?->name), self::SUPERVISORY_ROLES, true);
    }
}
