# Chapter 3 System Analysis and Design

Chapter 3 presents the system analysis and design for the proposed web-based house rental management system. This chapter describes the system requirements, architecture, process modeling, and database structure that guide the implementation. It also explains the design rationale for role governance, workflow control, and transaction data consistency.

## 3.1 System Overview
The system is designed to integrate property listing, tenant-side search and request submission, approval workflow, payment confirmation, contract generation, contract extension, and contextual messaging in one platform. The system serves three primary actors: admin, agent, and tenant.

## 3.2 Functional Requirements
The functional requirements are grouped into six core modules: authentication and authorization, property management, rental request and transaction, contract and extension, messaging, and administration.

## 3.3 Non-Functional Requirements
Security is implemented through role-based access and guarded status transitions. Reliability is supported by consistent transaction logic and relational persistence. Usability is addressed through role-specific interfaces, and maintainability is supported by modular Laravel architecture.

## 3.4 System Architecture
The system follows a three-layer web architecture: presentation layer (Blade interface), application layer (Laravel controllers, services, and validation), and data layer (MySQL relational schema).

## 3.5 Use Case Diagram

**Fig. 3.1 Use Case Diagram (Admin, Agent, Tenant)**

[Insert Figure 3.1 here]

Use case scope:
- Tenant: browse properties, search/filter listings, submit rental requests, confirm payment, view contracts, request extensions, and send contextual messages.
- Agent: manage properties, review rental requests, approve or reject requests, validate payment-related flow, handle extension requests, and reply to messages.
- Admin: monitor users, transactions, and platform-wide operational records.

## 3.6 Activity Diagram

**Fig. 3.2 Main Rental Workflow Activity Diagram**

[Insert Figure 3.2 here]

Main flow:
1. Tenant browses and selects a property.
2. Tenant submits a rental request.
3. System stores the request with `pending_review` status.
4. Agent reviews the request.
5. If rejected, the system sets `rejected` and ends the flow.
6. If approved, the system sets `awaiting_payment` and creates the payment due-date.
7. Tenant confirms payment before the due date.
8. If payment is valid, the system sets `paid` and generates the contract.
9. Optional contract extension follows the same approval and validation cycle.

## 3.7 Status Transition Diagram

**Fig. 3.3 Rental Request Status Transition Diagram**

[Insert Figure 3.3 here]

Primary statuses:
- `pending_review`
- `awaiting_payment`
- `paid`
- `rejected`
- `cancelled_by_tenant`
- `cancelled_by_agent`
- `cancelled_lost`

The state model ensures that each rental request moves through a single auditable lifecycle and prevents conflicting updates during transaction processing.

## 3.8 Entity Relationship Diagram (ERD)

**Fig. 3.4 Conceptual ERD of House Rental Management System**

[Insert Figure 3.4 here]

Core entities include `users`, `properties`, `rental_requests`, `payments`, `contracts`, and `messages` with traceable one-to-many and one-to-one relationships. Supporting entities such as `property_photos`, `contract_extensions`, `property_availability_cycles`, `facilities`, and `property_facility` are used to preserve listing detail, availability history, and many-to-many property-facility mapping.

## 3.9 Database Table Structures

**Tab. 3.1 Core Database Tables Summary**

Recommended table presentation format:
- Table name
- Field name
- Data type
- Nullability
- PK/FK
- Description

### 3.9.1 Core Entity Descriptions
Prioritize detailed tables for:
1. `users`
2. `properties`
3. `rental_requests`
4. `payments`
5. `contracts`
6. `messages`

## 3.10 Design Rationale

The design prioritizes workflow control, role governance, and data readiness for analytics. This approach reduces ambiguity in rental processing and improves transaction traceability.
