## 3.10 Database Table Structures

This section presents the physical database structures used in the implementation of the House Rental Management System. Each table is described by field name, data type, attributes, nullability, default value, and business description.


### Tab.3.1 User Table (`users`)

| Field | Type | Attributes | Null | Default | Description |
|---|---|---|---|---|---|
| id | bigint unsigned | Primary Key, Auto Increment | No | - | User ID |
| name | varchar(255) | - | No | - | Full name |
| email | varchar(255) | Unique | No | - | Email address |
| email_verified_at | timestamp | - | Yes | NULL | Email verification time |
| password | varchar(255) | - | No | - | Encrypted password |
| role | enum('admin','agent','tenant') | - | No | tenant | User role |
| phone | varchar(255) | - | Yes | NULL | Phone number |
| enabled | tinyint(1) | - | No | 1 | Account status flag |
| remember_token | varchar(100) | - | Yes | NULL | Remember-me token |
| created_at | timestamp | - | Yes | NULL | Created time |
| updated_at | timestamp | - | Yes | NULL | Last updated time |


### Tab.3.2 Property Table (`properties`)

| Field | Type | Attributes | Null | Default | Description |
|---|---|---|---|---|---|
| id | bigint unsigned | Primary Key, Auto Increment | No | - | Property ID |
| agent_id | bigint unsigned | Foreign Key -> users.id | No | - | Property owner/agent |
| title | varchar(255) | - | No | - | Property title |
| province_id | bigint unsigned | Foreign Key -> provinces.id | Yes | NULL | Province reference |
| regency_id | bigint unsigned | Foreign Key -> regencies.id | Yes | NULL | Regency/city reference |
| district_id | bigint unsigned | Foreign Key -> districts.id | Yes | NULL | District reference |
| village_id | bigint unsigned | Foreign Key -> villages.id | Yes | NULL | Village reference |
| address | varchar(255) | - | Yes | NULL | Detailed address |
| description | text | - | Yes | NULL | Property description |
| latitude | decimal(10,7) | - | Yes | NULL | Latitude coordinate |
| longitude | decimal(10,7) | - | Yes | NULL | Longitude coordinate |
| bedrooms | tinyint unsigned | Indexed | No | 0 | Number of bedrooms |
| bathrooms | decimal(3,1) | Indexed | No | 1.0 | Number of bathrooms |
| floors | tinyint unsigned | - | Yes | NULL | Number of floors |
| area | float | - | Yes | NULL | Land area |
| building_area | decimal(10,2) | - | Yes | NULL | Building area |
| facilities | json | Legacy field | Yes | NULL | Legacy facilities payload |
| rent_price | decimal(12,2) | Indexed | No | - | Rental price |
| status | enum('to-let','rented','maintenance') | Indexed | No | to-let | Availability status |
| created_at | timestamp | - | Yes | NULL | Created time |
| updated_at | timestamp | - | Yes | NULL | Last updated time |

Additional indexes:
- composite index: (`province_id`, `regency_id`, `district_id`, `village_id`) as `properties_location_hierarchy_idx`.


### Tab.3.3 Rental Request Table (`rental_requests`)

| Field | Type | Attributes | Null | Default | Description |
|---|---|---|---|---|---|
| id | bigint unsigned | Primary Key, Auto Increment | No | - | Rental request ID |
| property_id | bigint unsigned | Foreign Key -> properties.id | No | - | Requested property |
| availability_cycle_id | bigint unsigned | Foreign Key -> property_availability_cycles.id | Yes | NULL | Bound availability cycle |
| tenant_id | bigint unsigned | Foreign Key -> users.id | No | - | Requesting tenant |
| status | enum('pending_review','awaiting_payment','paid','rejected','cancelled_by_tenant','cancelled_by_agent','cancelled_lost') | - | No | pending_review | Request lifecycle status |
| awaiting_payment_at | timestamp | - | Yes | NULL | Entered awaiting payment at |
| payment_due_at | timestamp | Indexed | Yes | NULL | Payment due deadline |
| paid_at | timestamp | - | Yes | NULL | Paid timestamp |
| rejected_at | timestamp | - | Yes | NULL | Rejected timestamp |
| cancelled_at | timestamp | - | Yes | NULL | Cancelled timestamp |
| created_at | timestamp | - | Yes | NULL | Created time |
| updated_at | timestamp | - | Yes | NULL | Last updated time |

