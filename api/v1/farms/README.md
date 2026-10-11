# Developer 1 Phase 1 API

All paths below are direct PHP files under `/api/v1/`. Send a Bearer token as expected by the shared `verifyJWT()` interface. Responses use `{ "success": boolean, "data": value|null, "error": string|null }`.

| Method | Path | Input |
| --- | --- | --- |
| POST | `farms/create.php` | JSON farm profile; `name` required |
| GET | `farms/list.php` | None |
| GET | `farms/detail.php?id=1` | Farm ID |
| PATCH | `farms/update.php?id=1` | JSON with fields to change |
| POST | `farms/deactivate.php?id=1` | Farm ID |
| POST | `fields/create.php` | JSON including `farm_id`, `name`, `area_hectares` |
| GET | `fields/list.php?farm_id=1` | Farm ID |
| GET | `fields/detail.php?id=1` | Field ID |
| PATCH | `fields/update.php?id=1` | JSON with fields to change |
| POST | `fields/deactivate.php?id=1` | Field ID |
| POST | `fields/soil-records/create.php` | JSON including `field_id`, `recorded_at` (`YYYY-MM-DD HH:MM:SS`) and soil information |
| GET | `fields/soil-records/list.php?field_id=1` | Field ID; newest first |
| POST | `fields/rotation-history/create.php` | JSON including `field_id`, `crop_name`; dates use `YYYY-MM-DD` |
| GET | `fields/rotation-history/list.php?field_id=1` | Field ID; newest dated records first |

`PATCH` changes only supplied editable properties. Farm ownership and a field's `farm_id` cannot be changed through these endpoints. Deactivation sets `status` to `inactive`; no hard delete is provided. Lists include both active and inactive records.

The boundary is a GeoJSON `Polygon` or `MultiPolygon` geometry object, supplied as a JSON object or JSON string in `boundary_geojson`. It is normalized and stored as GeoJSON text in MySQL. Coordinates use GeoJSON order `[longitude, latitude]`. The optional farm/field centre uses separate `latitude` and `longitude` fields, supplied together.

Current access rule: admins can access all farms; other users can access farms they own via `farms.owner_user_id`. This relies on the shared authentication helper, which Developer 4 still needs to replace with real token verification. The endpoints do not manage farm memberships or roles.
