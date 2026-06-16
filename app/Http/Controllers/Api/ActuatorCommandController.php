<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActuatorCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActuatorCommandController extends Controller
{
    /**
     * Return all pending commands for the authenticated device and mark
     * them as sent. The device is expected to execute them and then call
     * the acknowledge endpoint.
     */
    public function pending(Request $request): JsonResponse
    {
        /** @var \App\Models\Device $device */
        $device = $request->attributes->get('device');

        $commands = ActuatorCommand::where('device_id', $device->id)
            ->where('status', 'pending')
            ->orderBy('id')
            ->get();

        $payload = $commands->map(fn (ActuatorCommand $command) => [
            'id' => $command->id,
            'actuator' => $command->actuator,
            'command' => $command->command,
            'duration' => $command->duration,
        ])->values();

        foreach ($commands as $command) {
            $command->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);
        }

        return response()->json([
            'commands' => $payload,
        ]);
    }

    /**
     * Acknowledge execution of a previously sent command. Scoped to the
     * authenticated device so a device cannot acknowledge another's commands.
     */
    public function acknowledge(Request $request, int $id): JsonResponse
    {
        /** @var \App\Models\Device $device */
        $device = $request->attributes->get('device');

        $command = ActuatorCommand::where('id', $id)
            ->where('device_id', $device->id)
            ->first();

        if (! $command) {
            return response()->json(['error' => 'Command not found'], 404);
        }

        $command->update([
            'status' => 'acknowledged',
            'executed_at' => now(),
        ]);

        return response()->json([
            'status' => 'acknowledged',
            'command_id' => $command->id,
        ]);
    }
}
