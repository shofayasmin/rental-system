# ERD Rental System

```mermaid
erDiagram
    USERS {
        bigint id PK
        string name
        string email UK
        enum role "admin|agent|tenant"
        string phone
        boolean enabled
    }

    LOGIN_AUDIT {
        bigint id PK
        bigint user_id FK
        string ip_address
        string user_agent
        timestamp logged_in_at
    }

    PROVINCES {
        bigint id PK
        string name
        string code UK
    }

    REGENCIES {
        bigint id PK
        bigint province_id FK
        string name
        string code UK
    }

    DISTRICTS {
        bigint id PK
        bigint regency_id FK
        string name
        string code UK
    }

    VILLAGES {
        bigint id PK
        bigint district_id FK
        string name
        string code UK
    }

    PROPERTIES {
        bigint id PK
        bigint agent_id FK
        bigint province_id FK
        bigint regency_id FK
        bigint district_id FK
        bigint village_id FK
        string title
        string address
        text description
        decimal latitude
        decimal longitude
        tinyint bedrooms
        decimal bathrooms
        tinyint floors
        float area
        decimal building_area
        json facilities_legacy
        decimal rent_price
        enum status "to-let|rented|maintenance"
    }

    PROPERTY_PHOTOS {
        bigint id PK
        bigint property_id FK
        string path
    }

    FACILITIES {
        bigint id PK
        string slug UK
        string name
        enum category "utilities|interior|outdoor"
        boolean is_active
    }

    PROPERTY_FACILITY {
        bigint id PK
        bigint property_id FK
        bigint facility_id FK
        string value
    }

    PROPERTY_AVAILABILITY_CYCLES {
        bigint id PK
        bigint property_id FK
        timestamp available_from_at
        timestamp unavailable_at
        enum closed_by "rented|maintenance|manual"
    }

    RENTAL_REQUESTS {
        bigint id PK
        bigint property_id FK
        bigint availability_cycle_id FK
        bigint tenant_id FK
        enum status "pending_review|awaiting_payment|paid|rejected|cancelled_by_tenant|cancelled_by_agent|cancelled_lost"
        timestamp awaiting_payment_at
        timestamp payment_due_at
        timestamp paid_at
        timestamp rejected_at
        timestamp cancelled_at
    }

    TRANSACTIONS {
        bigint id PK
        bigint rental_request_id FK
        bigint property_id FK
        bigint tenant_id FK
        bigint agent_id FK
        bigint contract_extension_id FK
        decimal amount
        enum type "initial_rent|extension_rent"
        enum status "unpaid|paid|failed"
    }

    CONTRACTS {
        bigint id PK
        bigint rental_request_id FK UK
        date start_date
        date end_date
        decimal monthly_rent
        decimal total_price
        enum status "active|ended"
        text ended_reason
        bigint ended_by FK
        timestamp ended_at
    }

    CONTRACT_EXTENSIONS {
        bigint id PK
        bigint contract_id FK
        int months_requested
        decimal monthly_rent_snapshot
        decimal amount
        enum status "pending|awaiting_payment|paid|rejected|cancelled_by_tenant|expired"
        bigint approved_by FK
        date old_end_date
        date new_end_date
        timestamp approved_at
        timestamp payment_due_at
        timestamp rejected_at
        timestamp cancelled_at
        timestamp paid_at
        timestamp extended_at
    }

    CONVERSATIONS {
        bigint id PK
        bigint property_id FK
        bigint tenant_id FK
        bigint agent_id FK
        bigint rental_request_id FK
        enum status "open|closed"
        timestamp last_message_at
    }

    CONVERSATION_MESSAGES {
        bigint id PK
        bigint conversation_id FK
        bigint sender_id FK
        bigint property_id FK
        bigint rental_request_id FK
        text message
    }

    USERS ||--o{ LOGIN_AUDIT : logs
    USERS ||--o{ PROPERTIES : owns_as_agent
    USERS ||--o{ RENTAL_REQUESTS : creates_as_tenant
    USERS ||--o{ TRANSACTIONS : pays_as_tenant
    USERS ||--o{ TRANSACTIONS : receives_as_agent
    USERS ||--o{ CONTRACTS : ends_contract
    USERS ||--o{ CONTRACT_EXTENSIONS : approves_extension
    USERS ||--o{ CONVERSATIONS : tenant_side
    USERS ||--o{ CONVERSATIONS : agent_side
    USERS ||--o{ CONVERSATION_MESSAGES : sends

    PROVINCES ||--o{ REGENCIES : has
    REGENCIES ||--o{ DISTRICTS : has
    DISTRICTS ||--o{ VILLAGES : has

    PROVINCES ||--o{ PROPERTIES : mapped
    REGENCIES ||--o{ PROPERTIES : mapped
    DISTRICTS ||--o{ PROPERTIES : mapped
    VILLAGES ||--o{ PROPERTIES : mapped

    PROPERTIES ||--o{ PROPERTY_PHOTOS : has
    PROPERTIES ||--o{ PROPERTY_AVAILABILITY_CYCLES : has
    PROPERTIES ||--o{ RENTAL_REQUESTS : receives
    PROPERTIES ||--o{ TRANSACTIONS : billed_in
    PROPERTIES ||--o{ CONVERSATIONS : context
    PROPERTIES ||--o{ CONVERSATION_MESSAGES : context

    FACILITIES ||--o{ PROPERTY_FACILITY : mapped
    PROPERTIES ||--o{ PROPERTY_FACILITY : mapped

    PROPERTY_AVAILABILITY_CYCLES ||--o{ RENTAL_REQUESTS : linked

    RENTAL_REQUESTS ||--o{ TRANSACTIONS : paid_by_type
    RENTAL_REQUESTS ||--o| CONTRACTS : generates
    RENTAL_REQUESTS ||--o| CONVERSATIONS : discussion
    RENTAL_REQUESTS ||--o{ CONVERSATION_MESSAGES : context

    CONTRACTS ||--o{ CONTRACT_EXTENSIONS : extended_by
    CONTRACT_EXTENSIONS ||--o| TRANSACTIONS : paid_with

    CONVERSATIONS ||--o{ CONVERSATION_MESSAGES : has
```

Catatan:
- `messages` table lama dan `rental_requests.message` sudah dihapus (migration `2026_05_22_000001`).
- `property_facility` punya unique `(property_id, facility_id)`.
- `contracts` punya unique `rental_request_id`.
- `transactions` punya unique `(rental_request_id, type)`.
- `conversations` punya unique `(tenant_id, agent_id)` (setelah migration `2026_05_05_090000`).
