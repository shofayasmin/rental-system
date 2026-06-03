# Chapter 4 Test Case Template (Fill with Real Results)

## Functional Black-Box Testing

| Test ID | Module | Test Scenario | Input/Test Data | Expected Result | Actual Result | Status |
|---|---|---|---|---|---|---|
| F-01 | Authentication | Valid login with tenant account | tenant credential | Tenant dashboard displayed |  |  |
| F-02 | Authentication | Invalid login credentials | invalid credential | Validation error shown |  |  |
| F-03 | Property | Agent creates new property listing | valid property form | Data saved and listed |  |  |
| F-04 | Rental Request | Tenant submits rental request | selected property + request form | Request created with initial status |  |  |
| F-05 | Request Decision | Agent approves request | pending request | Status changes to payment-waiting |  |  |
| F-06 | Payment | Tenant confirms payment before due date | payment proof/data | Payment recorded; status updated |  |  |
| F-07 | Contract | Generate contract after payment | paid request | Contract available |  |  |
| F-08 | Extension | Tenant submits extension request | active contract + extension form | Extension request created |  |  |
| F-09 | Messaging | Tenant sends message to agent | message content | Message stored and shown |  |  |
| F-10 | Admin | Admin opens monitoring module | admin session | Monitoring data visible |  |  |

## Scenario-Based End-to-End Testing

| Scenario ID | End-to-End Flow | Expected Outcome | Actual Outcome | Status |
|---|---|---|---|---|
| S-01 | Search → request → approve → pay on time → contract generated | Flow completes consistently |  |  |
| S-02 | Request → reject | Rejected state finalization works |  |  |
| S-03 | Approved request but payment overdue | Due-date expiration triggered correctly |  |  |
| S-04 | Extension request → approval → extension completion | Extension lifecycle recorded correctly |  |  |

## Role and Access Control Testing

| Access Test ID | Role | Attempted Action | Expected Result | Actual Result | Status |
|---|---|---|---|---|---|
| A-01 | Tenant | Access admin-only page | Access denied/redirect |  |  |
| A-02 | Tenant | Edit agent-owned property | Access denied |  |  |
| A-03 | Agent | Access admin configuration area | Access denied |  |  |
| A-04 | Admin | Access all monitoring features | Access granted |  |  |
