# Agent Task: Build FFMS Backend Modules — Developer 2, 3 & 4 Scope

You are building the PHP/MySQL backend for the **Full Farm Management System (FFMS)**.
Your scope covers the modules originally assigned to **Developer 2, Developer 3, and
Developer 4**. Another developer is separately building Developer 1's modules
(Farm & Field Management, Irrigation, Pest & Disease, Harvest, Yield Prediction) —
do not duplicate that work, but assume `farms`, `fields`, and `plantings` tables
will exist and are safe to reference as foreign keys.

Build in the phase order below. Within a phase, the sub-modules can be built in
parallel since they don't depend on each other.

---

## Shared PHP Coding Standard (apply to every module)

- **Folder structure:** `/api/{module}/{action}.php` — one folder per module.
- **Naming:** snake_case for DB columns/tables, camelCase for PHP variables/functions.
- **Response format:** every endpoint returns JSON:
  ```json
  { "success": true, "data": {}, "error": null }
  ```
  On failure: `{ "success": false, "data": null, "error": "message" }`
- **DB access:** use a single shared PDO wrapper (`db.php`) with prepared statements
  only — no raw string-concatenated SQL.
- **Error handling:** wrap all logic in try/catch. Never leak raw PHP errors or
  stack traces to the client.
- **Auth:** every protected endpoint calls a shared `verifyJWT()` function from
  `auth.php` before running. Assume this function exists and returns the
  authenticated user's `id` and `role`; if it doesn't exist yet, stub it to
  accept any bearer token during development and leave a `// TODO: replace stub`
  comment.
- **Versioning:** prefix all routes with `/api/v1/`.
- **Migrations:** write each module's tables as a numbered SQL migration file
  (e.g. `002_inventory.sql`, `003_equipment.sql`) rather than one giant schema file.

---

## PHASE 1 — Foundation (build first)

### Developer 2 scope: Farm Inventory & Inputs
Other modules (crops, livestock, equipment) will consume from this stock, so
build it before anything that deducts inventory.

**Tables**
```sql
inventory_items(id, farm_id, category, name, unit, expiry_date, created_at)
stock_movements(id, item_id, type ENUM('in','out'), quantity, moved_at, notes)
```
**Endpoints**
- `GET/POST/PUT/DELETE /api/v1/inventory-items`
- `POST /api/v1/inventory-items/{id}/stock-in`
- `POST /api/v1/inventory-items/{id}/stock-out`
- `GET /api/v1/inventory-items/low-stock` (returns items below a configurable threshold)

**Features to implement**
- Categories: seeds, fertilizers, chemicals, feed, vet meds, packaging, fuel, tools.
- Expiry-date tracking with a flag for items expiring within 30 days.
- Stock-in/out must update a running balance; never allow stock-out below zero.

