# Developer 1 Phase 2B: field pest and disease management

Migration `012_pest_disease.sql` adds `pest_disease_catalog`, `scouting_reports`, and `treatments`. Scouting resolves through `fields.id` to `farms.id`. No canonical Crop/Planting table exists yet, so no `planting_id` was invented; it can be linked through a later agreed migration.

All routes below are direct PHP files under `/api/v1/`. Send the shared Bearer token. Responses use `{ "success": boolean, "data": value|null, "error": string|null }`. The current `verifyJWT()` contract supplies user ID and role; Developer 4 owns real authentication. Admins may write the shared catalogue. Authenticated users may read active entries. Farm owners may access their fields' scouting and treatment records; admins may access all farms.

| Method | Route | Required input |
| --- | --- | --- |
| POST | `pest-disease-catalog/create.php` | JSON `issue_type`, `common_name`; admin |
| GET | `pest-disease-catalog/list.php` | Optional `issue_type`, `status`; active by default |
| GET | `pest-disease-catalog/detail.php?id=1` | Catalogue ID |
| PATCH | `pest-disease-catalog/update.php?id=1` | Editable JSON fields; admin |
| POST | `pest-disease-catalog/deactivate.php?id=1` | Catalogue ID; admin |
| POST | `scouting-reports/create.php` | JSON `field_id`, `observed_at`, `severity`, plus `catalog_id` or `observed_issue_name` |
| GET | `scouting-reports/list.php?field_id=1` | Field ID; newest first |
| GET | `scouting-reports/detail.php?id=1` | Report ID; includes current catalogue reference recommendation |
| PATCH | `scouting-reports/update.php?id=1` | Editable observation/status fields |
| GET | `scouting-reports/outbreak-status.php?farm_id=1` | Farm ID |
| POST | `scouting-reports/treatments/create.php` | JSON `scouting_report_id`, `treatment_type`, `product_or_method` |
| GET | `scouting-reports/treatments/list.php?scouting_report_id=1` | Report ID; newest first |
| GET | `scouting-reports/treatments/detail.php?id=1` | Treatment ID |
| PATCH | `scouting-reports/treatments/update.php?id=1` | Editable treatment lifecycle and effectiveness fields |

Catalogue issue types: `pest`, `disease`. Severity: `low`, `moderate`, `high`, `critical`. Report statuses: `open`, `monitoring`, `treated`, `resolved`. Treatment types: `chemical`, `biological`, `mechanical`, `cultural`, `other`. Treatment statuses: `planned`, `applied`, `monitoring`, `completed`. Dates use `YYYY-MM-DD`; date-times use `YYYY-MM-DD HH:MM:SS`.

Example catalogue body: `{ "issue_type": "pest", "common_name": "Example leaf pest", "common_symptoms": "Visible leaf damage", "recommended_control": "Inspect affected plants and consult local guidance" }`.

Example scouting body: `{ "field_id": 1, "catalog_id": 1, "observed_at": "2026-10-11 09:00:00", "severity": "high", "affected_area_hectares": 0.5, "incidence_percent": 25, "symptoms_notes": "Leaf damage observed" }`. For an unidentified issue, omit `catalog_id` and send `"observed_issue_name": "Unknown leaf spotting"`. The reporter comes from authentication, not the JSON body. Each observation creates a separate historical row.

Example chemical treatment body: `{ "scouting_report_id": 1, "treatment_type": "chemical", "product_or_method": "Recorded product name", "application_rate": 1.5, "application_rate_unit": "L/ha", "quantity_used": 2, "quantity_unit": "L", "applied_at": "2026-10-11 11:00:00", "status": "applied" }`. This records what was reported, not a dosage recommendation. `effectiveness_percent` may remain `NULL` until a follow-up observation and can later be updated from 0–100. No inventory is deducted and no financial transaction is created; those belong to Developers 2 and 4.

`outbreak-status.php` checks unresolved (`open`, `monitoring`, `treated`) observations from the past seven days. It flags a **possible** outbreak if at least one is critical, or if the same catalogue issue is high/critical on at least two distinct fields. Its response includes counts, field IDs, catalogue IDs, and the exact basis. This is **rule-based condition detection, not AI/ML**. It neither creates notifications nor sends alerts; Developer 4 owns delivery.
