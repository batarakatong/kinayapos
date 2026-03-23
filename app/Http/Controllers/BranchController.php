<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $branches = $user->branches()->where('branches.is_active', true)->get();
        return response()->json($branches);
    }
}
