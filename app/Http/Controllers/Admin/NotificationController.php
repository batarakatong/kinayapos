<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    // GET /admin/notifications
    public function index(Request $request)
    {
        $notifications = Notification::with('creator:id,name')
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($notifications);
    }

    // GET /admin/notifications/{notification}
    public function show(Notification $notification)
    {
        return response()->json($notification->load('creator:id,name', 'branches:id,name,code'));
    }

    // POST /admin/notifications — buat & kirim notifikasi
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'        => 'required|string|max:255',
            'body'         => 'required|string',
            'type'         => 'required|in:announcement,update,billing,alert',
            'is_broadcast' => 'boolean',
            'branch_ids'   => 'array',
            'branch_ids.*' => 'exists:branches,id',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        $notification = Notification::create([
            'title'        => $data['title'],
            'body'         => $data['body'],
            'type'         => $data['type'],
            'is_broadcast' => $data['is_broadcast'] ?? true,
            'created_by'   => $request->user()->id,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'sent_at'      => isset($data['scheduled_at']) ? null : now(),
        ]);

        // Attach ke branch tertentu atau semua branch
        if ($notification->is_broadcast) {
            $allBranchIds = Branch::pluck('id')->toArray();
            $notification->branches()->attach($allBranchIds);
        } elseif (!empty($data['branch_ids'])) {
            $notification->branches()->attach($data['branch_ids']);
        }

        return response()->json($notification->load('creator:id,name', 'branches:id,name,code'), 201);
    }

    // PUT /admin/notifications/{notification}
    public function update(Request $request, Notification $notification)
    {
        // Hanya bisa edit jika belum dikirim
        if ($notification->sent_at !== null) {
            return response()->json(['message' => 'Cannot edit sent notification'], 422);
        }

        $data = $request->validate([
            'title'        => 'sometimes|string|max:255',
            'body'         => 'sometimes|string',
            'type'         => 'sometimes|in:announcement,update,billing,alert',
            'scheduled_at' => 'nullable|date',
        ]);

        $notification->update($data);
        return response()->json($notification->fresh('creator:id,name'));
    }

    // DELETE /admin/notifications/{notification}
    public function destroy(Notification $notification)
    {
        $notification->delete();
        return response()->json(['message' => 'Notification deleted']);
    }

    // GET /admin/notifications/branch/{branch} — notifikasi untuk branch tertentu
    public function forBranch(Branch $branch)
    {
        $notifications = $branch->notifications()
            ->with('creator:id,name')
            ->orderByDesc('sent_at')
            ->paginate(20);

        return response()->json($notifications);
    }

    // PATCH /admin/notifications/{notification}/read/{branch} — tandai sudah dibaca
    public function markRead(Notification $notification, Branch $branch)
    {
        $notification->branches()->updateExistingPivot($branch->id, [
            'read_at' => now(),
        ]);

        return response()->json(['message' => 'Marked as read']);
    }
}
