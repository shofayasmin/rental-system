# Demo Readiness Pack (For Supervisor Progress Check)

## 1) Tujuan (Why this is needed)
Supervisor request focuses on **evidence of runnable implementation**, not only feature claims.  
So this pack provides:
- Current runnable UI slices
- Video demonstration flow
- Clear separation: `implemented now` vs `next step`
- Concrete result that will be shown

---

## 2) Runnable UI Slices to Capture

Use these as screenshot slices in progress slides/report.

| No | UI Slice | URL | Account | What to show |
|---|---|---|---|---|
| 1 | Login | `/login` | Admin/Agent/Tenant | Role-based redirect after login |
| 2 | Register | `/register` | Guest | Tenant self-registration form |
| 3 | Admin - User Management | `/admin/users` | Admin | Create/Edit/Enable/Disable user |
| 4 | Admin - Transactions | `/admin/transactions` | Admin | Search by ID/date/tenant/property/agent |
| 5 | Admin - Login Audit | `/admin/login-audit` | Admin | Login history table |
| 6 | Agent - My Properties | `/agent/properties` | Agent | Property CRUD + status |
| 7 | Agent - Property Photos | `/agent/properties/{id}/photos` | Agent | Upload/delete photo |
| 8 | Tenant - Browse Houses | `/` or `/houses` | Tenant/Guest | Filter by region/price/layout |
| 9 | Tenant - Property Detail | `/houses/{id}` | Tenant | Apply rent + chat from property |
| 10 | Agent - Rental Requests | `/agent/rental-requests` | Agent | Approve/Reject/Cancel Lock |
| 11 | Tenant - My Requests | `/tenant/requests` | Tenant | Track status + pay + cancel |
| 12 | Tenant - Contracts/Extensions | `/tenant/contracts` + `/tenant/contracts/extensions` | Tenant | View/print contract + extension flow |
| 13 | Chat List | `/messages` | Tenant/Agent | Conversation list |
| 14 | Chat Room | `/messages/{requestId}` or `/messages/conversations/{id}` | Tenant/Agent | Message exchange with context |
| 15 | Profile | `/profile` | Any logged-in user | Update profile + password |

---

## 3) Demo Video Script (8-12 minutes)

## 3.1 Opening (30-45 sec)
Narration:
"This system is a web-based house rental management platform with three roles: admin, agent, and tenant.  
Today I will demonstrate implemented functions that are already runnable."

## 3.2 Tenant Journey (3-4 min)
1. Login as tenant.
2. Browse/filter properties.
3. Open property detail, show status and photos.
4. Submit rental request.
5. Open `My Requests` and show status `pending_review`.
6. Open chat with agent from request.

Narration:
"Tenant can discover houses, submit rental request, track progress, and communicate with agent."

## 3.3 Agent Journey (2-3 min)
1. Login as agent.
2. Open `My Properties`, show property CRUD and status.
3. Open photo management and upload/delete photo.
4. Open `Rental Requests`.
5. Approve one request (moves to `awaiting_payment`) and optionally reject another.
6. Show cancel lock button.

Narration:
"Agent controls listing lifecycle and handles request decision workflow including payment-stage lock."

## 3.4 Payment + Contract Journey (1.5-2.5 min)
1. Back to tenant account.
2. Open request in `awaiting_payment`, click pay.
3. Show status changes to `paid`.
4. Open contract detail page and print preview.
5. Submit extension request.
6. Agent approves extension, tenant pays extension, show extension history.

Narration:
"After payment, contract is generated. Contract extension is supported with approval and payment flow."

## 3.5 Admin Monitoring Journey (1.5-2 min)
1. Login as admin.
2. Show user management list and toggle active/disabled.
3. Show transaction search page.
4. Show login audit records.

Narration:
"Admin can supervise operations, monitor transactions, and audit account access."

## 3.6 Closing (20-30 sec)
Narration:
"This demo shows completed and runnable core modules. Next work focuses on analytics/reporting and stronger automated test coverage."

---

## 4) “Done vs Next” Section (Use this wording in report/slide)

## 4.1 Implemented Functions (Done)
- Role-based access control for `admin`, `agent`, `tenant`
- Authentication and account status validation
- Admin account management (create, edit, enable/disable)
- Agent property management (CRUD, status update, photo management)
- Tenant browsing and rental request flow
- Agent decision flow (approve/reject/cancel payment lock)
- Internal payment status flow for initial rent and extension rent
- Contract creation, viewing/printing, and extension workflow
- Tenant-agent messaging (conversation/chat)
- Admin transaction search and login audit
- Scheduled expiration for overdue payment-stage requests

## 4.2 Next Step (In Progress)
- Advanced analytics/reporting module (dashboard-level summaries and trend metrics)
- Extended automated tests for end-to-end critical workflows
- Demo data polishing for clearer business storytelling

## 4.3 Expected Result to Show Next
- Analytics screens with role-relevant KPIs
- Measurable workflow insights (conversion, request outcome, payment completion rate)
- More stable release confidence backed by test results

---

## 5) Recording Checklist (Practical)
- Keep browser zoom at 100%.
- Use one clean dataset and fixed demo accounts.
- Prepare 3 tabs in advance: tenant, agent, admin.
- Avoid dead time: pre-open URLs.
- Record at 1080p; use cursor highlight if available.
- Keep total video under 12 minutes.

---

## 6) Suggested Demo Accounts
- Admin: `admin@test.com` / `password`
- Agent: `agent1@test.com` / `password`
- Tenant: `tenant1@test.com` / `password`

(Based on current seeder configuration.)

---

## 7) Ready-to-Paste Progress Paragraph (English)

"At the current stage, the system core workflows are already implemented and runnable through web UI pages.  
The completed scope includes role-based access control, account management, property lifecycle management, tenant rental request flow, agent review decisions, payment status processing, contract generation and extension handling, user messaging, and admin monitoring features.  
For next steps, the project will focus on advanced analytics/reporting and broader automated test coverage to improve decision-support visibility and release reliability.  
In the next demonstration, I will present KPI-oriented analytics views and measurable workflow outcomes based on transaction and request lifecycle data."
