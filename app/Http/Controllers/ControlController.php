<?php

namespace App\Http\Controllers;

use App\Models\ActuatorCommand;
use App\Models\Device;
use App\Models\Greenhouse;
use App\Services\AutomationEngine;
use App\Services\CommandIssuer;
use Illuminate\Http\Request;

class ControlController extends Controller
{
    /**
     * The four controllable relays shown on the panel.
     */
    private const ACTUATORS = [
        'pump'            => ['label' => 'Irrigation Pump',  'icon' => 'droplet', 'on_state' => 'Running', 'off_state' => 'Off'],
        'fan'             => ['label' => 'Ventilation Fan',  'icon' => 'fan',     'on_state' => 'Running', 'off_state' => 'Off'],
        'valve1'          => ['label' => 'Valve 1 — Zone A', 'icon' => 'valve',   'on_state' => 'Open',    'off_state' => 'Closed'],
        'fertiliser_pump' => ['label' => 'Fertiliser Pump', 'icon' => 'flask',   'on_state' => 'Dosing',  'off_state' => 'Off'],
    ];

    public function index(Request $request)
    {
        $currentGreenhouse = $request->filled('greenhouse')
            ? Greenhouse::find($request->input('greenhouse'))
            : Greenhouse::orderBy('name')->first();

        $deviceIds = $currentGreenhouse ? $currentGreenhouse->devices()->pluck('id') : collect();

        $actuators = [];
        $activeCount = 0;
        foreach (self::ACTUATORS as $key => $meta) {
            $latest = ActuatorCommand::whereIn('device_id', $deviceIds)
                ->where('actuator', $key)
                ->latest('id')
                ->first();

            $isOn = $latest && $latest->command === 'on';
            if ($isOn) {
                $activeCount++;
            }

            $actuators[] = array_merge($meta, [
                'key' => $key,
                'is_on' => $isOn,
                'latest' => $latest,
            ]);
        }

        $offlineDevices = ($currentGreenhouse ? $currentGreenhouse->devices() : Device::query())
            ->where('status', 'offline')->get();

        // Automation rules — sourced from the real engine so the panel reflects
        // exactly what runs on each reading. The fertigation row is schedule-driven.
        $rules = AutomationEngine::rules();
        $rules[] = ['name' => 'Fertigation', 'condition' => 'On schedule', 'action' => 'Fert Pump → ON', 'actuator' => 'fertiliser_pump'];
        foreach ($rules as &$rule) {
            $rule['enabled'] = true;
            $last = ActuatorCommand::whereIn('device_id', $deviceIds)
                ->where('actuator', $rule['actuator'])->latest('id')->first();
            $rule['last_triggered'] = $last?->created_at;
        }
        unset($rule);

        return view('control.index', compact(
            'currentGreenhouse', 'actuators', 'activeCount', 'offlineDevices', 'rules'
        ));
    }

    public function toggle(Request $request, CommandIssuer $issuer)
    {
        $data = $request->validate([
            'actuator' => ['required', 'in:pump,fan,valve1,valve2,fertiliser_pump'],
            'command' => ['required', 'in:on,off'],
            'duration' => ['nullable', 'integer', 'min:1'],
            'greenhouse' => ['nullable', 'exists:greenhouses,id'],
        ]);

        $greenhouse = isset($data['greenhouse'])
            ? Greenhouse::find($data['greenhouse'])
            : Greenhouse::orderBy('name')->first();

        $device = $greenhouse?->devices()->first();

        if (! $device) {
            return response()->json(['ok' => false, 'message' => 'No device available for this greenhouse.'], 422);
        }

        $command = $issuer->issue(
            $device,
            $data['actuator'],
            $data['command'],
            $data['duration'] ?? null,
            'manual',
            $request->user()->id
        );

        $delivered = $command->status === 'sent';

        return response()->json([
            'ok' => true,
            'command_id' => $command->id,
            'actuator' => $command->actuator,
            'command' => $command->command,
            'delivered' => $delivered,
            'message' => $delivered
                ? 'Command sent to device.'
                : 'Device offline — command queued and will apply on next sync.',
        ]);
    }
}
