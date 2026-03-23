<?php

namespace App\Http\Controllers;

use App\Models\SyncOutbox;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function push(Request $request)
    {
        $branchId = $request->attributes->get('branch_id');
        $data = $request->validate([
            'entries' => 'required|array',
            'entries.*.table_name' => 'required|string',
            'entries.*.row_id' => 'required',
            'entries.*.action' => 'required|in:create,update,delete',
            'entries.*.payload' => 'required|array',
        ]);

        foreach ($data['entries'] as $entry) {
            SyncOutbox::create([
                'branch_id' => $branchId,
                'table_name' => $entry['table_name'],
                'row_id' => $entry['row_id'],
                'action' => $entry['action'],
                'payload' => $entry['payload'],
            ]);
        }

        return response()->json(['message' => 'queued', 'count' => count($data['entries'])]);
    }

    public function pull(Request $request)
    {
        $branchId = $request->attributes->get('branch_id');
        $since = $request->query('since');
        $query = SyncOutbox::where('branch_id', $branchId)->orderBy('id', 'asc');
        if ($since) {
            $query->where('updated_at', '>=', $since);
        }
        $entries = $query->limit(200)->get();
        return response()->json(['data' => $entries]);
    }
}
