<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Greenhouse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request)
    {
        $currentGreenhouse = $request->filled('greenhouse')
            ? Greenhouse::find($request->input('greenhouse'))
            : null;

        $base = Alert::with(['greenhouse', 'device']);
        if ($currentGreenhouse) {
            $base->where('greenhouse_id', $currentGreenhouse->id);
        }

        // Tab counts (respecting greenhouse filter, ignoring the tab filter itself).
        $counts = [
            'all' => (clone $base)->count(),
            'active' => (clone $base)->where('status', 'active')->count(),
            'warning' => (clone $base)->where('severity', 'warning')->count(),
            'critical' => (clone $base)->where('severity', 'critical')->count(),
            'resolved' => (clone $base)->where('status', 'resolved')->count(),
        ];

        $filter = $request->input('filter', 'all');
        $query = clone $base;
        match ($filter) {
            'active' => $query->where('status', 'active'),
            'warning' => $query->where('severity', 'warning'),
            'critical' => $query->where('severity', 'critical'),
            'resolved' => $query->where('status', 'resolved'),
            default => null,
        };

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        $alerts = $query->latest('created_at')->paginate(15)->withQueryString();

        return view('alerts.index', compact('alerts', 'counts', 'filter', 'currentGreenhouse'));
    }

    public function acknowledge(Alert $alert)
    {
        $alert->update(['status' => 'acknowledged']);

        return back()->with('status', 'Alert acknowledged.');
    }

    public function resolve(Alert $alert)
    {
        $alert->update(['status' => 'resolved', 'resolved_at' => now()]);

        return back()->with('status', 'Alert resolved.');
    }
}
