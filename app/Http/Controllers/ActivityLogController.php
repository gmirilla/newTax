<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = auth()->user()->tenant;

        $query = AuditLog::where('tenant_id', $tenant->id)
            ->with('user')
            ->when($request->filled('user_id'), fn($q) => $q->where('user_id', $request->user_id))
            ->when($request->filled('event'), fn($q) => $q->where('event', 'like', '%' . $request->event . '%'))
            ->when($request->filled('from'), fn($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->filled('to'),   fn($q) => $q->whereDate('created_at', '<=', $request->to))
            ->orderByDesc('created_at');

        $logs = $query->paginate(50)->withQueryString();

        $teamMembers = User::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('activity.index', compact('logs', 'teamMembers'));
    }
}
