<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /** JSON dla bell-pollingu z frontu (każde N sekund). */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $items = AppNotification::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'type', 'title', 'message', 'link', 'read_at', 'created_at']);

        $unread = AppNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'unread' => $unread,
            'items'  => $items->map(fn ($n) => [
                'id'         => $n->id,
                'type'       => $n->type,
                'title'      => $n->title,
                'message'    => $n->message,
                'link'       => $n->link,
                'read'       => $n->read_at !== null,
                'created_at' => $n->created_at->diffForHumans(),
            ]),
        ]);
    }

    /** Mark one or all as read. */
    public function markRead(Request $request, int $id): JsonResponse
    {
        if ($id === 0) {
            AppNotification::query()->where('user_id', $request->user()->id)
                ->whereNull('read_at')->update(['read_at' => now()]);
        } else {
            AppNotification::query()->where('user_id', $request->user()->id)
                ->where('id', $id)->update(['read_at' => now()]);
        }
        return response()->json(['ok' => true]);
    }
}
