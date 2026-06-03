# Chapter 3 Formal Diagram Blueprint (UML + ERD)

Use this blueprint to redraw the diagrams in Word, Visio, draw.io, or StarUML with standard notation.

---

## 3.5 Use Case Diagram (UML)

System boundary: **House Rental Management System**

Actors:
- `Tenant`
- `Agent`
- `Admin`

Use cases:
- `Login`
- `Browse Properties`
- `Filter/Search Property`
- `Submit Rental Request`
- `Cancel Rental Request`
- `Confirm Payment`
- `View Contract`
- `Request Contract Extension`
- `Send Contextual Message`
- `Manage Properties`
- `Review Request`
- `Approve Request`
- `Reject Request`
- `Validate Payment`
- `Approve Extension`
- `Reject Extension`
- `Monitor Users`
- `Monitor Transactions`
- `Monitor Contracts`

Associations:
- `Tenant` -> Login, Browse Properties, Filter/Search Property, Submit Rental Request, Cancel Rental Request, Confirm Payment, View Contract, Request Contract Extension, Send Contextual Message
- `Agent` -> Login, Manage Properties, Review Request, Approve Request, Reject Request, Validate Payment, Approve Extension, Reject Extension, Send Contextual Message
- `Admin` -> Login, Monitor Users, Monitor Transactions, Monitor Contracts

Recommended UML include/extend:
- `Submit Rental Request` <<include>> `Browse Properties`
- `Submit Rental Request` <<include>> `Filter/Search Property`
- `Review Request` <<extend>> `Approve Request`
- `Review Request` <<extend>> `Reject Request`

Caption suggestion:
- **Figure 3.x Use Case Diagram of House Rental Management System**

---

## 3.6 Activity Diagram (UML) - Main Rental Flow

Swimlanes:
- `Tenant`
- `System`
- `Agent`

Flow:
1. Tenant selects property.
2. Tenant submits rental request.
3. System stores request with status `pending_review`.
4. Agent reviews request.
5. Decision node:
   - Reject -> System sets `rejected` -> End.
   - Approve -> System sets `awaiting_payment`, sets `payment_due_at`.
6. Tenant performs payment confirmation.
7. Decision node:
   - Payment after due date -> System sets `cancelled_lost` -> End.
   - Payment valid -> System sets `paid`.
8. System creates/activates contract.
9. End.

Add note in diagram:
- Guard: `[now <= payment_due_at]` for successful payment path.

Caption suggestion:
- **Figure 3.x Activity Diagram of Rental Request to Contract Flow**

---

## 3.6 (Alternative/Extra) State Diagram - Rental Request Status

States:
- `pending_review`
- `awaiting_payment`
- `paid`
- `rejected`
- `cancelled_by_tenant`
- `cancelled_by_agent`
- `cancelled_lost`

Transitions:
- Start -> `pending_review`
- `pending_review` --approve--> `awaiting_payment`
- `pending_review` --reject--> `rejected`
- `pending_review` --cancel_tenant--> `cancelled_by_tenant`
- `pending_review` --cancel_agent--> `cancelled_by_agent`
- `awaiting_payment` --payment_confirmed--> `paid`
- `awaiting_payment` --due_expired--> `cancelled_lost`
- `awaiting_payment` --cancel_tenant--> `cancelled_by_tenant`
- `awaiting_payment` --cancel_agent--> `cancelled_by_agent`

Final states:
- `paid`, `rejected`, `cancelled_by_tenant`, `cancelled_by_agent`, `cancelled_lost`

Caption suggestion:
- **Figure 3.x State Diagram of Rental Request Status Lifecycle**

---

## 3.8 Database Relationship Diagram (ERD)

Use crow's foot notation.

### Core entities and key attributes

1. `users`
- PK: `id`
- `name`, `email`, `role` (`admin|agent|tenant`)

2. `properties`
- PK: `id`
- FK: `agent_id -> users.id`
- `title`, `rent_price`, `status`

3. `rental_requests`
- PK: `id`
- FK: `property_id -> properties.id`
- FK: `tenant_id -> users.id`
- FK: `availability_cycle_id -> property_availability_cycles.id` (nullable)
- `status`, `payment_due_at`, `paid_at`

4. `transactions`
- PK: `id`
- FK: `rental_request_id -> rental_requests.id`
- FK: `property_id -> properties.id`
- FK: `tenant_id -> users.id`
- FK: `agent_id -> users.id`
- FK: `contract_extension_id -> contract_extensions.id` (nullable)
- `type`, `amount`, `status`

5. `contracts`
- PK: `id`
- FK: `rental_request_id -> rental_requests.id`
- FK: `ended_by -> users.id` (nullable)
- `start_date`, `end_date`, `status`

6. `contract_extensions`
- PK: `id`
- FK: `contract_id -> contracts.id`
- FK: `approved_by -> users.id` (nullable)
- `status`, `payment_due_at`, `paid_at`

7. `property_availability_cycles`
- PK: `id`
- FK: `property_id -> properties.id`
- `available_from_at`, `unavailable_at`, `closed_by`

8. `conversations`
- PK: `id`
- FK: `tenant_id -> users.id`
- FK: `agent_id -> users.id`
- FK: `property_id -> properties.id` (nullable)
- `status`

9. `conversation_messages`
- PK: `id`
- FK: `conversation_id -> conversations.id`
- FK: `sender_id -> users.id`
- FK: `rental_request_id -> rental_requests.id` (nullable)
- `message`

10. `facilities`
- PK: `id`
- `slug`, `name`, `category`

11. `property_facility`
- PK: `id`
- FK: `property_id -> properties.id`
- FK: `facility_id -> facilities.id`
- `value`

### Relationship cardinality (draw this exactly)

- `users (agent) 1 ----< properties`
- `users (tenant) 1 ----< rental_requests`
- `properties 1 ----< rental_requests`
- `properties 1 ----< property_availability_cycles`
- `property_availability_cycles 1 ----< rental_requests` (nullable on request side)
- `rental_requests 1 ---- 0..1 contracts`
- `rental_requests 1 ----< transactions`
- `contracts 1 ----< contract_extensions`
- `contract_extensions 1 ----< transactions` (only for extension rent, nullable link)
- `users 1 ----< conversations` (as tenant)
- `users 1 ----< conversations` (as agent)
- `conversations 1 ----< conversation_messages`
- `users 1 ----< conversation_messages` (as sender)
- `properties 1 ----< conversations` (nullable property in conversation)
- `rental_requests 1 ----< conversation_messages` (nullable context)
- `properties >----< facilities` via `property_facility`

Caption suggestion:
- **Figure 3.x Entity Relationship Diagram of House Rental Management Database**

---

## Quick checklist before finalizing diagrams

- Use consistent names with the chapter text (`pending_review`, `awaiting_payment`, etc.).
- Do not mix old table `messages` (dropped) with `conversation_messages` (active).
- Keep actor names and table names exactly as implemented in the project.

