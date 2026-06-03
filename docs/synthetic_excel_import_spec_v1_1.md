# Synthetic Excel Import Spec (v1.1)

Date: `2026-05-23`  
Scope: reset data domain dan isi ulang dari Excel untuk dashboard KPI `/admin/dashboard`.

## 1) Target Akhir Dataset

- Period: `2025-01-01 00:00:00` sampai `2026-04-30 23:59:59`
- Total properties: `600`
- Total agents: `30`
- Total tenants: `1,500`
- Total rental requests: `6,000–8,000` (recommended fixed awal: `6,000`)
- Total `initial_rent` transactions: `2,000–3,000`

KPI target:
- Total Rentals (paid): `2,000–2,600`
- Average Rent Price: `Rp3.5jt–Rp5.5jt`
- Average Time to Rent: `9–16 hari`
- Occupancy Rate snapshot: `65–72%`

## 2) Cakupan Wilayah dan Distribusi

City bucket:
- Jakarta `28%`
- Bandung `16%`
- Surabaya `14%`
- Medan `10%`
- Makassar `8%`
- Yogyakarta `8%`
- Semarang `8%`
- Denpasar `8%`

Catatan:
- Mapping wilayah wajib pakai `province_id/regency_id/district_id/village_id` (bukan `region`).
- `latitude/longitude` harus konsisten dengan city bucket administratif.

## 3) Struktur Workbook Excel

Satu workbook `.xlsx` dengan urutan sheet:
1. `00_config`
2. `01_users`
3. `02_properties`
4. `03_property_facility`
5. `04_availability_cycles`
6. `05_rental_requests`
7. `06_transactions`
8. `07_contracts`
9. `08_contract_extensions`
10. `09_conversations`
11. `10_conversation_messages`

Semua relasi antar-sheet pakai kode natural (`*_code`), bukan ID DB.

## 4) Spesifikasi Kolom per Sheet

## 4.1 `00_config`

Kolom:
- `key` (required)
- `value` (required)

Minimal keys:
- `period_start`
- `period_end`
- `total_properties`
- `total_agents`
- `total_tenants`
- `target_requests`
- `random_seed`

## 4.2 `01_users`

Kolom:
- `user_code` (required, unique) contoh: `USR_AGT_0001`
- `name` (required)
- `email` (required, unique)
- `role` (required: `admin|agent|tenant`)
- `enabled` (required: `0|1`)

Aturan:
- minimal 1 admin tetap: `admin@test.com` via update-or-create logic.
- target role count: admin >= 1, agent = 30, tenant = 1500.

## 4.3 `02_properties`

Kolom:
- `property_code` (required, unique)
- `agent_code` (required, ref `01_users.user_code` role=agent)
- `city_bucket` (required)
- `province_code` (required)
- `regency_code` (required)
- `district_code` (required)
- `village_code` (required)
- `title` (required)
- `address` (optional)
- `description` (optional)
- `bedrooms` (required, integer >= 0)
- `bathrooms` (required, decimal >= 1.0)
- `floors` (optional, integer >= 1)
- `area` (optional, numeric > 0)
- `building_area` (optional, numeric > 0)
- `rent_price` (required, numeric > 0)
- `status` (required: `to-let|rented|maintenance`)
- `latitude` (optional, -90..90)
- `longitude` (optional, -180..180)

Aturan distribusi:
- total properties = 600.
- city bucket mengikuti distribusi target.
- status snapshot:
  - `rented` 68%
  - `to-let` 24%
  - `maintenance` 8%

## 4.4 `03_property_facility`

Kolom:
- `property_code` (required)
- `facility_slug` (required, ref facilities master)
- `value` (optional, max 100)

Allowed facility slug:
- `electricity`, `water_supply`, `wifi`, `ac`, `water_heater`
- `furnished`, `kitchen_set`, `wardrobe`
- `carport`, `garden`, `balcony`, `backyard`

Constraint:
- unique pair (`property_code`, `facility_slug`).

## 4.5 `04_availability_cycles`

Kolom:
- `cycle_code` (required, unique)
- `property_code` (required)
- `available_from_at` (required datetime)
- `unavailable_at` (optional datetime)
- `closed_by` (optional: `rented|maintenance|manual`)

Aturan wajib:
- tiap properti minimal 1 cycle.
- properti `rented` tidak boleh punya active cycle (`unavailable_at` tidak null).
- properti `to-let` harus punya active cycle (`unavailable_at` null).
- jika `unavailable_at` terisi, harus >= `available_from_at`.

## 4.6 `05_rental_requests`

Kolom:
- `request_code` (required, unique)
- `property_code` (required)
- `tenant_code` (required, ref role tenant)
- `cycle_code` (required, ref `04_availability_cycles.cycle_code`)
- `status` (required enum final):
  - `pending_review`
  - `awaiting_payment`
  - `paid`
  - `rejected`
  - `cancelled_by_tenant`
  - `cancelled_by_agent`
  - `cancelled_lost`
