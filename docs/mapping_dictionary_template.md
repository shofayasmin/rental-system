# Mapping Dictionary

Version: `v1.2`  
Date: `2026-05-26`  
Dataset Source: `Synthetic Indonesia Rentals v1 (generated)`  
Owner: `Shofa`

## 1) Dataset Metadata
| Field | Value |
|---|---|
| Dataset Name | Synthetic Indonesia Rentals v1 |
| File Name | synthetic_properties.csv, synthetic_requests.csv, synthetic_transactions.csv |
| File Type | CSV |
| Timezone | Asia/Shanghai |
| Period Start | 2025-01-01 00:00:00 |
| Period End | 2026-04-30 23:59:59 |
| Total Rows (Raw) | ~11,000 |
| Total Rows (After Cleaning) | ~10,700 |

## 2) Column Mapping (Source -> Target)
| No | Source Table/File | Source Column | Target Table | Target Column | Data Type | Required | Transform Rule | Default/Fallback | Notes |
|---|---|---|---|---|---|---|---|---|---|
| 1 | synthetic_properties | property_title | properties | title | string | yes | trim + normalize spaces | `Untitled Property` |  |
| 2 | synthetic_properties | province_code | properties | province_id | bigint | yes | lookup `provinces.code` | reject row |  |
| 3 | synthetic_properties | regency_code | properties | regency_id | bigint | yes | lookup `regencies.code` under province | reject row |  |
| 4 | synthetic_properties | district_code | properties | district_id | bigint | yes | lookup `districts.code` under regency | reject row |  |
| 5 | synthetic_properties | village_code | properties | village_id | bigint | yes | lookup `villages.code` under district | reject row |  |
| 6 | synthetic_properties | bedrooms_count | properties | bedrooms | tinyint | yes | parse integer >= 0 | 0 |  |
| 7 | synthetic_properties | bathrooms_count | properties | bathrooms | decimal(3,1) | yes | parse decimal, round to 0.5 step | 1.0 |  |
| 8 | synthetic_properties | monthly_rent_idr | properties | rent_price | decimal(12,2) | yes | cast numeric, positive only | reject row | IDR |
| 9 | synthetic_properties | property_status | properties | status | enum | yes | dictionary map | `to-let` | target: to-let/rented/maintenance |
| 10 | synthetic_properties | address_text | properties | address | string | no | trim | null |  |
| 11 | synthetic_properties | description_text | properties | description | text | no | trim | null |  |
| 12 | synthetic_properties | land_area_m2 | properties | area | float | no | parse float | null |  |
| 13 | synthetic_properties | building_area_m2 | properties | building_area | decimal(10,2) | no | parse numeric >= 1 | null |  |
| 14 | synthetic_properties | floors_count | properties | floors | tinyint | no | parse integer >= 1 | null |  |
| 15 | synthetic_properties | latitude | properties | latitude | decimal(10,7) | no | parse decimal, range -90..90 | null |  |
| 16 | synthetic_properties | longitude | properties | longitude | decimal(10,7) | no | parse decimal, range -180..180 | null |  |
| 17 | synthetic_requests | request_status | rental_requests | status | enum | yes | dictionary map | `pending_review` | final enum only |
| 18 | synthetic_requests | request_created_at | rental_requests | created_at | datetime | yes | parse datetime | now() |  |
| 19 | synthetic_requests | awaiting_payment_at | rental_requests | awaiting_payment_at | datetime | conditional | parse datetime | null | required if status=awaiting_payment |
| 20 | synthetic_requests | request_due_at | rental_requests | payment_due_at | datetime | conditional | set `awaiting_payment_at + 7 days` | null | required if status=awaiting_payment |
| 21 | synthetic_requests | request_paid_at | rental_requests | paid_at | datetime | conditional | parse datetime | null | required if status=paid |
| 22 | synthetic_requests | request_rejected_at | rental_requests | rejected_at | datetime | conditional | parse datetime | null | required if status=rejected |
| 23 | synthetic_requests | request_cancelled_at | rental_requests | cancelled_at | datetime | conditional | parse datetime | null | required if status starts with cancelled_ |
| 24 | synthetic_requests | availability_cycle_ref | rental_requests | availability_cycle_id | bigint | yes | bind to active cycle key | reject row | must match property |
| 25 | synthetic_transactions | transaction_type | transactions | type | enum | yes | dictionary map | `initial_rent` | initial_rent / extension_rent |
| 26 | synthetic_transactions | transaction_amount_idr | transactions | amount | decimal(12,2) | yes | cast numeric, >= 0 | reject row |  |
| 27 | synthetic_transactions | transaction_status | transactions | status | enum | yes | dictionary map | `unpaid` | unpaid/paid/failed |
| 28 | synthetic_transactions | extension_ref | transactions | contract_extension_id | bigint | conditional | map to `contract_extensions.id` | null | required if type=extension_rent |
| 29 | synthetic_cycles | available_from | property_availability_cycles | available_from_at | datetime | yes | parse datetime | now() |  |
| 30 | synthetic_cycles | unavailable_at | property_availability_cycles | unavailable_at | datetime | no | parse datetime | null |  |
| 31 | synthetic_cycles | cycle_closed_by | property_availability_cycles | closed_by | enum | no | dictionary map | null | rented/maintenance/manual |

