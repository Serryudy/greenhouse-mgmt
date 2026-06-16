<?php

namespace App\Http\Controllers;

use App\Models\Greenhouse;
use Illuminate\Http\Request;

class GreenhouseController extends Controller
{
    public function index()
    {
        $greenhouses = Greenhouse::withCount([
            'devices',
            'alerts as active_alerts_count' => fn ($q) => $q->where('status', 'active'),
        ])->orderBy('name')->get();

        return view('greenhouses.index', compact('greenhouses'));
    }

    public function create()
    {
        return view('greenhouses.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $greenhouse = Greenhouse::create($data);

        return redirect()->route('greenhouses.show', $greenhouse)
            ->with('status', 'Greenhouse created.');
    }

    public function show(Greenhouse $greenhouse)
    {
        $greenhouse->load(['devices', 'thresholds']);
        $deviceIds = $greenhouse->devices->pluck('id');

        $latestReading = \App\Models\SensorReading::whereIn('device_id', $deviceIds)
            ->latest('recorded_at')->first();

        $recentAlerts = $greenhouse->alerts()
            ->with('device')->latest('created_at')->take(10)->get();

        return view('greenhouses.show', compact('greenhouse', 'latestReading', 'recentAlerts'));
    }

    public function edit(Greenhouse $greenhouse)
    {
        return view('greenhouses.edit', compact('greenhouse'));
    }

    public function update(Request $request, Greenhouse $greenhouse)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $greenhouse->update($data);

        return redirect()->route('greenhouses.show', $greenhouse)
            ->with('status', 'Greenhouse updated.');
    }

    public function destroy(Greenhouse $greenhouse)
    {
        $greenhouse->delete();

        return redirect()->route('greenhouses.index')
            ->with('status', 'Greenhouse deleted.');
    }
}
