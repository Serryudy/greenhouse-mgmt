<?php

namespace App\Http\Controllers;

use App\Models\FertigationSchedule;
use App\Models\Greenhouse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index(Request $request)
    {
        $currentGreenhouse = $request->filled('greenhouse')
            ? Greenhouse::find($request->input('greenhouse'))
            : Greenhouse::orderBy('name')->first();

        $schedules = $currentGreenhouse
            ? $currentGreenhouse->fertigationSchedules()->orderBy('start_time')->get()
            : collect();

        return view('schedules.index', compact('currentGreenhouse', 'schedules'));
    }

    public function create()
    {
        $greenhouses = Greenhouse::orderBy('name')->get();

        return view('schedules.create', compact('greenhouses'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $schedule = FertigationSchedule::create($data);

        return redirect()->route('schedules.index', ['greenhouse' => $schedule->greenhouse_id])
            ->with('status', 'Schedule created.');
    }

    public function edit(FertigationSchedule $schedule)
    {
        $greenhouses = Greenhouse::orderBy('name')->get();

        return view('schedules.edit', compact('schedule', 'greenhouses'));
    }

    public function update(Request $request, FertigationSchedule $schedule)
    {
        $schedule->update($this->validateData($request));

        return redirect()->route('schedules.index', ['greenhouse' => $schedule->greenhouse_id])
            ->with('status', 'Schedule updated.');
    }

    public function destroy(FertigationSchedule $schedule)
    {
        $ghId = $schedule->greenhouse_id;
        $schedule->delete();

        return redirect()->route('schedules.index', ['greenhouse' => $ghId])
            ->with('status', 'Schedule deleted.');
    }

    public function runNow(FertigationSchedule $schedule)
    {
        $schedule->update(['last_run_at' => now()]);

        return back()->with('status', "Schedule \"{$schedule->name}\" triggered.");
    }

    private function validateData(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'greenhouse_id' => ['required', 'exists:greenhouses,id'],
            'days_of_week' => ['nullable', 'array'],
            'days_of_week.*' => ['in:mon,tue,wed,thu,fri,sat,sun'],
            'start_time' => ['required'],
            'duration_minutes' => ['required', 'integer', 'min:0'],
            'dose_seconds' => ['required', 'integer', 'min:0'],
            'enabled' => ['nullable', 'boolean'],
        ]);

        return [
            'name' => $validated['name'],
            'greenhouse_id' => $validated['greenhouse_id'],
            'days_of_week' => $validated['days_of_week'] ?? [],
            'start_time' => $validated['start_time'],
            'duration_seconds' => (int) $validated['duration_minutes'] * 60,
            'dose_seconds' => (int) $validated['dose_seconds'],
            'enabled' => $request->boolean('enabled'),
        ];
    }
}
