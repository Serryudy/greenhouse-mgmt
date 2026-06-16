<?php

namespace App\Services;

use App\Models\ActuatorCommand;
use App\Models\Device;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pushes an actuator command directly to an ESP32 device over the LAN.
 *
 * The device exposes an HTTP endpoint (POST http://<ip>/command). We send the
 * command immediately for low-latency actuation. If the device is unreachable,
 * the command simply remains "pending" and the device will pick it up on its
 * next poll of GET /api/devices/commands.
 */
class DeviceCommandPusher
{
    /** Seconds to wait for the device before giving up and falling back to polling. */
    private const TIMEOUT = 2;

    public function push(Device $device, ActuatorCommand $command): bool
    {
        if (empty($device->ip_address)) {
            return false;
        }

        try {
            $response = Http::timeout(self::TIMEOUT)
                ->withHeaders(['X-Device-Key' => $device->api_key])
                ->acceptJson()
                ->post("http://{$device->ip_address}/command", [
                    'id' => $command->id,
                    'actuator' => $command->actuator,
                    'command' => $command->command,
                    'duration' => $command->duration,
                ]);

            return $response->successful();
        } catch (\Throwable $e) {
            // Offline / wrong IP / timeout — fall back to the polling path.
            Log::warning("Push to device {$device->id} ({$device->ip_address}) failed: {$e->getMessage()}");

            return false;
        }
    }
}
