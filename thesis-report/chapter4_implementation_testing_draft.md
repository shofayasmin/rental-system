# Chapter 4 Implementation and Testing

## 4.1 Development Environment
The system was implemented as a web-based application using Laravel and MySQL. Development and testing were carried out in a local environment with role-based user accounts (`admin`, `agent`, and `tenant`). The implementation scope includes modules for authentication, property management, rental request processing, payment confirmation, contract handling, contract extension, and contextual messaging.

## 4.2 Homepage and Navigation Module
The homepage provides entry points for user authentication and role-based navigation. For tenants, the interface emphasizes property discovery and rental request initiation. For agents and admins, the interface prioritizes management and monitoring actions.

Implementation highlights:
1. Public-facing property browsing and filtering.
2. Role-aware menu rendering after login.
3. Consistent navigation paths to core workflow modules.

## 4.3 Authentication and Authorization Module
Authentication is implemented using Laravel session-based login. Authorization is enforced through role checks at route/controller level.

Implementation highlights:
1. Credential validation and authenticated sessions.
2. Role-based route access protection.
3. Unauthorized access handling via redirect or access denial.

## 4.4 Property Management Module
This module allows agents to maintain property records and availability status.

Implementation highlights:
1. Create, update, and view property listing data.
2. Availability control to prevent listing ambiguity.
3. Property details presented for tenant decision support.

## 4.5 Rental Request and Transaction Module
This module handles the core rental workflow from request submission to decision and payment status.

Implementation highlights:
1. Tenant request submission linked to selected property.
2. Agent approval/rejection decisions.
3. Status-controlled transitions for request lifecycle.
4. Payment window control using due-date constraints.

## 4.6 Contract and Extension Module
After successful transaction progression, contract records are generated and managed.

Implementation highlights:
1. Contract generation after completed payment stage.
2. Contract extension request and approval flow.
3. Extension lifecycle tracking with status updates.

## 4.7 Messaging Module
The messaging module supports contextual communication between tenant and agent.

Implementation highlights:
1. Conversation linkage to related transaction context.
2. Bidirectional communication records.
3. Message history for interaction traceability.

## 4.8 Admin Monitoring Module
Admins monitor cross-module operations and data consistency.

Implementation highlights:
1. User and role monitoring.
2. Property, request, payment, and contract tracking.
3. Administrative visibility over platform-wide activity.

## 4.9 System Testing
Testing is performed using black-box functional testing, scenario-based workflow testing, and role/access testing.

### 4.9.1 Functional Black-Box Testing
Functional tests validate whether each feature behaves according to expected requirements.

| Test ID | Module | Test Scenario | Expected Result | Actual Result | Status |
|---|---|---|---|---|---|
| F-01 | Authentication | Valid login with tenant account | Tenant dashboard is displayed | Pass | Pass |
| F-02 | Authentication | Invalid login credentials | Login rejected with validation message | Pass | Pass |
| F-03 | Property | Agent creates new property listing | Property is saved and visible in listing | Pass | Pass |
| F-04 | Rental Request | Tenant submits rental request | Request record created with initial status | Pass | Pass |
| F-05 | Request Decision | Agent approves request | Status changes to payment-waiting state | Pass | Pass |
| F-06 | Payment | Tenant confirms payment within due date | Payment recorded and status updated | Pass | Pass |
| F-07 | Contract | Contract generation after completed payment | Contract record becomes available | Pass | Pass |
| F-08 | Extension | Tenant submits extension request | Extension request stored with pending status | Pass | Pass |
| F-09 | Messaging | Tenant sends message to agent | Message stored and visible to agent | Pass | Pass |
| F-10 | Admin | Admin accesses monitoring data | Cross-module data is visible | Pass | Pass |

### 4.9.2 Scenario-Based End-to-End Testing
Scenario tests validate complete workflow consistency across multiple modules.

| Scenario ID | End-to-End Flow | Expected Outcome | Actual Outcome | Status |
|---|---|---|---|---|
| S-01 | Tenant searches property → submits request → agent approves → tenant pays on time → contract generated | Workflow completes without state conflict | Aligned | Pass |
| S-02 | Tenant submits request → agent rejects | Request ends in rejected state, no payment flow opened | Aligned | Pass |
| S-03 | Tenant approved but misses payment due date | Request/payment window expires according to due-date rule | Aligned | Pass |
| S-04 | Tenant submits extension → agent approves → extension payment/validation completes | Extension workflow recorded and status updated | Aligned | Pass |

### 4.9.3 Role and Access Control Testing
Role tests verify permission boundaries and unauthorized action prevention.

| Access Test ID | Role | Attempted Action | Expected Result | Actual Result | Status |
|---|---|---|---|---|---|
| A-01 | Tenant | Access admin-only page | Access denied/redirected | Denied | Pass |
| A-02 | Tenant | Edit agent-owned property | Access denied | Denied | Pass |
| A-03 | Agent | Access unrelated admin configuration page | Access denied | Denied | Pass |
| A-04 | Admin | Access all monitoring modules | Full access granted | Granted | Pass |

### 4.9.4 Testing Summary
Based on the executed functional, scenario-based, and role-access tests, the implemented system satisfies the targeted operational flow and access governance requirements. The test outcomes indicate that core modules operate consistently and that role boundaries are effectively enforced during rental transactions.

## References (Chapter 4 Draft)

Le, H. T., Shar, L. K., Bianculli, D., Briand, L. C., & Nguyen, C. D. (2022). Automated reverse engineering of role-based access control policies of web applications. *Journal of Systems and Software, 184*, 111109. https://doi.org/10.1016/j.jss.2021.111109

Setiawan, A. B., Yuniar, E., Hermansyah, M., Mujiono, M., & Ariyadi, D. J. (2026). Decision support system for selecting the best rental house with Weight Product in Sidokare District, Sidoarjo Regency. *G-Tech: Jurnal Teknologi Terapan, 10*(1), 536-548. https://doi.org/10.70609/g-tech.v10i1.8865

Wang, Y., Livingston, M., McArthur, D. P., & Bailey, N. (2024). Enhancing our understanding of short-term rental activity: A daily scrape-based approach for Airbnb listings. *PLOS ONE, 19*(2), e0298131. https://doi.org/10.1371/journal.pone.0298131
