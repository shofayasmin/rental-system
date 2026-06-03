# Final Schema Snapshot (Relevant for Mapping Dictionary)

Date: `2026-05-23`  
Scope: tables used by dashboard KPI, property filtering, and synthetic import pipeline.

## How to pick migrations quickly
Read these migration groups:

1. `properties`
- `2025_12_13_031938_create_properties_table.php`
- `2026_05_02_153156_add_location_and_room_columns_to_properties_table.php`
- `2026_05_08_004200_add_building_area_to_properties_table.php`
- `2026_05_13_120000_add_description_to_properties_table.php`
- `2026_05_13_163000_add_floors_to_properties_table.php`
- `2026_05_16_123000_add_coordinates_to_properties_table.php`
- `2026_05_08_020000_drop_layout_and_region_from_properties_table.php`

2. `rental_requests`
- `2025_12_13_031938_create_rental_requests_table.php`
- `2025_12_14_182047_add_message_to_rental_requests_table.php`
- `2026_04_13_000004_add_soft_lock_statuses_to_rental_requests.php`
- `2026_04_13_000005_rename_pending_to_pending_review_in_rental_requests.php`
- `2026_04_30_152522_add_availability_cycle_id_to_rental_requests_table.php`
- `2026_05_03_160426_hard_remove_queued_from_rental_requests.php`
- `2026_05_04_155312_add_payment_due_at_to_rental_requests_table.php`

3. `transactions`
- `2025_12_13_031938_create_transactions_table.php`
- `2025_12_14_184434_add_property_id_to_transactions_table.php`
- `2025_12_14_184628_add_tenant_id_to_transactions_table.php`
- `2025_12_14_184821_add_agent_id_to_transactions_table.php`
- `2026_04_09_000003_add_recommended_extension_workflow_columns.php`
- `2026_04_21_000008_enforce_contract_and_transaction_uniqueness.php`

4. `contracts` + `contract_extensions`
- `2025_12_13_072854_create_contracts_table.php`
- `2026_04_08_000001_add_status_to_contracts_table.php`
- `2026_04_21_000007_remove_extended_status_from_contracts.php`
- `2026_05_12_120000_add_early_checkout_columns_to_contracts_table.php`
- `2026_04_08_000002_create_contract_extensions_table.php`
- `2026_04_09_000003_add_recommended_extension_workflow_columns.php`
- `2026_05_18_120000_add_payment_due_at_to_contract_extensions_table.php`
- `2026_05_20_000001_add_cancelled_by_tenant_to_contract_extension_status_enum.php`
- `2026_05_20_000002_add_cancelled_at_to_contract_extensions_table.php`

5. Availability cycle
- `2026_04_30_151918_create_property_availability_cycles_table.php`

6. Indonesia location master
- `2026_05_02_153154_create_indonesia_region_tables.php`

7. Facilities master + pivot
- `2026_05_02_153155_create_facilities_and_property_facility_tables.php`

---

## Final table shape (practical)

## `properties`
Core columns:
- `id` (PK)
- `agent_id` (FK -> `users.id`)
- `title`
- `province_id` (nullable FK -> `provinces.id`)
- `regency_id` (nullable FK -> `regencies.id`)
- `district_id` (nullable FK -> `districts.id`)
- `village_id` (nullable FK -> `villages.id`)
- `address` (nullable)
- `description` (nullable text)
- `latitude` (`decimal(10,7)`, nullable)
- `longitude` (`decimal(10,7)`, nullable)
- `bedrooms` (`unsignedTinyInteger`, default `0`)
- `bathrooms` (`decimal(3,1)`, default `1.0`)
- `floors` (`unsignedTinyInteger`, nullable)
- `area` (`float`, nullable)
- `building_area` (`decimal(10,2)`, nullable)
- `facilities` (`json`, legacy nullable)
- `rent_price` (`decimal(12,2)`)
- `status` enum: `to-let | rented | maintenance`
- `created_at`, `updated_at`

Important notes:
- `layout` and `region` are already removed.

Important indexes:
- `status`
- `rent_price`
- `bedrooms`
- `bathrooms`
- composite: (`province_id`, `regency_id`, `district_id`, `village_id`)

---

## `rental_requests`
Core columns:
- `id` (PK)
- `property_id` (FK -> `properties.id`)
- `availability_cycle_id` (nullable FK -> `property_availability_cycles.id`, `nullOnDelete`)
- `tenant_id` (FK -> `users.id`)
- `status` enum final:
  - `pending_review`
  - `awaiting_payment`
  - `paid`
  - `rejected`
  - `cancelled_by_tenant`
  - `cancelled_by_agent`
  - `cancelled_lost`