### Developer 4 scope: Security & User Management
Build this first — every other endpoint (yours and Developer 2/3's) depends on it.

**Tables**
```sql
users(id, name, email, password_hash, role, created_at)
roles_permissions(id, role, resource, can_read, can_write, can_delete)
audit_logs(id, user_id, action, resource, timestamp)
```
**Endpoints**
- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login` (returns JWT)
- `GET /api/v1/users/me`
- `GET/POST /api/v1/roles-permissions`
- `GET /api/v1/audit-logs`

**Features to implement**
- Roles: admin, farm-owner, farm-manager, agronomist, worker, accountant.
- Passwords hashed with `password_hash()` / verified with `password_verify()`.
- JWT issued on login, verified via the shared `verifyJWT()` function.
- Every write action across the whole system should call a shared
  `logAudit($userId, $action, $resource)` helper — expose it in `auth.php` so
  Developer 2 and 3's modules can call it too.

*(Developer 3's Phase 1 module — Crop Management — is being built by Developer 1's
team per the current plan; skip it unless told otherwise.)*

---

## PHASE 2 — Core Daily Operations

### Developer 2 scope: Farm Equipment & Machinery + Labour & Employee Management

**Tables**
```sql
equipment(id, farm_id, name, type, purchase_date, purchase_cost)
equipment_usage_logs(id, equipment_id, date, hours_used, fuel_used)
maintenance_records(id, equipment_id, date, type, cost, notes)

employees(id, farm_id, name, role, contact, wage_rate)
attendance(id, employee_id, date, hours_worked)
task_assignments(id, employee_id, task_desc, field_id, assigned_at, status)
payroll(id, employee_id, period, amount, paid_at)
```
**Endpoints**
- `GET/POST/PUT/DELETE /api/v1/equipment`
- `POST /api/v1/equipment/{id}/usage-logs`
- `POST /api/v1/equipment/{id}/maintenance`
- `GET /api/v1/equipment/{id}/depreciation` (simple straight-line calc)
- `GET/POST/PUT/DELETE /api/v1/employees`
- `POST /api/v1/employees/{id}/attendance`
- `POST /api/v1/task-assignments`
- `GET /api/v1/employees/{id}/payroll`

### Developer 3 scope: Livestock Management

**Tables**
```sql
animals(id, farm_id, tag_id, breed, dob, sex, status)
vaccinations(id, animal_id, vaccine, date_given, next_due)
animal_treatments(id, animal_id, condition, treatment, date)
weight_logs(id, animal_id, weight_kg, recorded_at)
breeding_records(id, female_id, male_id, mating_date, expected_birth)
```
**Endpoints**
- `GET/POST/PUT/DELETE /api/v1/animals`
- `POST /api/v1/animals/{id}/vaccinations`
- `POST /api/v1/animals/{id}/treatments`
- `POST /api/v1/animals/{id}/weight-logs`
- `GET /api/v1/livestock/mortality-analysis`

**Features to implement**
- `next_due` on vaccinations should be queryable so Developer 4's Notifications
  module can later pull "vaccinations due in next 7 days."
- Mortality analysis: simple ratio of `status='deceased'` over total animals,
  filterable by date range and breed.

### Developer 4 scope: Financial Management + Weather & Environmental Monitoring

**Tables**
```sql
transactions(id, farm_id, type ENUM('income','expense'), category, amount, date)
loans(id, farm_id, lender, amount, interest_rate, due_date)
budgets(id, farm_id, period, category, planned_amount)

weather_logs(id, farm_id, date, rainfall_mm, temp_c, humidity, wind_kmh)
```
**Endpoints**
- `GET/POST/PUT/DELETE /api/v1/transactions`
- `GET/POST /api/v1/loans`
- `GET /api/v1/financials/profit-loss`
- `GET /api/v1/financials/cost-per-hectare`
- `GET /api/v1/farms/{id}/weather/current` (integrate a weather API — use a
  config-driven API key, do not hardcode it)
- `GET /api/v1/farms/{id}/weather/forecast`
- `GET /api/v1/farms/{id}/weather/history` (from `weather_logs`)

---

## PHASE 3 — Trade & Post-Harvest

### Developer 2 scope: Storage & Post-Harvest Management

**Tables**
```sql
storage_facilities(id, farm_id, type, capacity)
stored_produce(id, facility_id, planting_id, quantity, grade, stored_at)
dispatch_records(id, stored_produce_id, quantity, dispatched_at, destination)
```
**Endpoints**
- `GET/POST/PUT/DELETE /api/v1/storage-facilities`
- `POST /api/v1/storage-facilities/{id}/stored-produce`
- `POST /api/v1/stored-produce/{id}/dispatch`

**Features to implement**
- Dispatch must decrement `stored_produce.quantity`; block dispatch exceeding
  available quantity.

### Developer 3 scope: Market & Sales Management + Supplier & Procurement Management

**Tables**
```sql
customers(id, name, contact, type ENUM('buyer','broker'))
sales_orders(id, customer_id, produce_id, quantity, price, status)
invoices(id, sales_order_id, amount, issued_at, paid_at)
contracts(id, customer_id, terms, start_date, end_date)

suppliers(id, name, contact, category)
purchase_orders(id, supplier_id, item_id, quantity, price, status)
supplier_payments(id, purchase_order_id, amount, paid_at)
```
**Endpoints**
- `GET/POST/PUT/DELETE /api/v1/customers`
- `GET/POST/PUT/DELETE /api/v1/sales-orders`
- `POST /api/v1/sales-orders/{id}/invoice`
- `GET/POST /api/v1/contracts`
- `GET/POST/PUT/DELETE /api/v1/suppliers`
- `GET/POST/PUT/DELETE /api/v1/purchase-orders`
- `POST /api/v1/purchase-orders/{id}/payments`

**Features to implement**
- `purchase_orders` on completion should call Developer 2's stock-in endpoint
  (or write directly to `stock_movements` if calling cross-module isn't wired
  up yet — leave a comment either way).

### Developer 4 scope: Notifications & Alerts

**Tables**
```sql
notifications(id, user_id, type, message, is_read, created_at)
alert_rules(id, module, condition, threshold)
```
**Endpoints**
- `GET /api/v1/notifications`
- `POST /api/v1/notifications/mark-read`
- `GET/POST /api/v1/alert-rules`

**Features to implement**
- Rule engine should check: low inventory (Dev 2), equipment maintenance due
  (Dev 2), vaccinations due (Dev 3), loan due dates (Dev 4) — pull these via
  direct DB queries, not cross-service calls, for simplicity.
- Delivery via SMS/Email API — stub the send function behind an interface so
  the provider (Twilio/SendGrid) can be swapped later.

---

## PHASE 4 — Prediction, Dashboard & Reports (build last — needs real data)

### Developer 2 scope: Reporting Support (no new tables)
- `GET /api/v1/inventory-items/summary`
- `GET /api/v1/equipment/summary`
These just aggregate your Phase 1/2 tables for Developer 4's dashboard to consume.

### Developer 3 scope: Sales / Revenue Prediction

**Tables**
```sql
price_trends(id, crop_id, market, date, price)
revenue_forecasts(id, planting_id, expected_price, expected_revenue)
```
**Endpoints**
- `GET /api/v1/crops/{id}/price-trends`
- `GET /api/v1/plantings/{id}/revenue-forecast`

**Features to implement**
- Simple forecast: moving average of `price_trends` × expected yield from
  Developer 1's `yield_predictions` table (read-only join, no write access needed).

### Developer 4 scope: Farm Dashboard & Analytics + Reports & Analytics

**Endpoints**
- `GET /api/v1/dashboard/summary`
- `GET /api/v1/dashboard/{farm_id}`
- `GET /api/v1/reports/{type}?farm_id=&from=&to=`
- `GET /api/v1/reports/{type}/export` (PDF/Excel)

**Features to implement**
- Dashboard aggregates: acreage, active crops, livestock counts, expenses,
  revenue, profit, inventory levels, weather, pest alerts, market prices —
  pull read-only from every table built in Phases 1–3.
- Reports: crop, yield, livestock, financial, expense, sales, inventory,
  labour, irrigation, pest/disease, equipment, profitability, farm performance.
- Export via a PDF/Excel library (e.g. dompdf, PhpSpreadsheet); charts on the
  frontend use Chart.js — just return the raw JSON data from these endpoints.

---

## Deliverable Checklist (work through in order)

- [ ] Phase 1: inventory tables/endpoints, auth tables/endpoints
- [ ] Phase 2: equipment, labour, livestock, financial, weather
- [ ] Phase 3: storage, market/sales, suppliers, notifications
- [ ] Phase 4: reporting support, revenue prediction, dashboard, reports
- [ ] All endpoints follow the shared JSON response format
- [ ] All protected endpoints call `verifyJWT()`
- [ ] All write actions call `logAudit()` where applicable
- [ ] SQL migrations are numbered and separated by module
- [ ] No hardcoded API keys or secrets — use environment variables