Additional indexes:
- `rental_requests_property_cycle_idx` on (`property_id`, `availability_cycle_id`)
- `rental_requests_payment_due_at_idx` on (`payment_due_at`)


### Tab.3.4 Transaction Table (`transactions`)

| Field | Type | Attributes | Null | Default | Description |
|---|---|---|---|---|---|
| id | bigint unsigned | Primary Key, Auto Increment | No | - | Transaction ID |
| rental_request_id | bigint unsigned | Foreign Key -> rental_requests.id | No | - | Related request |
| amount | decimal(12,2) | - | No | - | Transaction amount |
| type | enum('initial_rent','extension_rent') | Unique pair with rental_request_id | No | initial_rent | Transaction type |
| status | enum('unpaid','paid','failed') | - | No | unpaid | Payment status |
| property_id | bigint unsigned | Foreign Key -> properties.id | No | - | Related property |
| tenant_id | bigint unsigned | Foreign Key -> users.id | No | - | Paying tenant |
| agent_id | bigint unsigned | Foreign Key -> users.id | No | - | Receiving agent |
| contract_extension_id | bigint unsigned | Foreign Key -> contract_extensions.id | Yes | NULL | Linked extension (if any) |
| created_at | timestamp | - | Yes | NULL | Created time |
| updated_at | timestamp | - | Yes | NULL | Last updated time |

Additional constraints:
- unique: (`rental_request_id`, `type`) as `transactions_request_type_unique`.


### Tab.3.5 Contract Table (`contracts`)

| Field | Type | Attributes | Null | Default | Description |
|---|---|---|---|---|---|
| id | bigint unsigned | Primary Key, Auto Increment | No | - | Contract ID |
| rental_request_id | bigint unsigned | Foreign Key -> rental_requests.id, Unique | No | - | Source request |
| start_date | date | - | No | - | Contract start date |
| end_date | date | - | No | - | Contract end date |
| total_price | decimal(12,2) | - | No | - | Total contract value |
| monthly_rent | decimal(12,2) | - | Yes | NULL | Monthly rent snapshot |
| status | enum('active','ended') | - | No | active | Contract status |
| ended_reason | text | - | Yes | NULL | End reason |
| ended_by | bigint unsigned | Foreign Key -> users.id | Yes | NULL | User who ended contract |
| ended_at | timestamp | - | Yes | NULL | Contract end timestamp |
| created_at | timestamp | - | Yes | NULL | Created time |
| updated_at | timestamp | - | Yes | NULL | Last updated time |

Additional constraints:
- unique: (`rental_request_id`) as `contracts_rental_request_unique`.


### Tab.3.6 Contract Extension Table (`contract_extensions`)

| Field | Type | Attributes | Null | Default | Description |
|---|---|---|---|---|---|
| id | bigint unsigned | Primary Key, Auto Increment | No | - | Extension ID |
| contract_id | bigint unsigned | Foreign Key -> contracts.id | No | - | Parent contract |
| old_end_date | date | - | No | - | Original end date |
| new_end_date | date | - | No | - | Proposed end date |
| extended_at | timestamp | - | No | - | Extension created timestamp |
| months_requested | int unsigned | - | No | 1 | Number of months requested |
| monthly_rent_snapshot | decimal(12,2) | - | Yes | NULL | Rent snapshot at request time |
| amount | decimal(12,2) | - | Yes | NULL | Computed extension amount |
| status | enum('pending','awaiting_payment','paid','rejected','cancelled_by_tenant','expired') | - | No | pending | Extension status |
| approved_by | bigint unsigned | Foreign Key -> users.id | Yes | NULL | Approving agent/admin |
| approved_at | timestamp | - | Yes | NULL | Approval timestamp |
| payment_due_at | timestamp | Indexed | Yes | NULL | Extension payment due |
| rejected_at | timestamp | - | Yes | NULL | Rejection timestamp |
| cancelled_at | timestamp | - | Yes | NULL | Tenant cancellation timestamp |
| paid_at | timestamp | - | Yes | NULL | Paid timestamp |
| created_at | timestamp | - | Yes | NULL | Created time |
| updated_at | timestamp | - | Yes | NULL | Last updated time |

Additional indexes:
- `contract_extensions_payment_due_at_idx` on (`payment_due_at`).


## 3.10.1 Core Entity Descriptions

The core entities that represent the main business flow of this system are `users`, `properties`, `rental_requests`, `transactions`, `contracts`, and `contract_extensions`. These tables handle identity and role management, property publication, request lifecycle control, payment transactions, contract generation, and contract extension workflow.
