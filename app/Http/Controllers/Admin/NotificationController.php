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
        $notifications = Notification::with('creator:id,name', 'branches:id,name,code')
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when(isset($request->is_draft), fn($q) => $q->where('is_draft', $request->boolean('is_draft')))
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($notifications);
    }

    // GET /admin/notifications/{notification}
    public function show(Notification $notification)
    {
        return response()->json($notification->load('creator:id,name', 'branches:id,name,code'));
    }

    // POST /admin/notifications
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
            'image'        => 'nullable|string|max:255',
            'action_url'   => 'nullable|string|max:255',
            'is_draft'     => 'boolean',
        ]);

        $isBroadcast = $data['is_broadcast'] ?? (empty($data['branch_ids']));
        $isDraft     = $data['is_draft'] ?? false;

        $notification = Notification::create([
            'title'        => $data['title'],
            'body'         => $data['body'],
            'type'         => $data['type'],
            'is_broadcast' => $isBroadcast,
            'created_by'   => $request->user()->id,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'image'        => $data['image'] ?? null,
            'action_url'   => $data['action_url'] ?? null,
            'is_draft'     => $isDraft,
            'sent_at'      => ($isDraft || isset($data['scheduled_at'])) ? null : now(),
        ]);

        if ($isBroadcast) {
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
        $data = $request->validate([
            'title'        => 'sometimes|string|max:255',
            'body'         => 'sometimes|string',
            'type'         => 'sometimes|in:announcement,update,billing,alert',
            'scheduled_at' => 'nullable|date',
            'image'        => 'nullable|string|max:255',
            'action_url'   => 'nullable|string|max:255',
            'is_draft'     => 'boolean',
            'branch_ids'   => 'array',
            'branch_ids.*' => 'exists:branches,id',
        ]);

        if (isset($data['branch_ids'])) {
            $notification->branches()->sync($data['branch_ids']);
            unset($data['branch_ids']);
        }

        if (isset($data['is_draft']) && !$data['is_draft'] && !$notification->sent_at) {
            $data['sent_at'] = now();
        }

        $notification->update($data);
        return response()->json($notification->fresh()->load('creator:id,name', 'branches:id,name,code'));
    }

    // DELETE /admin/notifications/{notification}
    public function destroy(Notification $notification)
    {
        $notification->delete();
        return response()->json(['message' => 'Notification deleted']);
    }

    // GET /notifications/branch/{branch}  — accessible by all authenticated users
    public function forBranch(Branch $branch)
    {
        $notifications = $branch->notifications()
            ->with('creator:id,name')
            ->where('is_draft', false)
            ->orderByDesc('sent_at')
            ->withPivot('read_at', 'delivered_at')
            ->get()
            ->map(function ($n) {
                $arr = $n->toArray();
                $arr['pivot'] = [
                    'read_at'      => $n->pivot->read_at ?? null,
                    'delivered_at' => $n->pivot->delivered_at ?? null,
                ];
                return $arr;
            });

        return response()->json($notifications);
    }

    // POST /notifications/{notification}/read  — body: {branch_id}
    public function markReadByBranch(Request $request, Notification $notification)
    {
        $branchId = $request->input('branch_id');
        if ($branchId) {
            $notification->branches()->updateExistingPivot($branchId, [
                'read_at' => now(),
            ]);
        }
        return response()->json(['message' => 'Marked as read']);
    }

    // PATCH /admin/notifications/{notification}/read/{branch}  — admin only
    public function markRead(Notification $notification, Branch $branch)
    {
        $notification->branches()->updateExistingPivot($branch->id, [
            'read_at' => now(),
        ]);

        return response()->json(['message' => 'Marked as read']);
    }
}
