# Chapter 3 System Analysis and Design

Chapter 3 explains the system analysis and design of the web-based house rental management system developed in this research. The chapter focuses on functional and non-functional requirements, architecture design, process diagrams, and database structure that support reliable rental operations. The design objective is to ensure controlled workflow transitions, clear role responsibilities, and consistent transaction records across the platform.

## 3.1 System Overview

The proposed system is designed to integrate the full rental lifecycle into one web platform. The lifecycle includes property publication, tenant-side search and filtering, rental request submission, agent-side review and decision, payment confirmation with due-date control, contract generation, contract extension handling, and contextual messaging between tenant and agent.

Three user roles are implemented: `admin`, `agent`, and `tenant`. The `tenant` role focuses on search, application, payment, and communication. The `agent` role handles property management and transaction decisions. The `admin` role supervises platform-level records and operational activities. Through this role separation and status-driven workflow, the system reduces process ambiguity and improves transaction traceability.

## 3.2 Functional Requirements

The system functional requirements are grouped into core modules as follows.

1. Authentication and Authorization Module
The system shall support login and role identification for all users. Access to features and data shall be restricted based on role (`admin`, `agent`, `tenant`).

2. Property Management Module
The system shall allow agents to create, edit, and manage property data and property availability status. Tenants shall be able to browse and filter listed properties.

3. Rental Request and Transaction Module
The system shall allow tenants to submit rental requests for selected properties. The system shall allow agents to approve or reject requests. The system shall enforce request status transitions and payment due-date constraints.

4. Contract and Extension Module
The system shall generate contract records after successful payment. The system shall support contract extension requests, extension approval/rejection, and extension payment deadline handling.

5. Messaging Module
The system shall support contextual messaging between tenant and agent linked to relevant rental records.

6. Administration Module
The system shall provide monitoring capability for users, properties, rental requests, transactions, and contracts.

## 3.3 Non-Functional Requirements

The main non-functional requirements are defined as follows.

1. Security
The system shall enforce role-based access control and route-level authorization. Unauthorized status transitions and cross-role actions must be prevented.

2. Reliability
The system shall maintain consistent request and transaction states through controlled status transitions and guarded update logic. Rental payment deadline checks shall be processed consistently.

3. Usability
The system interface shall provide clear navigation and role-specific features. Core flows such as search, request submission, and approval should be completed with minimal interaction steps.

4. Maintainability
The system shall use modular Laravel architecture, separating business logic from presentation components to simplify future maintenance and feature extension.

## 3.4 System Architecture

The system follows a three-layer web architecture.

1. Presentation Layer
The user interface is implemented using Blade templates. Different interfaces are served according to user role and process context.

2. Application Layer
Business processes are handled by Laravel controllers and services. Input validation, workflow checks, and role authorization are executed at this layer.

3. Data Layer
Data persistence is handled by a relational MySQL schema. Core entities include users, properties, rental requests, transactions, contracts, extensions, and messaging records.

This layered architecture helps ensure separation of concerns and stable feature evolution.

## 3.5 Use Case Diagram and Explanation

**Figure 3.1 Use Case Diagram of House Rental Management System**

[Insert Figure 3.1 here]

The use case model describes the interaction between actors and core platform services. Tenant use cases include login, property search, request submission, payment confirmation, contract viewing, extension request, and messaging. Agent use cases include property management, request decision handling, payment validation, extension decision handling, and messaging response. Admin use cases include user and transaction monitoring at platform level. This model confirms that each role is assigned clear responsibilities and access boundaries.

## 3.6 Activity Diagram and Explanation

**Figure 3.2 Activity Diagram of Rental Request to Contract Flow**

[Insert Figure 3.2 here]

The main activity starts when a tenant selects a property and submits a rental request. The request is recorded with status `pending_review`. The agent then reviews the request and chooses approval or rejection. If rejected, the flow ends with status `rejected`. If approved, the system sets status `awaiting_payment` and creates `payment_due_at`. The tenant confirms payment, and the system validates due-date constraints. Valid payment changes status to `paid` and triggers contract activation. Overdue payment changes status to `cancelled_lost`. This flow prevents conflicting request outcomes and enforces transaction deadlines.

## 3.7 State Diagram and Explanation

**Figure 3.3 State Diagram of Rental Request Lifecycle**

[Insert Figure 3.3 here]

The state model formalizes allowable transitions for rental requests. Initial state is `pending_review`. From this state, the request can move to `awaiting_payment` (approve), `rejected`, `cancelled_by_tenant`, or `cancelled_by_agent`. From `awaiting_payment`, the request can move to `paid` (payment confirmed), `cancelled_lost` (payment overdue), or cancellation states. These terminal states ensure each request ends with a single, auditable outcome and reduce ambiguity in operational decisions.

## 3.8 Database Design

The database is designed using relational modeling to support data consistency and traceability. Core entities are:

1. `users`
2. `properties`
3. `rental_requests`
4. `transactions`
5. `contracts`
6. `contract_extensions`
7. `property_availability_cycles`
8. `conversations`
9. `conversation_messages`
10. `facilities`
11. `property_facility`

Each entity is connected through foreign-key constraints to maintain referential integrity across the rental lifecycle.

## 3.9 Entity Relationship Diagram (ERD)

**Figure 3.4 Entity Relationship Diagram of House Rental Management Database**

[Insert Figure 3.4 here]

The ERD shows the primary relationships between entities. One agent can manage many properties, and one tenant can create many rental requests. One property can receive multiple requests over time and across availability cycles. One rental request may produce one contract and one or more transactions. One contract can have multiple extension records. Messaging is linked through conversation and message entities to preserve communication context. Facility assignment uses a many-to-many pattern via `property_facility`.

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