- `created_at` (required datetime)
- `awaiting_payment_at` (conditional)
- `payment_due_at` (conditional)
- `paid_at` (conditional)
- `rejected_at` (conditional)
- `cancelled_at` (conditional)
- `message` (optional)

Aturan status:
- `paid` => `paid_at` wajib.
- `awaiting_payment` => `awaiting_payment_at` wajib, `payment_due_at` wajib.
- `rejected` => `rejected_at` wajib.
- `cancelled_*` => `cancelled_at` wajib.
- `queued` tidak boleh muncul.

Distribusi status rekomendasi:
- `paid` 35%
- `pending_review` 25%
- `awaiting_payment` 10%
- `rejected` 15%
- `cancelled_*` 15%

## 4.7 `06_transactions`

Kolom:
- `tx_code` (required, unique)
- `request_code` (required)
- `type` (required: `initial_rent|extension_rent`)
- `status` (required: `unpaid|paid|failed`)
- `amount` (required numeric >= 0)
- `extension_code` (conditional: required if `type=extension_rent`)

Aturan:
- unique per request+type (sesuai constraint DB).
- request `paid` wajib punya `initial_rent` status `paid`.
- request `awaiting_payment` biasanya punya `initial_rent` `unpaid`.
- request `cancelled_lost` biasanya punya `initial_rent` `failed`.

## 4.8 `07_contracts`

Kolom:
- `contract_code` (required, unique)
- `request_code` (required, unique)
- `start_date` (required date)
- `end_date` (required date)
- `monthly_rent` (required numeric > 0)
- `total_price` (required numeric > 0)
- `status` (required: `active|ended`)
- `ended_reason` (optional)
- `ended_at` (conditional, if ended)

Aturan:
- hanya boleh dibuat dari request `paid`.
- `active` jika `end_date >= today`; selain itu `ended`.

## 4.9 `08_contract_extensions`

Kolom:
- `extension_code` (required, unique)
- `contract_code` (required)
- `old_end_date` (required date)
- `new_end_date` (required date)
- `extended_at` (required datetime)
- `months_requested` (required integer 1..12)
- `monthly_rent_snapshot` (required numeric > 0)
- `amount` (required numeric > 0)
- `status` (required):
  - `pending`
  - `awaiting_payment`
  - `paid`
  - `rejected`
  - `cancelled_by_tenant`
  - `expired`
- `approved_at` (conditional)
- `payment_due_at` (conditional)
- `paid_at` (conditional)
- `rejected_at` (conditional)
- `cancelled_at` (conditional)

Aturan:
- jika status `awaiting_payment` => `approved_at` dan `payment_due_at` wajib.
- jika status `paid` => `paid_at` wajib.
- jika status `cancelled_by_tenant` => `cancelled_at` wajib.
- jika status `rejected` => `rejected_at` wajib.

## 4.10 `09_conversations`

Kolom:
- `conversation_code` (required, unique)
- `tenant_code` (required)
- `agent_code` (required)
- `property_code` (optional)
- `request_code` (optional)
- `status` (required: `open|closed`)
- `last_message_at` (optional datetime)

Aturan:
- unique pair (`tenant_code`, `agent_code`) untuk sinkron dengan unique index DB.

## 4.11 `10_conversation_messages`

Kolom:
- `message_code` (required, unique)
- `conversation_code` (required)
- `sender_code` (required)
- `property_code` (optional)
- `request_code` (optional)
- `message` (required text)
- `created_at` (required datetime)

Aturan:
- `sender_code` harus salah satu peserta conversation (tenant/agent).

## 5) Rule Waktu dan Realisme

- Musiman: volume request naik di `Jun–Aug` dan `Nov–Jan`.
- Weekday effect: request lebih tinggi Senin–Kamis.
- Time-to-rent:
  - Jakarta/Bandung median 8–12 hari
  - kota lain median 10–18 hari
  - outlier 30–60 hari maksimal 5% paid requests

## 6) Import Order ke DB

1. users
2. properties
3. property_facility
4. property_availability_cycles
5. rental_requests
6. transactions
7. contracts
8. contract_extensions
9. conversations
10. conversation_messages

## 7) Validator Checklist Pasca-Import

- Null hierarchy lokasi (`province/regency/district/village`) = 0
- Invalid location hierarchy = 0
- `paid` request tanpa `paid_at` = 0
- `paid` request tanpa `initial_rent:paid` = 0
- properti `rented` dengan active cycle = 0
- duplicate `(rental_request_id, type)` di transactions = 0
- invalid extension link (`extension_rent` tanpa extension) = 0
- occupancy snapshot dalam target 65–72%
- KPI utama berada pada range target

## 8) Reset + Import Procedure

1. Backup jika perlu.
2. `php artisan migrate:fresh`
3. Pastikan `admin@test.com` di-create via upsert.
4. Jalankan importer Excel sesuai urutan sheet di atas.
5. Jalankan validator checks dan simpan report.
