# Chapter 3 System Analysis and Design

Chapter 3 presents the system analysis and design for the proposed web-based house rental management system. This chapter describes the system requirements, architecture, process modeling, and database structure that guide the implementation. It also explains the design rationale for role governance, workflow control, and transaction data consistency.

## 3.1 System Overview

The system is designed to integrate property listing, tenant-side search and request submission, approval workflow, payment confirmation, contract generation, contract extension, and contextual messaging in one platform. The system serves three primary actors: `admin`, `agent`, and `tenant`.

## 3.2 Functional Requirements

The functional requirements are grouped into six core modules:

1. Authentication and authorization module: user login, role identification, and role-restricted access.
2. Property management module: agents create, update, and manage property listings and availability; tenants browse and filter properties.
3. Rental request and transaction module: tenants submit requests; agents approve or reject requests; the system enforces status transitions and due-date payment constraints.
4. Contract and extension module: contract generation after payment completion; extension request and approval flow.
5. Messaging module: contextual tenant-agent communication linked to related records.
6. Administration module: admin-level monitoring across users, properties, requests, payments, and contracts.

## 3.3 Non-Functional Requirements

Security is implemented through role-based access and guarded status transitions. Reliability is supported by consistent transaction logic and relational persistence. Usability is addressed through role-specific interfaces, and maintainability is supported by modular Laravel architecture.

## 3.4 System Architecture

The system follows a three-layer web architecture: presentation layer (Blade interface), application layer (Laravel controllers, services, and validation), and data layer (MySQL relational schema). This structure supports clear separation of responsibilities and stable workflow control during implementation.

## 3.5 Use Case Diagram

**Fig. 3.1 Use Case Diagram (Admin, Agent, Tenant)**

[Insert Figure 3.1 here]

Use case scope:
- `Tenant`: browse properties, search/filter listings, submit rental requests, confirm payment, view contracts, request extensions, and send contextual messages.
- `Agent`: manage properties, review rental requests, approve or reject requests, validate payment-related flow, handle extension requests, and reply to messages.
- `Admin`: monitor users, transactions, and platform-wide operational records.

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

## 3.8 Entity Relationship Diagram

**Fig. 3.4 Conceptual ERD of House Rental Management System**

[Insert Figure 3.4 here]

Core entities include `users`, `properties`, `rental_requests`, `payments`, `contracts`, and `messages` with traceable one-to-many and one-to-one relationships. Supporting entities such as `property_photos`, `contract_extensions`, `property_availability_cycles`, `facilities`, and `property_facility` are used to preserve listing detail, availability history, and many-to-many property-facility mapping.

## 3.9 Database Table Structures

**Tab. 3.1 Core Database Tables Summary**

| Table Name | Primary Purpose | Key Fields | Primary Key | Foreign Keys |
|---|---|---|---|---|
| `users` | Store user identity and role | `name`, `email`, `password`, `role` | `id` | - |
| `properties` | Store rental listing data | `title`, `price`, `location`, `status`, `agent_id` | `id` | `agent_id -> users.id` |
| `rental_requests` | Track rental application lifecycle | `tenant_id`, `property_id`, `status`, `payment_due_at` | `id` | `tenant_id -> users.id`; `property_id -> properties.id` |
| `payments` | Record payment confirmation | `rental_request_id`, `amount`, `paid_at`, `proof` | `id` | `rental_request_id -> rental_requests.id` |
| `contracts` | Store generated contract records | `rental_request_id`, `contract_no`, `start_date`, `end_date` | `id` | `rental_request_id -> rental_requests.id` |
| `messages` | Store contextual communication | `sender_id`, `receiver_id`, `rental_request_id`, `content` | `id` | `sender_id -> users.id`; `receiver_id -> users.id`; `rental_request_id -> rental_requests.id` |

## 3.10 Design Rationale

The design prioritizes workflow control, role governance, and data readiness for analytics. This approach reduces ambiguity in rental processing and improves transaction traceability.

## References (Chapter 3)

Le, H. T., Shar, L. K., Bianculli, D., Briand, L. C., & Nguyen, C. D. (2022). Automated reverse engineering of role-based access control policies of web applications. *Journal of Systems and Software, 184*, 111109. https://doi.org/10.1016/j.jss.2021.111109

Obse, Z. G. (2025). Addis Ababa online home rental management system, Ethiopia. *Journal of Electrical Systems and Information Technology, 12*(1). https://doi.org/10.1186/s43067-025-00220-1

Riyanti, A., Taryana, T., Dirgantoro, G. P., & Gunawan, I. M. A. O. (2024). Development of rental application using prototyping method. *TECHNOVATE: Journal of Information Technology and Strategic Innovation Management, 1*(2), 69-80. https://doi.org/10.52432/technovate.1.2.2024.69-80

Setiawan, A. B., Yuniar, E., Hermansyah, M., Mujiono, M., & Ariyadi, D. J. (2026). Decision support system for selecting the best rental house with Weight Product in Sidokare District, Sidoarjo Regency. *G-Tech: Jurnal Teknologi Terapan, 10*(1), 536-548. https://doi.org/10.70609/g-tech.v10i1.8865

Wang, Y., Livingston, M., McArthur, D. P., & Bailey, N. (2024). Enhancing our understanding of short-term rental activity: A daily scrape-based approach for Airbnb listings. *PLOS ONE, 19*(2), e0298131. https://doi.org/10.1371/journal.pone.0298131
