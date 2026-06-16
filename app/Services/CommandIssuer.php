<?php

namespace App\Services;

use App\Models\ActuatorCommand;
use App\Models\Device;

/**
 * Creates an actuator command and attempts to deliver it to the device.
 * Single place that "issues + pushes" so manual control, automation, and
 * schedules all behave identically (instant push, poll fallback).
 */
class CommandIssuer
{
    public function __construct(private DeviceCommandPusher $pusher)
    {
    }

    public function issue(
        Device $device,
        string $actuator,
        string $command,
        ?int $duration,
        string $source,
        ?int $userId = null
    ): ActuatorCommand {
        $cmd = ActuatorCommand::create([
            'device_id' => $device->id,
            'actuator' => $actuator,
            'command' => $command,
            'duration' => $duration,
            'source' => $source,
            'status' => 'pending',
            'issued_by' => $userId,
        ]);

        if ($this->pusher->push($device, $cmd)) {
            $cmd->update(['status' => 'sent', 'sent_at' => now()]);
        }

        return $cmd;
    }
}
