# Developer 1 Phase 2A irrigation API

All routes are direct PHP files under `/api/v1/`. Send the shared Bearer token. Responses use `{ "success": boolean, "data": value|null, "error": string|null }`. Current access is through the persisted system's farm: admins can access any farm; other users can access only farms they own. The shared `verifyJWT()` remains Developer 4's responsibility.

| Method | Route | Required input |
| --- | --- | --- |
| POST | `irrigation-systems/create.php` | JSON `farm_id`, `name`, `system_type` |
| GET | `irrigation-systems/list.php?farm_id=1` | Farm ID |
| GET | `irrigation-systems/detail.php?id=1` | System ID |
| PATCH | `irrigation-systems/update.php?id=1` | Editable JSON properties |
| POST | `irrigation-systems/deactivate.php?id=1` | System ID |
| GET | `irrigation-systems/status.php?id=1` | System ID |
| GET | `irrigation-systems/recommendation.php?id=1` | System ID |
| POST | `irrigation-schedules/create.php` | JSON `irrigation_system_id`, `scheduled_at`, `duration_minutes` |
| GET | `irrigation-schedules/list.php?irrigation_system_id=1` | System ID; latest scheduled time first |
| GET | `irrigation-schedules/detail.php?id=1` | Schedule ID |
| PATCH | `irrigation-schedules/update.php?id=1` | Editable JSON properties |
| POST | `irrigation-systems/usage/create.php` | JSON `irrigation_system_id`, `logged_at`, `volume_liters`, `duration_minutes` |
| GET | `irrigation-systems/usage/list.php?irrigation_system_id=1` | System ID; newest first |
| GET | `irrigation-systems/usage/summary.php?irrigation_system_id=1` | System ID |

System types: `drip`, `sprinkler`, `flood`, `manual`, `other`. Water-source types: `borehole`, `well`, `river`, `reservoir`, `tank`, `municipal`, `other`. Pump states: `operational`, `maintenance`, `fault`, `offline`. System states: `active`, `inactive`, `maintenance`. Schedule states: `scheduled`, `completed`, `skipped`, `cancelled`. Times use `YYYY-MM-DD HH:MM:SS`; installation dates use `YYYY-MM-DD`. `weather_adjusted` is a JSON boolean indicating that a schedule was adjusted because of weather information; the API does not adjust schedules automatically.

Example system body: `{ "farm_id": 1, "field_id": 2, "name": "North drip", "system_type": "drip", "water_source_type": "borehole", "water_source_name": "Main borehole", "water_source_capacity_liters": 10000, "pump_name": "Pump A", "pump_status": "operational", "flow_rate_lph": 1200 }`.

Example schedule body: `{ "irrigation_system_id": 1, "scheduled_at": "2026-10-12 06:00:00", "duration_minutes": 45, "planned_volume_liters": 900, "weather_adjusted": false }`.

Example usage body: `{ "irrigation_system_id": 1, "schedule_id": 1, "logged_at": "2026-10-12 06:50:00", "volume_liters": 840, "duration_minutes": 45, "energy_kwh": 2.5, "cost_amount": 12.50 }`. Each call creates a new historical row. A schedule, if supplied, must belong to the same system. `usage/summary.php` sums only recorded rows; a zero total energy or cost can also mean those optional values were not recorded.

The status endpoint returns deterministic flags from current system state and the latest usage observation. It does not send notifications; Developer 4 owns notification delivery. The recommendation endpoint reads the newest usable `weather_logs` row for the system's farm. It is a **rule-based advisory, not AI/ML**. Records older than two days and absent weather yield `insufficient_weather_data`. Recent rainfall of at least 5 mm suggests reviewing a reduction or delay; temperature of at least 30 C with under 1 mm rainfall suggests checking for increased demand. It never specifies exact litres or changes a schedule.
