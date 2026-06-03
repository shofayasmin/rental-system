# Slide-by-Slide Demo Deck Outline (Updated to Current Build)

## Slide 1 - Title
**Title:** Development Progress Demo: Web-Based House Rental Management System  
**Subtitle:** Runnable UI Evidence + Current Workflow Logic + Next Milestone  
**Presenter Info:** Name, Student ID, Supervisor, Date

**Presenter script (short):**  
"This demo focuses on what is already running in the current build, including status logic and operational controls."

---

## Slide 2 - Scope and Roles
**Title:** System Scope and Actors  
**Content:**
- Roles: `admin`, `agent`, `tenant`
- Main flow: discovery -> request -> review -> payment lock -> paid -> contract -> extension
- Governance: transaction monitoring, login audit, scheduled expiry

**Visual:**  
One clean workflow diagram with role swimlanes.

---

## Slide 3 - Runnable UI Coverage Map
**Title:** Current Runnable UI Slices  
**Content:**
- Auth: login/register + role-based redirect
- Tenant: houses, requests, payments, contracts, extensions, chat
- Agent: property CRUD, photos, request board, extension approvals
- Admin: users, transactions, login audit
- Shared: profile and password update

**Visual:**  
Collage with 4 to 6 screenshots representing each role.

---

## Slide 4 - Authentication and Access Guard
**Title:** Role-Based Authentication and Route Protection  
**Content:**
- Redirect after login to role dashboard (`/admin`, `/agent`, `/tenant`)
- Middleware enforcement: `auth`, `enabled`, `role:*`
- Disabled account is blocked from protected workflow

**Visual:**  
`/login` plus dashboard screenshots for each role.

---

## Slide 5 - Admin Control Panel
**Title:** Admin: User Governance  
**Content:**
- Create/edit users for `agent` and `tenant`
- Enable/disable account
- Quick operational links from admin dashboard

**Visual:**  
`/admin/users`, create form, edit form, toggle state.

---

## Slide 6 - Tenant Discovery and Apply Entry
**Title:** Tenant: Property Discovery and Detail  
**Content:**
- Browse listing from `/` and `/houses`
- Filter by location and house attributes (region/price/layout)
- Open `/houses/{id}` and start application or property-level chat

**Visual:**  
`/houses` + `/houses/{id}` with filter section and apply button.

---

## Slide 7 - Request Creation and Tracking
**Title:** Tenant: Request Starts at `pending_review`  
**Content:**
- Request submitted from property detail
- Initial status is `pending_review`
- Tenant tracks progress at `/tenant/requests` and detail page

**Visual:**  
Create action + request list/detail with `pending_review`.

---

## Slide 8 - Agent Request Board and Lock Logic
**Title:** Agent: Review Queue and Payment Lock Handling  
**Content:**
- Agent approves/rejects from `/agent/rental-requests`
- Approved request becomes `awaiting_payment`
- Only one active `awaiting_payment` lock per property at a time
- Agent can cancel active lock (`cancelled_by_agent`)

**Visual:**  
Request board showing status badges and action buttons.

---

## Slide 9 - Payment Window and Outcome States
**Title:** Payment Stage Rules and Final Outcomes  
**Content:**
- Approval sets `payment_due_at` = now + 7 days
- Tenant pays from request detail while status is `awaiting_payment`
- Outcome states include: `paid`, `rejected`, `cancelled_by_tenant`, `cancelled_by_agent`, `cancelled_lost`

**Visual:**  
Request detail page with due date, pay button, and status transitions.

---

## Slide 10 - Initial Rent Payment to Contract
**Title:** Initial Payment Converts Request to Contract  
**Content:**
- `initial_rent` transaction changes from `unpaid` to `paid`
- Rental request status changes to `paid`
- Contract is generated and printable (`/tenant/contracts/{transaction}`)
- Property status updates to `rented`

**Visual:**  
Before/after payment view + contract page.

---

## Slide 11 - Contract Extension Workflow
**Title:** Extension Request, Approval, and Payment  
**Content:**
- Tenant submits extension request (months-based)
- Agent approves/rejects extension request
- Approval generates `extension_rent` transaction with `awaiting_payment`
- Tenant payment updates contract end date and extension history

**Visual:**  
`/tenant/contracts/extensions` + agent extension section on `/agent/rental-requests`.

---

## Slide 12 - Messaging with Context
**Title:** Tenant-Agent Communication Module  
**Content:**
- Conversation list at `/messages`
- Context can be property-based or request-based
- Two-way messaging with tenant/agent authorization checks

**Visual:**  
Conversation list + one active chat room.

---

## Slide 13 - Admin Monitoring
**Title:** Operational Monitoring and Audit  
**Content:**
- Transaction search by ID/date/tenant/property/agent
- Login audit trail for account access visibility
- Supports administrative review and incident tracing

**Visual:**  
`/admin/transactions` and `/admin/login-audit`.

---

## Slide 14 - Scheduled Automation
**Title:** Overdue Payment Expiry Automation  
**Content:**
- Command: `requests:expire-payment-due`
- Scheduled every 5 minutes in `routes/console.php`
- Overdue `awaiting_payment` request -> `cancelled_lost`
- Related unpaid `initial_rent` transaction -> `failed`

**Visual:**  
Scheduler code snippet + mini flow diagram.

---

## Slide 15 - Implemented vs Next Step
**Title:** Progress Status (Current Build)  
**Content (left): Implemented**
- Role/auth/route protection
- Admin user governance + login audit
- Agent property lifecycle + photos
- Tenant request/payment/contract flow
- Extension workflow with dedicated transaction type
- Tenant-agent messaging
- Overdue-payment scheduler automation

**Content (right): Next Step**
- KPI analytics and reporting dashboard
- Wider automated test coverage for critical flows
- Demo data/story refinement for clearer performance narrative

---

## Slide 16 - Next Demonstration Target
**Title:** Planned Deliverables for Next Milestone  
**Content:**
- KPI-oriented analytics screens by role
- Workflow metrics: conversion, payment completion, and request outcomes
- Test-backed release confidence evidence

**Presenter script (short):**  
"Next milestone shifts from feature completeness to analytics visibility and measurable reliability."

---

## Slide 17 - Closing
**Title:** Conclusion  
**Content:**
- Core role-based rental workflow is runnable end-to-end
- UI evidence aligns with current business logic and status lifecycle
- Next phase focuses on analytics depth and quality assurance hardening

**Final line:**  
"Thank you. I am ready for questions and feedback."
