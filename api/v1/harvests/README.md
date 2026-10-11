# Developer 1 Phase 3 harvest API

Migration `013_harvests.sql` adds one `harvests` table for planned, in-progress, completed, and cancelled field harvests. Canonical crop and planting tables are not yet in this repository, so each record requires `field_id` and `crop_name_snapshot`; `variety_snapshot` is optional. A later agreed migration can link canonical `planting_id` without replacing historical snapshots.

All routes are direct PHP files under `/api/v1/`, use the shared Bearer authentication interface, and return `{ "success": boolean, "data": value|null, "error": string|null }`. Access resolves from the persisted harvest to its field and farm. Admins may access all farms; other users may access farms they own under the current Developer 1 convention. Developer 4 owns real authentication.

| Method | Route | Required input |
| --- | --- | --- |
| POST | `harvests/create.php` | JSON `field_id`, `crop_name_snapshot`; completed records also need actual date, quantity and harvested area |
| GET | `harvests/list.php?field_id=1` | Field ID; newest relevant date first |
| GET | `harvests/detail.php?id=1` | Harvest ID, including derived analysis |
| PATCH | `harvests/update.php?id=1` | Editable lifecycle fields; validates final merged state |
| POST | `harvests/cancel.php?id=1` | Harvest ID; soft cancellation |
| GET | `harvests/actual-vs-expected.php?harvest_id=1` | Harvest ID |
| GET | `harvests/field-history.php?field_id=1` | Field ID; completed harvests only, newest actual date first |

Example planned body: `{ "field_id": 1, "crop_name_snapshot": "Maize", "expected_harvest_date": "2026-12-01", "expected_quantity_kg": 500, "status": "planned" }`.

Example completed body: `{ "field_id": 1, "crop_name_snapshot": "Maize", "expected_quantity_kg": 500, "actual_harvest_date": "2026-11-28", "actual_quantity_kg": 450, "loss_quantity_kg": 20, "area_harvested_hectares": 1.5, "grade": "A", "quality_notes": "Harvest-time assessment", "status": "completed" }`.

**All quantities are stored and calculated in kilograms.** Expected quantity must be positive when supplied; actual quantity may be zero for a crop failure but cannot be negative. Loss defaults to zero and cannot exceed actual quantity, so a zero-yield harvest has zero recorded post-harvest loss. A completed record requires actual date, quantity and positive harvested area. Harvested area cannot exceed the field area. The creator comes from authentication, not client JSON.

Analysis is calculated from stored values: `difference_kg = actual - expected`; `difference_percent = difference / expected × 100`; `marketable_quantity_kg = actual - loss`; `loss_percent = loss / actual × 100`; `gross_yield_kg_per_hectare = actual / harvested area`; `marketable_yield_kg_per_hectare = marketable / harvested area`. Date variance is actual date minus expected date in days; positive means later. Missing inputs produce `null` derived results rather than invented values. `field-history.php` supplies completed production facts for future yield prediction, but does not predict yield.

Harvest-time grade, quality and losses end at this module. Developer 2 owns storage, spoilage after harvest and dispatch; Developers 3 and 4 own sales and finance; Developer 4 owns notifications based on expected dates. This API creates none of those records.
