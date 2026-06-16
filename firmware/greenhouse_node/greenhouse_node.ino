/* ============================================================================
 * Verdantia Greenhouse OS — ESP32 Node Firmware
 * ----------------------------------------------------------------------------
 * Talks to the Laravel app over the LAN:
 *   1. POSTs sensor readings  -> POST  /api/sensor-data
 *   2. Receives commands TWO ways:
 *        a) PUSH  : the server calls  POST http://<this-ip>/command  (instant)
 *        b) POLL  : this node calls   GET  /api/devices/commands      (fallback)
 *   3. Confirms execution     -> POST  /api/commands/{id}/acknowledge
 *
 * All requests authenticate with the header:   X-Device-Key: <api_key>
 *
 * Libraries (install via Arduino Library Manager):
 *   - ArduinoJson  (by Benoit Blanchon)
 *   (WiFi, HTTPClient, WebServer ship with the ESP32 core)
 * ==========================================================================*/

#include <WiFi.h>
#include <HTTPClient.h>
#include <WebServer.h>
#include <ArduinoJson.h>

// ----------------------------------------------------------------------------
// CONFIG — edit these for your site
// ----------------------------------------------------------------------------
const char* WIFI_SSID = "YOUR_WIFI_SSID";
const char* WIFI_PASS = "YOUR_WIFI_PASSWORD";

// The PC running `php artisan serve --host=0.0.0.0`. Use its LAN IP + port.
const char* SERVER    = "http://192.168.1.50:8000";

// Must match the device's api_key in the Laravel DB (see Devices page).
const char* DEVICE_KEY = "gh01-secret-key-0001";

// How often to push sensor data / poll for commands (milliseconds).
const unsigned long SENSOR_INTERVAL_MS = 30000;  // 30 s
const unsigned long POLL_INTERVAL_MS   = 5000;   // 5 s

// Relays: most relay boards are ACTIVE-LOW (LOW = ON). Set false if active-high.
const bool RELAY_ACTIVE_LOW = true;

// ----------------------------------------------------------------------------
// PIN MAP — actuator name (must match the DB enum) -> GPIO
// ----------------------------------------------------------------------------
struct Relay { const char* name; uint8_t pin; unsigned long offAt; };
Relay relays[] = {
  { "pump",            26, 0 },
  { "fan",             27, 0 },
  { "valve1",          14, 0 },
  { "valve2",          12, 0 },
  { "fertiliser_pump", 13, 0 },
};
const int RELAY_COUNT = sizeof(relays) / sizeof(relays[0]);

// Sensor pins (examples — wire to your hardware).
#define PIN_SOIL   34   // analog
#define PIN_WATER  35   // analog
#define PIN_GAS    32   // analog (MQ-x)
#define PIN_RAIN   33   // analog
#define PIN_MOTION 25   // digital (PIR)

WebServer server(80);
unsigned long lastSensor = 0;
unsigned long lastPoll   = 0;

// ----------------------------------------------------------------------------
// Relay helpers
// ----------------------------------------------------------------------------
void writeRelay(uint8_t pin, bool on) {
  digitalWrite(pin, (on ^ RELAY_ACTIVE_LOW) ? HIGH : LOW);
}

Relay* findRelay(const String& name) {
  for (int i = 0; i < RELAY_COUNT; i++)
    if (name == relays[i].name) return &relays[i];
  return nullptr;
}

// Apply one command to a relay. Returns true if the actuator was recognised.
bool applyCommand(const String& actuator, const String& command, long duration) {
  Relay* r = findRelay(actuator);
  if (!r) return false;

  bool on = (command == "on");
  writeRelay(r->pin, on);

  // Optional auto-off after `duration` seconds (0 / null = stay until told otherwise).
  r->offAt = (on && duration > 0) ? millis() + (duration * 1000UL) : 0;

  Serial.printf("[RELAY] %s -> %s (duration %lds)\n", actuator.c_str(), command.c_str(), duration);
  return true;
}

// ----------------------------------------------------------------------------
// HTTP to server
// ----------------------------------------------------------------------------
void acknowledge(long id) {
  if (id <= 0) return;
  HTTPClient http;
  http.begin(String(SERVER) + "/api/commands/" + String(id) + "/acknowledge");
  http.addHeader("X-Device-Key", DEVICE_KEY);
  http.addHeader("Accept", "application/json");
  int code = http.POST("");
  Serial.printf("[ACK] command %ld -> HTTP %d\n", id, code);
  http.end();
}