## 3) Value Mapping - Status
### 3.1 Property Status
| Source Value | Target Value (`properties.status`) | Rule/Notes |
|---|---|---|
| available | to-let | listing is open for rent |
| occupied | rented | currently rented |
| under_maintenance | maintenance | unavailable for technical reason |

### 3.2 Rental Request Status
| Source Value | Target Value (`rental_requests.status`) | Rule/Notes |
|---|---|---|
| new | pending_review | initial request |
| waiting_payment | awaiting_payment | payment stage |
| success | paid | `paid_at` must be filled |
| declined | rejected | rejected by agent |
| cancelled_tenant | cancelled_by_tenant | canceled by tenant |
| cancelled_agent | cancelled_by_agent | canceled by agent |
| lost | cancelled_lost | lost due to another winner |
| queued | pending_review | legacy status mapped to final enum |

### 3.3 Transaction Status & Type
| Source Value | Target Value | Column | Rule/Notes |
|---|---|---|---|
| success | paid | transactions.status | payment completed |
| pending | unpaid | transactions.status | waiting payment |
| failed | failed | transactions.status | payment failed |
| rent_first_month | initial_rent | transactions.type | main KPI scope |
| extension_fee | extension_rent | transactions.type | separate KPI scope |

## 4) Value Mapping - Facilities (Master + Pivot)
### 4.1 Facilities Master Dictionary
| Source Label | Target Slug | Target Name | Category (`utilities/interior/outdoor`) | Active |
|---|---|---|---|---|
| electricity / electric power | electricity | Electricity | utilities | 1 |
| water / clean water | water_supply | Water Supply | utilities | 1 |
| wifi / internet | wifi | WiFi | utilities | 1 |
| ac / air conditioner | ac | Air Conditioner | utilities | 1 |
| water heater / heater | water_heater | Water Heater | utilities | 1 |
| furnished | furnished | Furnished | interior | 1 |
| kitchen set | kitchen_set | Kitchen Set | interior | 1 |
| wardrobe / closet | wardrobe | Wardrobe | interior | 1 |
| carport / parking | carport | Carport | outdoor | 1 |
| garden | garden | Garden | outdoor | 1 |
| balcony | balcony | Balcony | outdoor | 1 |
| backyard | backyard | Backyard | outdoor | 1 |

### 4.2 Unknown Facility Handling
| Case | Action |
|---|---|
| Unknown facility label | add to review list, do not insert directly |
| Valid new synonym | add synonym to dictionary and rerun mapping |

## 5) Cleaning Rules
| Rule ID | Rule | Affected Column(s) | Action |
|---|---|---|---|
| C1 | Trim whitespace | all string fields | trim |
| C2 | Normalize currency | rent/amount | remove symbol and separators, cast numeric |
| C3 | Normalize datetime | date/timestamp | convert to `YYYY-MM-DD HH:MM:SS` |
| C4 | Region hierarchy validation | province/regency/district/village | reject row if hierarchy mismatch |
| C5 | Bedrooms/Bathrooms parse | bedrooms_count/bathrooms_count | parse numeric, enforce min values |
| C6 | Required fields | mandatory columns | reject row if empty |
| C7 | Request-payment consistency | request status + paid_at | if status=paid then `paid_at` required |
| C8 | Cycle consistency | available_from/unavailable_at | reject if unavailable_at < available_from |
| C9 | Coordinate range validation | latitude/longitude | reject row if out of range |
| C10 | Extension consistency | transactions.type + contract_extension_id | if type=extension_rent then extension id required |

