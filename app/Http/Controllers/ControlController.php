<?php

namespace App\Http\Controllers;

use App\Models\ActuatorCommand;
use App\Models\Device;
use App\Models\Greenhouse;
use App\Services\DeviceCommandPusher;
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

        // Automation rules (display-only summary tied to threshold logic).
        $rules = [
            ['name' => 'Low Moisture',   'condition' => 'Soil moisture < 30%', 'action' => 'Irrigation Pump → ON', 'actuator' => 'pump',            'enabled' => true],
            ['name' => 'High Temp Vent', 'condition' => 'Temperature > 35 °C', 'action' => 'Ventilation Fan → ON', 'actuator' => 'fan',             'enabled' => true],
            ['name' => 'Low Water',      'condition' => 'Water level < 25 cm', 'action' => 'Fill Valve → OPEN',    'actuator' => 'valve1',          'enabled' => true],
            ['name' => 'Fertigation',    'condition' => 'On schedule',         'action' => 'Fert Pump → ON',       'actuator' => 'fertiliser_pump', 'enabled' => true],
        ];
        foreach ($rules as &$rule) {
            $last = ActuatorCommand::whereIn('device_id', $deviceIds)
                ->where('actuator', $rule['actuator'])->latest('id')->first();
            $rule['last_triggered'] = $last?->created_at;
        }
        unset($rule);

        return view('control.index', compact(
            'currentGreenhouse', 'actuators', 'activeCount', 'offlineDevices', 'rules'
        ));
    }

    public function toggle(Request $request, DeviceCommandPusher $pusher)
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

        $command = ActuatorCommand::create([
            'device_id' => $device->id,
            'actuator' => $data['actuator'],
            'command' => $data['command'],
            'duration' => $data['duration'] ?? null,
            'source' => 'manual',
            'status' => 'pending',
            'issued_by' => $request->user()->id,
        ]);

        // Try to deliver instantly; otherwise the device gets it on its next poll.
        $delivered = $pusher->push($device, $command);
        if ($delivered) {
            $command->update(['status' => 'sent', 'sent_at' => now()]);
        }

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
