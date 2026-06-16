# ESP32 Node Firmware — Verdantia Greenhouse OS

Firmware that connects an ESP32 to the Laravel app over your LAN. It posts
sensor readings, receives actuator commands (push **and** poll), drives relays,
and acknowledges execution.

## 1. Arduino IDE setup
1. Install the **ESP32 board package**: *File → Preferences →* add
   `https://raw.githubusercontent.com/espressif/arduino-esp32/gh-pages/package_esp32_index.json`
   then *Tools → Board → Boards Manager →* install **esp32**.
2. Install the **ArduinoJson** library (*Tools → Manage Libraries*).
3. Open `greenhouse_node/greenhouse_node.ino`.

## 2. Configure (top of the sketch)
| Constant | Set to |
|---|---|
| `WIFI_SSID` / `WIFI_PASS` | your WiFi |
| `SERVER` | the PC running Laravel, e.g. `http://192.168.1.50:8000` |
| `DEVICE_KEY` | the device's `api_key` from the **Devices** page (e.g. `gh01-secret-key-0001`) |
| `RELAY_ACTIVE_LOW` | `true` for typical blue relay boards (LOW = ON) |
| pin map | match `pump/fan/valve1/valve2/fertiliser_pump` to your GPIOs |

## 3. Start Laravel so the device can reach it
```bash
php artisan serve --host=0.0.0.0 --port=8000
```
`0.0.0.0` (not `127.0.0.1`) is required so other LAN devices can connect.

## 4. Register the device's IP for instant push
After flashing, the ESP32 prints its IP in the Serial Monitor (115200 baud).
Open **Devices → (your device) → Edit** and put that IP in **IP Address**.
That's the address the server pushes commands to. Without it, the device still
works via polling (just up to ~5 s slower).

## 5. Control flow
- **Push:** flipping a Control Panel toggle → server `POST http://<esp-ip>/command`
  → relay switches immediately → device calls `acknowledge`.
- **Poll fallback:** if the ESP32 is unreachable at that moment, the command stays
  `pending`; the device's 5 s poll picks it up next cycle.
- Either way the command ends up `acknowledged` with `executed_at` set — that's
  your confirmation the relay actually moved.

## HTTP contract (if you write your own firmware)
All requests send header `X-Device-Key: <api_key>`.

| Direction | Method & URL | Body |
|---|---|---|
| Device → server | `POST /api/sensor-data` | `{"readings":{"temperature":…,"humidity":…,…}}` |
| Device → server | `GET /api/devices/commands` | — → `{"commands":[{"id","actuator","command","duration"}]}` |
| Device → server | `POST /api/commands/{id}/acknowledge` | — |
| Server → device | `POST http://<ip>/command` | `{"id","actuator","command","duration"}` → reply `{"status":"ok"}` |

## Safety notes
- Relays driving pumps/valves switch **mains or pump voltage** — wire through
  proper relay modules and fuse appropriately; never drive loads from GPIO directly.
- `duration` (seconds) auto-offs a relay so a stuck network can't leave a pump
  running forever. Set it on time-limited actions.