## 6) Data Quality Checks
| Check | Query/Method | Target |
|---|---|---|
| Null rate `province_id/regency_id/district_id/village_id` | SQL count null | 0% |
| Null rate `paid_at` for paid requests | SQL conditional count | 0% |
| Invalid FK on `property_facility` | SQL left join missing | 0 rows |
| Orphan transactions | SQL missing request/property | 0 rows |
| Active cycle per property > 1 | SQL group by + having | 0 rows |
| Request and transaction mismatch (`paid` vs non-`paid`) | SQL join status check | 0 rows |
| Out-of-range coordinates | SQL where latitude/longitude invalid | 0 rows |
| Invalid extension transaction link | SQL extension_rent with null extension id | 0 rows |
| Invalid conversations uniqueness | SQL duplicate `(tenant_id, agent_id)` | 0 rows |

## 7) Import Order
1. `users` (admin/agent/tenant)
2. `provinces` -> `regencies` -> `districts` -> `villages`
3. `properties`
4. `facilities` (master)
5. `property_facility` (pivot)
6. `property_availability_cycles`
7. `rental_requests`
8. `contracts`
9. `contract_extensions`
10. `transactions`
11. `conversations`
12. `conversation_messages`

## 8) KPI Definition
### 8.1 Global Rules
| Field | Value |
|---|---|
| Timezone | Asia/Shanghai |
| Default Period Filter | `rental_requests.created_at` (dashboard date range, default last 30 days) |
| Currency | IDR |
| Scope | Valid domain rows (FK consistent, status final enum) |

### 8.2 Total Rentals
| Item | Definition |
|---|---|
| Business Meaning | Total successful rentals in selected period |
| Formula | `COUNT(rental_requests.id WHERE status = 'paid' AND paid_at IS NOT NULL)` |
| Source | `rental_requests` |
| Output | Integer |
| Notes | Include only rows with final status `paid` |

### 8.3 Average Rent Price
| Item | Definition |
|---|---|
| Business Meaning | Average monthly rent for successfully rented properties |
| Formula | `AVG(properties.rent_price)` over join with `rental_requests.status='paid'` |
| Source | `properties` + `rental_requests` |
| Output | Numeric (IDR) |
| Notes | Join by `rental_requests.property_id = properties.id` |

### 8.4 Average Time to Rent
| Item | Definition |
|---|---|
| Business Meaning | Average duration from property availability opening to paid |
| Formula | `AVG(TIMESTAMPDIFF(HOUR, pac.available_from_at, rental_requests.paid_at)) / 24` |
| Source | `rental_requests` + `property_availability_cycles` |
| Output | Days (decimal) |
| Notes | Filter: `status='paid'`, `paid_at IS NOT NULL`, `pac.available_from_at IS NOT NULL` |

### 8.5 Occupancy Rate (Snapshot)
| Item | Definition |
|---|---|
| Business Meaning | Percentage of currently occupied properties at snapshot time |
| Formula | `COUNT(properties.status='rented') / COUNT(properties.id) * 100` |
| Source | `properties` |
| Output | Percent (%) |
| Notes | Snapshot KPI uses current `properties.status` |

### 8.6 Revenue
| Item | Definition |
|---|---|
| Business Meaning | Total paid transaction amount in selected period scope |
| Formula | `SUM(transactions.amount)` on join `transactions.rental_request_id = rental_requests.id` where `rental_requests.status='paid'`, `rental_requests.paid_at IS NOT NULL`, `transactions.status='paid'`, and `rental_requests.created_at` in period |
| Source | `rental_requests` + `transactions` |
| Output | Numeric (IDR) |
| Notes | Includes `initial_rent` and `extension_rent` if linked to qualifying paid request |

## 9) Change Log
| Version | Date | Change | Author |
|---|---|---|---|
| v1.0 | 2026-05-02 | First filled draft for synthetic Indonesia scenario | Codex + Shofa |
| v1.1 | 2026-05-23 | Sync with latest schema and dashboard KPI logic (`layout/region` removed; add building/floor/coords/description; add revenue KPI note) | Codex + Shofa |
| v1.2 | 2026-05-26 | Remove stale `rental_requests.message` mapping, add legacy `queued -> pending_review` mapping note, expand import order to include contracts/extensions/conversations/messages | Codex + Shofa |