- timestamps:
  - `awaiting_payment_at`
  - `payment_due_at`
  - `paid_at`
  - `rejected_at`
  - `cancelled_at`
- `message` (nullable)
- `created_at`, `updated_at`

Important indexes:
- composite: (`property_id`, `availability_cycle_id`)
- `payment_due_at` (`rental_requests_payment_due_at_idx`)

---

## `transactions`
Core columns:
- `id` (PK)
- `rental_request_id` (FK -> `rental_requests.id`)
- `property_id` (FK -> `properties.id`)
- `tenant_id` (FK -> `users.id`)
- `agent_id` (FK -> `users.id`)
- `contract_extension_id` (nullable FK -> `contract_extensions.id`, `nullOnDelete`)
- `amount` (`decimal(12,2)`)
- `type` enum: `initial_rent | extension_rent` (default `initial_rent`)
- `status` enum: `unpaid | paid | failed`
- `created_at`, `updated_at`

Important constraint:
- unique (`rental_request_id`, `type`) as `transactions_request_type_unique`

---

## `contracts`
Core columns:
- `id` (PK)
- `rental_request_id` (FK -> `rental_requests.id`)
- `start_date`, `end_date`
- `total_price` (`decimal(12,2)`)
- `monthly_rent` (`decimal(12,2)`, nullable)
- `status` enum: `active | ended`
- `ended_reason` (nullable text)
- `ended_by` (nullable FK -> `users.id`)
- `ended_at` (nullable timestamp)
- `created_at`, `updated_at`

Important constraint:
- unique (`rental_request_id`) as `contracts_rental_request_unique`

---

## `contract_extensions`
Core columns:
- `id` (PK)
- `contract_id` (FK -> `contracts.id`)
- `old_end_date`, `new_end_date`
- `extended_at`
- `months_requested` (unsigned integer)
- `monthly_rent_snapshot` (`decimal(12,2)`, nullable)
- `amount` (`decimal(12,2)`, nullable)
- `status` enum:
  - `pending`
  - `awaiting_payment`
  - `paid`
  - `rejected`
  - `cancelled_by_tenant`
  - `expired`
- `approved_by` (nullable FK -> `users.id`)
- `approved_at` (nullable timestamp)
- `payment_due_at` (nullable timestamp)
- `rejected_at` (nullable timestamp)
- `cancelled_at` (nullable timestamp)
- `paid_at` (nullable timestamp)
- `created_at`, `updated_at`

Important indexes:
- `payment_due_at` (`contract_extensions_payment_due_at_idx`)

---

## `property_availability_cycles`
Core columns:
- `id` (PK)
- `property_id` (FK -> `properties.id`)
- `available_from_at` (timestamp)
- `unavailable_at` (nullable timestamp)
- `closed_by` enum: `rented | maintenance | manual` (nullable)
- `created_at`, `updated_at`

Important indexes:
- (`property_id`, `available_from_at`)
- (`property_id`, `unavailable_at`)

---

## `provinces`
- `id` (PK)
- `name`
- `code` (unique)
- `created_at`, `updated_at`

## `regencies`
- `id` (PK)
- `province_id` (FK -> `provinces.id`)
- `name`
- `code` (unique)
- `created_at`, `updated_at`
- index: `province_id`

## `districts`
- `id` (PK)
- `regency_id` (FK -> `regencies.id`)
- `name`
- `code` (unique)
- `created_at`, `updated_at`
- index: `regency_id`

## `villages`
- `id` (PK)
- `district_id` (FK -> `districts.id`)
- `name`
- `code` (unique)
- `created_at`, `updated_at`
- index: `district_id`

---

## `facilities`
- `id` (PK)
- `slug` (unique)
- `name`
- `category` enum: `utilities | interior | outdoor`
- `is_active` (`boolean`, default `true`)
- `created_at`, `updated_at`
- index: `category`

## `property_facility`
- `id` (PK)
- `property_id` (FK -> `properties.id`)
- `facility_id` (FK -> `facilities.id`)
- `value` (nullable string 100)
- `created_at`, `updated_at`
- unique: (`property_id`, `facility_id`) as `property_facility_unique`
- index: `facility_id`

---

## Minimal notes for mapping dictionary
Use these as source of truth:
1. `rental_requests.status` must use final enum above.
2. `transactions.type` and `transactions.status` must use final enums above.
3. Location mapping targets hierarchy `province_id` -> `regency_id` -> `district_id` -> `village_id`.
4. Facilities map to `facilities.slug` then write to `property_facility`.
5. Dashboard KPI currently filters period by `rental_requests.created_at` and computes revenue from paid transactions linked to paid requests.