void postSensors() {
  HTTPClient http;
  http.begin(String(SERVER) + "/api/sensor-data");
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-Device-Key", DEVICE_KEY);
  http.addHeader("Accept", "application/json");

  // ---- Read your sensors here. Replace with real conversions. ----
  StaticJsonDocument<512> doc;
  JsonObject readings = doc.createNestedObject("readings");
  readings["temperature"]   = 25.0 + (random(-30, 30) / 10.0);   // TODO: DHT22
  readings["humidity"]      = 60.0 + (random(-50, 50) / 10.0);   // TODO: DHT22
  readings["soil_moisture"] = map(analogRead(PIN_SOIL), 0, 4095, 0, 100);
  readings["water_level_cm"]= map(analogRead(PIN_WATER), 0, 4095, 0, 100);
  readings["gas_level"]     = analogRead(PIN_GAS);
  readings["rain"]          = analogRead(PIN_RAIN);
  readings["motion"]        = digitalRead(PIN_MOTION);

  String body;
  serializeJson(doc, body);
  int code = http.POST(body);
  Serial.printf("[SENSOR] POST -> HTTP %d\n", code);
  http.end();
}

// Fallback path: pull any pending commands and run them.
void pollCommands() {
  HTTPClient http;
  http.begin(String(SERVER) + "/api/devices/commands");
  http.addHeader("X-Device-Key", DEVICE_KEY);
  http.addHeader("Accept", "application/json");
  int code = http.GET();

  if (code == 200) {
    StaticJsonDocument<1024> doc;
    if (deserializeJson(doc, http.getString()) == DeserializationError::Ok) {
      for (JsonObject c : doc["commands"].as<JsonArray>()) {
        long id = c["id"] | 0;
        if (applyCommand(c["actuator"].as<String>(), c["command"].as<String>(), c["duration"] | 0))
          acknowledge(id);
      }
    }
  }
  http.end();
}

// ----------------------------------------------------------------------------
// PUSH path: server -> this node.  POST /command
// ----------------------------------------------------------------------------
void handleCommand() {
  // Authenticate the server using the shared device key.
  if (server.header("X-Device-Key") != DEVICE_KEY) {
    server.send(401, "application/json", "{\"error\":\"unauthorized\"}");
    return;
  }

  StaticJsonDocument<256> doc;
  if (deserializeJson(doc, server.arg("plain")) != DeserializationError::Ok) {
    server.send(400, "application/json", "{\"error\":\"bad json\"}");
    return;
  }

  long id = doc["id"] | 0;
  bool ok = applyCommand(doc["actuator"].as<String>(), doc["command"].as<String>(), doc["duration"] | 0);

  if (ok) {
    acknowledge(id);
    server.send(200, "application/json", "{\"status\":\"ok\"}");
  } else {
    server.send(422, "application/json", "{\"error\":\"unknown actuator\"}");
  }
}

// ----------------------------------------------------------------------------
// Setup / loop
// ----------------------------------------------------------------------------
void setup() {
  Serial.begin(115200);

  for (int i = 0; i < RELAY_COUNT; i++) {
    pinMode(relays[i].pin, OUTPUT);
    writeRelay(relays[i].pin, false);   // start OFF
  }
  pinMode(PIN_MOTION, INPUT);

  WiFi.begin(WIFI_SSID, WIFI_PASS);
  Serial.print("Connecting WiFi");
  while (WiFi.status() != WL_CONNECTED) { delay(400); Serial.print("."); }
  Serial.printf("\nConnected. IP: %s\n", WiFi.localIP().toString().c_str());
  Serial.println(">> Put this IP in the device's 'IP Address' field on the Devices page.");

  // Only keep the X-Device-Key header for routing decisions.
  const char* headers[] = { "X-Device-Key" };
  server.collectHeaders(headers, 1);
  server.on("/command", HTTP_POST, handleCommand);
  server.on("/", HTTP_GET, []() { server.send(200, "text/plain", "Verdantia ESP32 node OK"); });
  server.begin();
}

void loop() {
  server.handleClient();
  unsigned long now = millis();

  // Auto-off relays whose duration elapsed.
  for (int i = 0; i < RELAY_COUNT; i++) {
    if (relays[i].offAt && now >= relays[i].offAt) {
      writeRelay(relays[i].pin, false);
      relays[i].offAt = 0;
      Serial.printf("[RELAY] %s auto-off (duration elapsed)\n", relays[i].name);
    }
  }

  if (now - lastSensor >= SENSOR_INTERVAL_MS) { lastSensor = now; postSensors(); }
  if (now - lastPoll   >= POLL_INTERVAL_MS)   { lastPoll   = now; pollCommands(); }
}
