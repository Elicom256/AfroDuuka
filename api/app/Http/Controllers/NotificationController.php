<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get all notifications for the authenticated user
     */
    public function index(): JsonResponse
    {
        $query = Notification::where('user_id', Auth::id());

        if ($type = request()->query('type')) {
            $types = array_filter(explode(',', $type));
            if ($types) {
                $query->whereIn('type', $types);
            }
        }

        if (request()->has('is_read')) {
            $query->where('is_read', filter_var(request()->query('is_read'), FILTER_VALIDATE_BOOLEAN));
        }

        $notifications = $query->latest()->paginate(30);

        return response()->json([
            'message' => 'Notifications fetched successfully',
            'notifications' => $notifications->items(),
            'meta' => [
                'total' => $notifications->total(),
                'unread' => $this->unreadCountForUser(),
                'unread_by_type' => $this->unreadByTypeForUser(),
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
            ],
        ]);
    }

    /**
     * Get a single notification for the authenticated user
     */
    public function show(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'message' => 'Notification fetched successfully',
            'notification' => $notification,
        ]);
    }

    /**
     * Mark a single notification as read
     */
    public function markAsRead(Notification $notification): JsonResponse
    {
        // Security: Ensure user can only mark their own notifications
        if ($notification->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read',
            'notification' => $notification,
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(): JsonResponse
    {
        $count = Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'message' => 'All notifications marked as read!',
            'marked_count' => $count,
        ]);
    }

    /**
     * Delete a notification
     */
    public function destroy(Notification $notification): JsonResponse
    {
        if ($notification->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notification->delete();

        return response()->json([
            'message' => 'Notification deleted successfully',
        ]);
    }

    /**
     * Optional: Get unread count only (useful for badge)
     */
    public function unreadCount(): JsonResponse
    {
        return response()->json([
            'unread_count' => $this->unreadCountForUser(),
            'unread_by_type' => $this->unreadByTypeForUser(),
        ]);
    }

    /**
     * Clear all notifications for the authenticated user
     */
    public function clearAll(): JsonResponse
    {
        $count = Notification::where('user_id', Auth::id())->delete();

        return response()->json([
            'message' => 'All notifications cleared',
            'cleared_count' => $count,
        ]);
    }

    private function unreadCountForUser(): int
    {
        return Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->count();
    }

    private function unreadByTypeForUser(): array
    {
        return Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->selectRaw('type, count(*) as total')
            ->groupBy('type')
            ->orderByDesc('total')
            ->pluck('total', 'type')
            ->map(fn ($v) => (int) $v)
            ->all();
    }
}
