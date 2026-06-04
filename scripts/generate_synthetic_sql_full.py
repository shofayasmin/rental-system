#!/usr/bin/env python3
import random
from datetime import datetime, timedelta
from pathlib import Path


SEED = 20260526
random.seed(SEED)

PERIOD_START = datetime(2025, 1, 1, 0, 0, 0)
PERIOD_END = datetime(2026, 4, 30, 23, 59, 59)
TODAY = datetime(2026, 5, 26, 0, 0, 0)

TOTAL_PROPERTIES = 600
TOTAL_AGENTS = 30
TOTAL_TENANTS = 1500
TOTAL_REQUESTS = 6000

PROPERTY_STATUS_COUNTS = {
    "rented": 408,      # 68%
    "to-let": 144,      # 24%
    "maintenance": 48,  # 8%
}

REQUEST_STATUS_COUNTS = {
    "paid": 2100,                # 35%
    "pending_review": 1500,      # 25% (queued merged here)
    "awaiting_payment": 600,     # 10%
    "rejected": 900,             # 15%
    "cancelled_by_tenant": 300,  # 5%
    "cancelled_by_agent": 300,   # 5%
    "cancelled_lost": 300,       # 5%
}

CITY_SPECS = [
    {
        "name": "Jakarta",
        "count": 168,
        "province_code": "ID-JK",
        "regency_code": "ID-JK-JP",
        "district_code": "ID-JK-JP-GM",
        "village_code": "ID-JK-JP-GM-CD",
        "rent_min": 4_000_000,
        "rent_max": 12_000_000,
        "lat": -6.1754,
        "lng": 106.8272,
    },
    {
        "name": "Bandung",
        "count": 96,
        "province_code": "ID-JB",
        "regency_code": "ID-JB-BDG",
        "district_code": "ID-JB-BDG-CB",
        "village_code": "ID-JB-BDG-CB-DG",
        "rent_min": 2_500_000,
        "rent_max": 7_000_000,
        "lat": -6.9175,
        "lng": 107.6191,
    },
    {
        "name": "Surabaya",
        "count": 84,
        "province_code": "ID-JI",
        "regency_code": "ID-JI-SBY",
        "district_code": "ID-JI-SBY-TG",
        "village_code": "ID-JI-SBY-TG-KR",
        "rent_min": 2_800_000,
        "rent_max": 8_000_000,
        "lat": -7.2575,
        "lng": 112.7521,
    },
    {
        "name": "Medan",
        "count": 60,
        "province_code": "ID-SU",
        "regency_code": "ID-SU-MDN",
        "district_code": "ID-SU-MDN-MK",
        "village_code": "ID-SU-MDN-MK-KP",
        "rent_min": 2_000_000,
        "rent_max": 6_000_000,
        "lat": 3.5952,
        "lng": 98.6722,
    },
    {
        "name": "Makassar",
        "count": 48,
        "province_code": "ID-SN",
        "regency_code": "ID-SN-MKS",
        "district_code": "ID-SN-MKS-PA",
        "village_code": "ID-SN-MKS-PA-MS",
        "rent_min": 2_200_000,
        "rent_max": 6_500_000,
        "lat": -5.1477,
        "lng": 119.4327,
    },
    {
        "name": "Yogyakarta",
        "count": 48,
        "province_code": "ID-YO",
        "regency_code": "ID-YO-YGY",
        "district_code": "ID-YO-YGY-GD",
        "village_code": "ID-YO-YGY-GD-KL",
        "rent_min": 1_800_000,
        "rent_max": 5_500_000,
        "lat": -7.7956,
        "lng": 110.3695,
    },
    {
        "name": "Semarang",
        "count": 48,
        "province_code": "ID-JT",
        "regency_code": "ID-JT-SMG",
        "district_code": "ID-JT-SMG-CN",
        "village_code": "ID-JT-SMG-CN-JM",
        "rent_min": 2_000_000,
        "rent_max": 6_000_000,
        "lat": -6.9667,
        "lng": 110.4167,
    },
    {
        "name": "Denpasar",
        "count": 48,
        "province_code": "ID-BA",
        "regency_code": "ID-BA-DPS",
        "district_code": "ID-BA-DPS-DS",
        "village_code": "ID-BA-DPS-DS-SN",
        "rent_min": 3_000_000,
        "rent_max": 9_000_000,
        "lat": -8.6500,
        "lng": 115.2167,
    },
]

FACILITY_SLUGS = [
    "electricity",
    "water_supply",
    "wifi",
    "ac",
    "water_heater",
    "furnished",
    "kitchen_set",
    "wardrobe",
    "carport",
    "garden",
    "balcony",
    "backyard",
]


def dt_fmt(dt: datetime) -> str:
    return dt.strftime("%Y-%m-%d %H:%M:%S")


def d_fmt(dt: datetime) -> str:
    return dt.strftime("%Y-%m-%d")


def sql_str(text: str) -> str:
    return "'" + text.replace("\\", "\\\\").replace("'", "''") + "'"


def sql_val(value):
    if value is None:
        return "NULL"
    if isinstance(value, bool):
        return "1" if value else "0"
    if isinstance(value, int):
        return str(value)
    if isinstance(value, float):
        return ("%.7f" % value).rstrip("0").rstrip(".")
    return sql_str(str(value))


def weighted_month():
    # Seasonal peaks: Jun-Aug, Nov-Jan
    month_weights = {
        1: 1.5,
        2: 0.8,
        3: 0.9,
        4: 0.9,
        5: 1.0,
        6: 1.4,
        7: 1.5,
        8: 1.4,
        9: 1.0,
        10: 1.0,
        11: 1.4,
        12: 1.5,
    }
    months = list(range(1, 13))
    weights = [month_weights[m] for m in months]
    return random.choices(months, weights=weights, k=1)[0]


def random_request_datetime():
    while True:
        month = weighted_month()
        year = 2025 if month >= 1 else 2026
        if month <= 4:
            year = random.choices([2025, 2026], weights=[0.45, 0.55], k=1)[0]
        else:
            year = 2025

        day_max = 28 if month == 2 else 30 if month in [4, 6, 9, 11] else 31
        day = random.randint(1, day_max)
        hour = random.randint(8, 21)
        minute = random.randint(0, 59)
        second = random.randint(0, 59)
        dt = datetime(year, month, day, hour, minute, second)

        if dt < PERIOD_START or dt > PERIOD_END:
            continue

        # Weekday effect: Mon-Thu higher
        wd = dt.weekday()  # Mon=0 .. Sun=6
        if wd <= 3:
            return dt
        if random.random() < 0.55:
            return dt


def bounded(value, min_val, max_val):
    return max(min_val, min(value, max_val))


def add_months(base: datetime, months: int) -> datetime:
    y = base.year + (base.month - 1 + months) // 12
    m = (base.month - 1 + months) % 12 + 1
    d = min(base.day, 28)
    return datetime(y, m, d, base.hour, base.minute, base.second)


def chunked_insert(lines, table_name, columns, rows, chunk_size=500):
    if not rows:
        return
    col_sql = ",".join(f"`{c}`" for c in columns)
    for i in range(0, len(rows), chunk_size):
        chunk = rows[i:i + chunk_size]
        lines.append(f"INSERT INTO `{table_name}`")
        lines.append(f"({col_sql})")
        lines.append("VALUES")
        values = []
        for row in chunk:
            values.append("(" + ",".join(sql_val(v) for v in row) + ")")
        lines.append(",\n".join(values) + ";")
        lines.append("")


def main():
    if sum(c["count"] for c in CITY_SPECS) != TOTAL_PROPERTIES:
        raise ValueError("City distribution count must equal total properties.")

    if sum(REQUEST_STATUS_COUNTS.values()) != TOTAL_REQUESTS:
        raise ValueError("Request status counts must equal total requests.")

    # Users
    users = []
    agent_codes = []
    tenant_codes = []
    user_id = 9_100_000
    default_hash = "$2y$12$FBDTGSdTGYH2xQAqFL2B9edbtgr3ZrMBtxu.qPOBPYzo78jhhqzh6"

    users.append((user_id, "USR_ADM_0001", "Admin Local", "admin@local.test", default_hash, "admin", 1, "081200000001"))
    user_id += 1

    for i in range(1, TOTAL_AGENTS + 1):
        code = f"USR_AGT_{i:04d}"
        agent_codes.append(code)
        users.append((user_id, code, f"Agent {i}", f"agent{i:04d}@local.test", default_hash, "agent", 1, f"08120{i:06d}"))
        user_id += 1

    for i in range(1, TOTAL_TENANTS + 1):
        code = f"USR_TNT_{i:04d}"
        tenant_codes.append(code)
        users.append((user_id, code, f"Tenant {i}", f"tenant{i:04d}@local.test", default_hash, "tenant", 1, f"08210{i:06d}"))
        user_id += 1

    user_code_to_id = {u[1]: u[0] for u in users}

    # Properties
    properties = []
    property_list = []  # dict list for generation
    property_id = 9_200_000
    agent_ix = 0

    for city in CITY_SPECS:
        for i in range(1, city["count"] + 1):
            code = f"PRP_{city['name'][:3].upper()}_{i:04d}"
            agent_code = agent_codes[agent_ix % len(agent_codes)]
            agent_ix += 1

            # Bias toward lower-mid values to keep overall average rent realistic.
            rent_price = int(random.triangular(
                city["rent_min"],
                city["rent_max"],
                city["rent_min"] + (city["rent_max"] - city["rent_min"]) * 0.28,
            ))
            bedrooms = random.choices([1, 2, 3, 4, 5], weights=[25, 35, 24, 12, 4], k=1)[0]
            bathrooms = random.choices([1.0, 1.5, 2.0, 2.5, 3.0], weights=[35, 25, 22, 10, 8], k=1)[0]
            floors = random.choices([1, 2, 3], weights=[55, 37, 8], k=1)[0]
            area = random.randint(36, 240)
            building_area = round(area * random.uniform(0.75, 1.05), 2)
            lat = round(city["lat"] + random.uniform(-0.045, 0.045), 7)
            lng = round(city["lng"] + random.uniform(-0.045, 0.045), 7)
            title = f"{bedrooms}BR Rental in {city['name']} #{i:04d}"
            address = f"Jl. Synthetic {i:04d}, {city['name']}"
            description = f"Synthetic property data for KPI testing in {city['name']}."

            property_list.append({
                "id": property_id,
                "code": code,
                "city": city["name"],
                "agent_code": agent_code,
                "province_code": city["province_code"],
                "regency_code": city["regency_code"],
                "district_code": city["district_code"],
                "village_code": city["village_code"],
                "rent_price": rent_price,
            })

            properties.append((
                property_id, code, agent_code,
                city["province_code"], city["regency_code"], city["district_code"], city["village_code"],
                title, address, description, bedrooms, bathrooms, floors, area, building_area,
                rent_price, None, lat, lng
            ))
            property_id += 1

    # Assign property status snapshot
    idxs = list(range(len(property_list)))
    random.shuffle(idxs)
    rented_idx = set(idxs[:PROPERTY_STATUS_COUNTS["rented"]])
    tolet_idx = set(idxs[PROPERTY_STATUS_COUNTS["rented"]:PROPERTY_STATUS_COUNTS["rented"] + PROPERTY_STATUS_COUNTS["to-let"]])
    maint_idx = set(idxs[-PROPERTY_STATUS_COUNTS["maintenance"]:])

    rented_props = []
    tolet_props = []
    maint_props = []
    for i, prop in enumerate(property_list):
        if i in rented_idx:
            status = "rented"
            rented_props.append(prop)
        elif i in tolet_idx:
            status = "to-let"
            tolet_props.append(prop)
        else:
            status = "maintenance"
            maint_props.append(prop)
        prop["status"] = status

    properties = [(*row[:16], property_list[i]["status"], *row[17:]) for i, row in enumerate(properties)]

    property_code_to_prop = {p["code"]: p for p in property_list}

    # Property facilities
    property_facility = []
    pf_id = 9_300_000
    for prop in property_list:
        selected = set(["electricity", "water_supply"])
        if random.random() < 0.82:
            selected.add("wifi")
        if random.random() < 0.72:
            selected.add("ac")
        if random.random() < 0.56:
            selected.add("carport")
        if random.random() < 0.40:
            selected.add("furnished")
        if random.random() < 0.33:
            selected.add("kitchen_set")
        if random.random() < 0.25:
            selected.add("water_heater")
        if random.random() < 0.20:
            selected.add("garden")
        if random.random() < 0.15:
            selected.add("balcony")
        if random.random() < 0.12:
            selected.add("backyard")
        if random.random() < 0.18:
            selected.add("wardrobe")

        for slug in sorted(selected):
            val = None
            if slug == "electricity":
                val = random.choice(["1300 VA", "2200 VA", "3500 VA"])
            elif slug == "wifi":
                val = random.choice(["50 Mbps", "100 Mbps", "200 Mbps"])
            elif slug == "water_supply":
                val = random.choice(["PDAM", "Well"])
            property_facility.append((pf_id, prop["code"], slug, val))
            pf_id += 1

    # Cycles (active for to-let, maintenance closed, paid request closed)
    cycles = []
    cycle_id = 9_400_000
    active_cycle_by_property = {}
    maintenance_cycle_by_property = {}

    for prop in tolet_props:
        start_at = random_request_datetime() - timedelta(days=random.randint(5, 40))
        ccode = f"CYC_{prop['code']}_ACTIVE"
        active_cycle_by_property[prop["code"]] = ccode
        cycles.append((cycle_id, ccode, prop["code"], dt_fmt(start_at), None, None))
        cycle_id += 1

    for prop in maint_props:
        start_at = random_request_datetime() - timedelta(days=random.randint(10, 70))
        unavailable = start_at + timedelta(days=random.randint(2, 20))
        ccode = f"CYC_{prop['code']}_MNT"
        maintenance_cycle_by_property[prop["code"]] = ccode
        cycles.append((cycle_id, ccode, prop["code"], dt_fmt(start_at), dt_fmt(unavailable), "maintenance"))
        cycle_id += 1

    # Requests
    requests = []
    request_objects = []
    request_id = 9_500_000
    req_num = 1

    def new_request_code():
        nonlocal req_num
        code = f"REQ_{req_num:06d}"
        req_num += 1
        return code

    # Paid requests on rented properties with dedicated rented cycles
    for i in range(REQUEST_STATUS_COUNTS["paid"]):
        prop = rented_props[i % len(rented_props)]
        created = random_request_datetime()
        if prop["city"] in ("Jakarta", "Bandung"):
            delta_days = random.randint(5, 9) if random.random() > 0.05 else random.randint(28, 55)
        else:
            delta_days = random.randint(7, 13) if random.random() > 0.05 else random.randint(28, 55)
        available_from = created - timedelta(days=delta_days, hours=random.randint(0, 23))
        awaiting_at = created + timedelta(hours=random.randint(4, 36))
        paid_at = awaiting_at + timedelta(days=random.randint(1, 3), hours=random.randint(0, 6))
        if paid_at > PERIOD_END:
            paid_at = PERIOD_END - timedelta(hours=random.randint(1, 36))
        due_at = awaiting_at + timedelta(days=7)

        rcode = new_request_code()
        ccode = f"CYC_{prop['code']}_RNT_{i+1:04d}"
        cycles.append((cycle_id, ccode, prop["code"], dt_fmt(available_from), dt_fmt(paid_at), "rented"))
        cycle_id += 1

        tenant_code = random.choice(tenant_codes)
        requests.append((
            request_id, rcode, prop["code"], tenant_code, ccode, "paid",
            dt_fmt(created), dt_fmt(awaiting_at), dt_fmt(due_at), dt_fmt(paid_at), None, None
        ))
        request_objects.append({
            "id": request_id,
            "code": rcode,
            "property_code": prop["code"],
            "tenant_code": tenant_code,
            "status": "paid",
            "created_at": created,
            "paid_at": paid_at,
        })
        request_id += 1

    # Awaiting payment on to-let active cycles
    for _ in range(REQUEST_STATUS_COUNTS["awaiting_payment"]):
        prop = random.choice(tolet_props)
        created = random_request_datetime()
        awaiting_at = created + timedelta(hours=random.randint(2, 30))
        due_at = awaiting_at + timedelta(days=7)
        rcode = new_request_code()
        tenant_code = random.choice(tenant_codes)
        requests.append((
            request_id, rcode, prop["code"], tenant_code, active_cycle_by_property[prop["code"]], "awaiting_payment",
            dt_fmt(created), dt_fmt(awaiting_at), dt_fmt(due_at), None, None, None
        ))
        request_objects.append({
            "id": request_id,
            "code": rcode,
            "property_code": prop["code"],
            "tenant_code": tenant_code,
            "status": "awaiting_payment",
            "created_at": created,
            "paid_at": None,
        })
        request_id += 1

    # Pending review on to-let active cycles
    for _ in range(REQUEST_STATUS_COUNTS["pending_review"]):
        prop = random.choice(tolet_props)
        created = random_request_datetime()
        rcode = new_request_code()
        tenant_code = random.choice(tenant_codes)
        requests.append((
            request_id, rcode, prop["code"], tenant_code, active_cycle_by_property[prop["code"]], "pending_review",
            dt_fmt(created), None, None, None, None, None
        ))
        request_objects.append({
            "id": request_id,
            "code": rcode,
            "property_code": prop["code"],
            "tenant_code": tenant_code,
            "status": "pending_review",
            "created_at": created,
            "paid_at": None,
        })
        request_id += 1

    # Rejected: 700 on to-let, 200 on maintenance
    for i in range(REQUEST_STATUS_COUNTS["rejected"]):
        if i < 700:
            prop = random.choice(tolet_props)
            cycle_code = active_cycle_by_property[prop["code"]]
        else:
            prop = random.choice(maint_props)
            cycle_code = maintenance_cycle_by_property[prop["code"]]
        created = random_request_datetime()
        rejected_at = created + timedelta(days=random.randint(1, 7), hours=random.randint(0, 12))
        rcode = new_request_code()
        tenant_code = random.choice(tenant_codes)
        requests.append((
            request_id, rcode, prop["code"], tenant_code, cycle_code, "rejected",
            dt_fmt(created), None, None, None, dt_fmt(rejected_at), None
        ))
        request_objects.append({
            "id": request_id,
            "code": rcode,
            "property_code": prop["code"],
            "tenant_code": tenant_code,
            "status": "rejected",
            "created_at": created,
            "paid_at": None,
        })
        request_id += 1

    # Cancelled statuses on to-let active cycles
    for status in ["cancelled_by_tenant", "cancelled_by_agent", "cancelled_lost"]:
        for _ in range(REQUEST_STATUS_COUNTS[status]):
            prop = random.choice(tolet_props)
            created = random_request_datetime()
            cancelled_at = created + timedelta(days=random.randint(1, 7), hours=random.randint(0, 14))
            rcode = new_request_code()
            tenant_code = random.choice(tenant_codes)
            requests.append((
                request_id, rcode, prop["code"], tenant_code, active_cycle_by_property[prop["code"]], status,
                dt_fmt(created), None, None, None, None, dt_fmt(cancelled_at)
            ))
            request_objects.append({
                "id": request_id,
                "code": rcode,
                "property_code": prop["code"],
                "tenant_code": tenant_code,
                "status": status,
                "created_at": created,
                "paid_at": None,
            })
            request_id += 1

    # Transactions
    transactions = []
    tx_id = 9_600_000
    tx_num = 1

    def new_tx_code():
        nonlocal tx_num
        code = f"TX_{tx_num:07d}"
        tx_num += 1
        return code

    paid_requests = [r for r in request_objects if r["status"] == "paid"]
    awaiting_requests = [r for r in request_objects if r["status"] == "awaiting_payment"]
    lost_requests = [r for r in request_objects if r["status"] == "cancelled_lost"]

    for r in paid_requests:
        amount = property_code_to_prop[r["property_code"]]["rent_price"]
        transactions.append((tx_id, new_tx_code(), r["code"], "initial_rent", "paid", amount, None))
        tx_id += 1
    for r in awaiting_requests:
        amount = property_code_to_prop[r["property_code"]]["rent_price"]
        transactions.append((tx_id, new_tx_code(), r["code"], "initial_rent", "unpaid", amount, None))
        tx_id += 1
    for r in lost_requests:
        amount = property_code_to_prop[r["property_code"]]["rent_price"]
        transactions.append((tx_id, new_tx_code(), r["code"], "initial_rent", "failed", amount, None))
        tx_id += 1

    # Contracts for paid requests
    contracts = []
    contract_objects = []
    contract_id = 9_700_000
    contract_num = 1
    for r in paid_requests:
        start_date = r["paid_at"] + timedelta(days=1)
        months = random.randint(6, 15)
        end_date = add_months(start_date, months)
        monthly = property_code_to_prop[r["property_code"]]["rent_price"]
        total = monthly * months
        status = "active" if end_date >= TODAY else "ended"
        ccode = f"CTR_{contract_num:07d}"
        contract_num += 1
        contracts.append((
            contract_id, ccode, r["code"], d_fmt(start_date), d_fmt(end_date),
            monthly, total, status, None, None, None
        ))
        contract_objects.append({
            "id": contract_id,
            "code": ccode,
            "request_code": r["code"],
            "property_code": r["property_code"],
            "tenant_code": r["tenant_code"],
            "end_date": end_date,
            "monthly": monthly,
        })
        contract_id += 1

    # Contract extensions for subset of contracts
    selected_contracts = random.sample(contract_objects, 600)
    ext_status_pool = (
        ["pending"] * 120
        + ["awaiting_payment"] * 180
        + ["paid"] * 150
        + ["rejected"] * 60
        + ["cancelled_by_tenant"] * 60
        + ["expired"] * 30
    )
    random.shuffle(ext_status_pool)

    extensions = []
    extension_objects = []
    ext_id = 9_800_000
    ext_num = 1
    for c, ext_status in zip(selected_contracts, ext_status_pool):
        months_req = random.randint(1, 6)
        old_end = c["end_date"]
        new_end = add_months(old_end, months_req)
        extended_at = old_end - timedelta(days=random.randint(5, 40))
        amount = c["monthly"] * months_req
        approved_by = property_code_to_prop[c["property_code"]]["agent_code"]

        approved_at = None
        payment_due = None
        rejected_at = None
        cancelled_at = None
        paid_at = None

        if ext_status in ("awaiting_payment", "paid", "rejected", "cancelled_by_tenant"):
            approved_at = extended_at + timedelta(hours=random.randint(2, 48))
        if ext_status == "awaiting_payment":
            payment_due = approved_at + timedelta(days=7)
        if ext_status == "paid":
            payment_due = approved_at + timedelta(days=7)
            paid_at = approved_at + timedelta(days=random.randint(1, 5))
        if ext_status == "rejected":
            rejected_at = (approved_at or extended_at) + timedelta(days=random.randint(1, 4))
        if ext_status == "cancelled_by_tenant":
            cancelled_at = (approved_at or extended_at) + timedelta(days=random.randint(1, 4))

        ecode = f"EXT_{ext_num:07d}"
        ext_num += 1
        extensions.append((
            ext_id, ecode, c["code"], d_fmt(old_end), d_fmt(new_end), dt_fmt(extended_at),
            months_req, c["monthly"], amount, ext_status, approved_by,
            dt_fmt(approved_at) if approved_at else None,
            dt_fmt(payment_due) if payment_due else None,
            dt_fmt(rejected_at) if rejected_at else None,
            dt_fmt(cancelled_at) if cancelled_at else None,
            dt_fmt(paid_at) if paid_at else None,
        ))
        extension_objects.append({
            "id": ext_id,
            "code": ecode,
            "request_code": c["request_code"],
            "status": ext_status,
            "amount": amount,
        })
        ext_id += 1

    # Extension rent transactions (optional additional KPI volume)
    request_has_extension_tx = set()
    for e in extension_objects:
        status = e["status"]
        if status in ("awaiting_payment", "paid", "rejected"):
            tx_status = "unpaid" if status == "awaiting_payment" else "paid" if status == "paid" else "failed"
            if e["request_code"] in request_has_extension_tx:
                continue
            request_has_extension_tx.add(e["request_code"])
            transactions.append((
                tx_id,
                new_tx_code(),
                e["request_code"],
                "extension_rent",
                tx_status,
                e["amount"],
                e["code"],
            ))
            tx_id += 1

    # Conversations and messages
    req_sorted = sorted(request_objects, key=lambda x: x["created_at"])
    pair_latest = {}
    for r in req_sorted:
        prop = property_code_to_prop[r["property_code"]]
        pair = (r["tenant_code"], prop["agent_code"])
        pair_latest[pair] = r

    conversations = []
    messages = []
    conv_id = 9_900_000
    msg_id = 10_900_000
    conv_num = 1
    msg_num = 1

    for (tenant_code, agent_code), req in pair_latest.items():
        ccode = f"CNV_{conv_num:07d}"
        conv_num += 1
        last_message_at = req["created_at"] + timedelta(hours=random.randint(1, 72))
        conversations.append((
            conv_id, ccode, tenant_code, agent_code, req["property_code"], req["code"], "open", dt_fmt(last_message_at)
        ))

        m1_time = req["created_at"] + timedelta(hours=random.randint(0, 12))
        m2_time = last_message_at
        messages.append((
            msg_id, f"MSG_{msg_num:09d}", ccode, tenant_code, req["property_code"], req["code"],
            "Hello, I am interested in this property.", dt_fmt(m1_time)
        ))
        msg_id += 1
        msg_num += 1
        messages.append((
            msg_id, f"MSG_{msg_num:09d}", ccode, agent_code, req["property_code"], req["code"],
            "Thanks, we can continue the process via this chat.", dt_fmt(m2_time)
        ))
        msg_id += 1
        msg_num += 1
        conv_id += 1

    # Build SQL file
    out_path = Path("storage/app/synthetic-import/synthetic_import_full_v1.sql")
    out_path.parent.mkdir(parents=True, exist_ok=True)

    lines = []
    lines.append("-- Synthetic Import SQL (Full Scale, Indonesia v1)")
    lines.append("-- Generated automatically by scripts/generate_synthetic_sql_full.py")
    lines.append(f"-- Seed: {SEED}")
    lines.append("-- Run: mysql -u <db_user> -p <db_name> < storage/app/synthetic-import/synthetic_import_full_v1.sql")
    lines.append("")
    lines.append("START TRANSACTION;")
    lines.append("SET FOREIGN_KEY_CHECKS=0;")
    lines.append("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;")
    lines.append("")
    lines.extend([
        "TRUNCATE TABLE `login_audit`;",
        "TRUNCATE TABLE `conversation_messages`;",
        "TRUNCATE TABLE `conversations`;",
        "TRUNCATE TABLE `contract_extensions`;",
        "TRUNCATE TABLE `contracts`;",
        "TRUNCATE TABLE `transactions`;",
        "TRUNCATE TABLE `rental_requests`;",
        "TRUNCATE TABLE `property_availability_cycles`;",
        "TRUNCATE TABLE `property_facility`;",
        "TRUNCATE TABLE `property_photos`;",
        "TRUNCATE TABLE `properties`;",
        "TRUNCATE TABLE `users`;",
        "",
    ])

    # Staging table definitions
    lines.extend([
        "DROP TEMPORARY TABLE IF EXISTS `stg_users`;",
        "CREATE TEMPORARY TABLE `stg_users` (",
        "  `id` BIGINT UNSIGNED PRIMARY KEY,",
        "  `user_code` VARCHAR(50) NOT NULL UNIQUE,",
        "  `name` VARCHAR(255) NOT NULL,",
        "  `email` VARCHAR(255) NOT NULL UNIQUE,",
        "  `password_hash` VARCHAR(255) NOT NULL,",
        "  `role` ENUM('admin','agent','tenant') NOT NULL,",
        "  `enabled` TINYINT(1) NOT NULL DEFAULT 1,",
        "  `phone` VARCHAR(30) NULL",
        ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        "",
        "DROP TEMPORARY TABLE IF EXISTS `stg_users_agent`;",
        "CREATE TEMPORARY TABLE `stg_users_agent` (",
        "  `id` BIGINT UNSIGNED PRIMARY KEY,",
        "  `user_code` VARCHAR(50) NOT NULL UNIQUE,",
        "  `name` VARCHAR(255) NOT NULL,",
        "  `email` VARCHAR(255) NOT NULL UNIQUE,",
        "  `password_hash` VARCHAR(255) NOT NULL,",
        "  `role` ENUM('admin','agent','tenant') NOT NULL,",
        "  `enabled` TINYINT(1) NOT NULL DEFAULT 1,",
        "  `phone` VARCHAR(30) NULL",
        ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        "",
        "DROP TEMPORARY TABLE IF EXISTS `stg_properties`;",
        "CREATE TEMPORARY TABLE `stg_properties` (",
        "  `id` BIGINT UNSIGNED PRIMARY KEY,",
        "  `property_code` VARCHAR(50) NOT NULL UNIQUE,",
        "  `agent_code` VARCHAR(50) NOT NULL,",
        "  `province_code` VARCHAR(20) NOT NULL,",
        "  `regency_code` VARCHAR(20) NOT NULL,",
        "  `district_code` VARCHAR(20) NOT NULL,",
        "  `village_code` VARCHAR(20) NOT NULL,",
        "  `title` VARCHAR(255) NOT NULL,",
        "  `address` VARCHAR(255) NULL,",
        "  `description` TEXT NULL,",
        "  `bedrooms` TINYINT UNSIGNED NOT NULL,",
        "  `bathrooms` DECIMAL(3,1) NOT NULL,",
        "  `floors` TINYINT UNSIGNED NULL,",
        "  `area` FLOAT NULL,",
        "  `building_area` DECIMAL(10,2) NULL,",
        "  `rent_price` DECIMAL(12,2) NOT NULL,",
        "  `status` ENUM('to-let','rented','maintenance') NOT NULL,",
        "  `latitude` DECIMAL(10,7) NULL,",
        "  `longitude` DECIMAL(10,7) NULL",
        ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        "",
        "DROP TEMPORARY TABLE IF EXISTS `stg_property_facility`;",
        "CREATE TEMPORARY TABLE `stg_property_facility` (",
        "  `id` BIGINT UNSIGNED PRIMARY KEY,",
        "  `property_code` VARCHAR(50) NOT NULL,",
        "  `facility_slug` VARCHAR(50) NOT NULL,",
        "  `value` VARCHAR(100) NULL",
        ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        "",
        "DROP TEMPORARY TABLE IF EXISTS `stg_cycles`;",
        "CREATE TEMPORARY TABLE `stg_cycles` (",
        "  `id` BIGINT UNSIGNED PRIMARY KEY,",
        "  `cycle_code` VARCHAR(60) NOT NULL UNIQUE,",
        "  `property_code` VARCHAR(50) NOT NULL,",
        "  `available_from_at` DATETIME NOT NULL,",
        "  `unavailable_at` DATETIME NULL,",
        "  `closed_by` ENUM('rented','maintenance','manual') NULL",
        ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        "",
        "DROP TEMPORARY TABLE IF EXISTS `stg_requests`;",
        "CREATE TEMPORARY TABLE `stg_requests` (",
        "  `id` BIGINT UNSIGNED PRIMARY KEY,",
        "  `request_code` VARCHAR(60) NOT NULL UNIQUE,",
        "  `property_code` VARCHAR(50) NOT NULL,",
        "  `tenant_code` VARCHAR(50) NOT NULL,",
        "  `cycle_code` VARCHAR(60) NOT NULL,",
        "  `status` ENUM('pending_review','awaiting_payment','paid','rejected','cancelled_by_tenant','cancelled_by_agent','cancelled_lost') NOT NULL,",
        "  `created_at` DATETIME NOT NULL,",
        "  `awaiting_payment_at` DATETIME NULL,",
        "  `payment_due_at` DATETIME NULL,",
        "  `paid_at` DATETIME NULL,",
        "  `rejected_at` DATETIME NULL,",
        "  `cancelled_at` DATETIME NULL",
        ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        "",
        "DROP TEMPORARY TABLE IF EXISTS `stg_transactions`;",
        "CREATE TEMPORARY TABLE `stg_transactions` (",
        "  `id` BIGINT UNSIGNED PRIMARY KEY,",
        "  `tx_code` VARCHAR(60) NOT NULL UNIQUE,",
        "  `request_code` VARCHAR(60) NOT NULL,",
        "  `type` ENUM('initial_rent','extension_rent') NOT NULL,",
        "  `status` ENUM('unpaid','paid','failed') NOT NULL,",
        "  `amount` DECIMAL(12,2) NOT NULL,",
        "  `extension_code` VARCHAR(60) NULL",
        ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        "",
        "DROP TEMPORARY TABLE IF EXISTS `stg_contracts`;",
        "CREATE TEMPORARY TABLE `stg_contracts` (",
        "  `id` BIGINT UNSIGNED PRIMARY KEY,",
        "  `contract_code` VARCHAR(60) NOT NULL UNIQUE,",
        "  `request_code` VARCHAR(60) NOT NULL UNIQUE,",
        "  `start_date` DATE NOT NULL,",
        "  `end_date` DATE NOT NULL,",
        "  `monthly_rent` DECIMAL(12,2) NOT NULL,",
        "  `total_price` DECIMAL(12,2) NOT NULL,",
        "  `status` ENUM('active','ended') NOT NULL,",
        "  `ended_reason` TEXT NULL,",
        "  `ended_by_user_code` VARCHAR(50) NULL,",
        "  `ended_at` DATETIME NULL",
        ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        "",
        "DROP TEMPORARY TABLE IF EXISTS `stg_contract_extensions`;",
        "CREATE TEMPORARY TABLE `stg_contract_extensions` (",
        "  `id` BIGINT UNSIGNED PRIMARY KEY,",
        "  `extension_code` VARCHAR(60) NOT NULL UNIQUE,",
        "  `contract_code` VARCHAR(60) NOT NULL,",
        "  `old_end_date` DATE NOT NULL,",
        "  `new_end_date` DATE NOT NULL,",
        "  `extended_at` DATETIME NOT NULL,",
        "  `months_requested` INT UNSIGNED NOT NULL,",
        "  `monthly_rent_snapshot` DECIMAL(12,2) NOT NULL,",
        "  `amount` DECIMAL(12,2) NOT NULL,",
        "  `status` ENUM('pending','awaiting_payment','paid','rejected','cancelled_by_tenant','expired') NOT NULL,",
        "  `approved_by_user_code` VARCHAR(50) NULL,",
        "  `approved_at` DATETIME NULL,",
        "  `payment_due_at` DATETIME NULL,",
        "  `rejected_at` DATETIME NULL,",
        "  `cancelled_at` DATETIME NULL,",
        "  `paid_at` DATETIME NULL",
        ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        "",
        "DROP TEMPORARY TABLE IF EXISTS `stg_conversations`;",
        "CREATE TEMPORARY TABLE `stg_conversations` (",
        "  `id` BIGINT UNSIGNED PRIMARY KEY,",
        "  `conversation_code` VARCHAR(60) NOT NULL UNIQUE,",
        "  `tenant_code` VARCHAR(50) NOT NULL,",
        "  `agent_code` VARCHAR(50) NOT NULL,",
        "  `property_code` VARCHAR(50) NOT NULL,",
        "  `request_code` VARCHAR(60) NULL,",
        "  `status` ENUM('open','closed') NOT NULL,",
        "  `last_message_at` DATETIME NULL",
        ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        "",
        "DROP TEMPORARY TABLE IF EXISTS `stg_conversation_messages`;",
        "CREATE TEMPORARY TABLE `stg_conversation_messages` (",
        "  `id` BIGINT UNSIGNED PRIMARY KEY,",
        "  `message_code` VARCHAR(60) NOT NULL UNIQUE,",
        "  `conversation_code` VARCHAR(60) NOT NULL,",
        "  `sender_code` VARCHAR(50) NOT NULL,",
        "  `property_code` VARCHAR(50) NULL,",
        "  `request_code` VARCHAR(60) NULL,",
        "  `message` TEXT NOT NULL,",
        "  `created_at` DATETIME NOT NULL",
        ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
        "",
    ])

    # Staging data inserts
    chunked_insert(lines, "stg_users",
                   ["id", "user_code", "name", "email", "password_hash", "role", "enabled", "phone"], users, 800)
    chunked_insert(lines, "stg_users_agent",
                   ["id", "user_code", "name", "email", "password_hash", "role", "enabled", "phone"], users, 800)
    chunked_insert(lines, "stg_properties",
                   ["id", "property_code", "agent_code", "province_code", "regency_code", "district_code", "village_code",
                    "title", "address", "description", "bedrooms", "bathrooms", "floors", "area", "building_area",
                    "rent_price", "status", "latitude", "longitude"], properties, 500)
    chunked_insert(lines, "stg_property_facility",
                   ["id", "property_code", "facility_slug", "value"], property_facility, 1500)
    chunked_insert(lines, "stg_cycles",
                   ["id", "cycle_code", "property_code", "available_from_at", "unavailable_at", "closed_by"], cycles, 1500)
    chunked_insert(lines, "stg_requests",
                   ["id", "request_code", "property_code", "tenant_code", "cycle_code", "status",
                    "created_at", "awaiting_payment_at", "payment_due_at", "paid_at", "rejected_at", "cancelled_at"], requests, 1500)
    chunked_insert(lines, "stg_transactions",
                   ["id", "tx_code", "request_code", "type", "status", "amount", "extension_code"], transactions, 1500)
    chunked_insert(lines, "stg_contracts",
                   ["id", "contract_code", "request_code", "start_date", "end_date", "monthly_rent", "total_price",
                    "status", "ended_reason", "ended_by_user_code", "ended_at"], contracts, 1200)
    chunked_insert(lines, "stg_contract_extensions",
                   ["id", "extension_code", "contract_code", "old_end_date", "new_end_date", "extended_at", "months_requested",
                    "monthly_rent_snapshot", "amount", "status", "approved_by_user_code", "approved_at", "payment_due_at",
                    "rejected_at", "cancelled_at", "paid_at"], extensions, 1200)
    chunked_insert(lines, "stg_conversations",
                   ["id", "conversation_code", "tenant_code", "agent_code", "property_code", "request_code", "status", "last_message_at"], conversations, 1500)
    chunked_insert(lines, "stg_conversation_messages",
                   ["id", "message_code", "conversation_code", "sender_code", "property_code", "request_code", "message", "created_at"], messages, 2000)

    # Validation checks
    lines.extend([
        "-- Validation checks",
        "SELECT COUNT(*) AS missing_region_refs",
        "FROM `stg_properties` sp",
        "LEFT JOIN `provinces` p ON p.code = sp.province_code",
        "LEFT JOIN `regencies` r ON r.code = sp.regency_code",
        "LEFT JOIN `districts` d ON d.code = sp.district_code",
        "LEFT JOIN `villages` v ON v.code = sp.village_code",
        "WHERE p.id IS NULL OR r.id IS NULL OR d.id IS NULL OR v.id IS NULL;",
        "",
        "SELECT COUNT(*) AS missing_facility_refs",
        "FROM `stg_property_facility` pf",
        "LEFT JOIN `facilities` f ON f.slug = pf.facility_slug",
        "WHERE f.id IS NULL;",
        "",
    ])

    # Final inserts into domain tables
    lines.extend([
        "INSERT INTO `users`",
        "(`id`,`name`,`email`,`password`,`role`,`phone`,`enabled`,`email_verified_at`,`remember_token`,`created_at`,`updated_at`)",
        "SELECT id,name,email,password_hash,role,phone,enabled,NULL,NULL,NOW(),NOW()",
        "FROM `stg_users`;",
        "",
        "INSERT INTO `properties`",
        "(`id`,`agent_id`,`title`,`province_id`,`regency_id`,`district_id`,`village_id`,`address`,`description`,`latitude`,`longitude`,`bedrooms`,`bathrooms`,`floors`,`area`,`building_area`,`facilities`,`rent_price`,`status`,`created_at`,`updated_at`)",
        "SELECT",
        "  sp.id,",
        "  su.id,",
        "  sp.title,",
        "  p.id,",
        "  r.id,",
        "  d.id,",
        "  v.id,",
        "  sp.address,",
        "  sp.description,",
        "  sp.latitude,",
        "  sp.longitude,",
        "  sp.bedrooms,",
        "  sp.bathrooms,",
        "  sp.floors,",
        "  sp.area,",
        "  sp.building_area,",
        "  NULL,",
        "  sp.rent_price,",
        "  sp.status,",
        "  NOW(),",
        "  NOW()",
        "FROM `stg_properties` sp",
        "JOIN `stg_users` su ON su.user_code = sp.agent_code AND su.role = 'agent'",
        "JOIN `provinces` p ON p.code = sp.province_code",
        "JOIN `regencies` r ON r.code = sp.regency_code",
        "JOIN `districts` d ON d.code = sp.district_code",
        "JOIN `villages` v ON v.code = sp.village_code;",
        "",
        "INSERT INTO `property_facility`",
        "(`id`,`property_id`,`facility_id`,`value`,`created_at`,`updated_at`)",
        "SELECT",
        "  pf.id,",
        "  sp.id,",
        "  f.id,",
        "  pf.value,",
        "  NOW(),",
        "  NOW()",
        "FROM `stg_property_facility` pf",
        "JOIN `stg_properties` sp ON sp.property_code = pf.property_code",
        "JOIN `facilities` f ON f.slug = pf.facility_slug;",
        "",
        "INSERT INTO `property_availability_cycles`",
        "(`id`,`property_id`,`available_from_at`,`unavailable_at`,`closed_by`,`created_at`,`updated_at`)",
        "SELECT sc.id, sp.id, sc.available_from_at, sc.unavailable_at, sc.closed_by, NOW(), NOW()",
        "FROM `stg_cycles` sc",
        "JOIN `stg_properties` sp ON sp.property_code = sc.property_code;",
        "",
        "INSERT INTO `rental_requests`",
        "(`id`,`property_id`,`availability_cycle_id`,`tenant_id`,`status`,`awaiting_payment_at`,`payment_due_at`,`paid_at`,`rejected_at`,`cancelled_at`,`created_at`,`updated_at`)",
        "SELECT sr.id, sp.id, sc.id, st.id, sr.status, sr.awaiting_payment_at, sr.payment_due_at, sr.paid_at, sr.rejected_at, sr.cancelled_at, sr.created_at, sr.created_at",
        "FROM `stg_requests` sr",
        "JOIN `stg_properties` sp ON sp.property_code = sr.property_code",
        "JOIN `stg_cycles` sc ON sc.cycle_code = sr.cycle_code",
        "JOIN `stg_users` st ON st.user_code = sr.tenant_code AND st.role = 'tenant';",
        "",
        "INSERT INTO `contracts`",
        "(`id`,`rental_request_id`,`start_date`,`end_date`,`total_price`,`monthly_rent`,`status`,`ended_reason`,`ended_by`,`ended_at`,`created_at`,`updated_at`)",
        "SELECT",
        "  sc.id,",
        "  sr.id,",
        "  sc.start_date,",
        "  sc.end_date,",
        "  sc.total_price,",
        "  sc.monthly_rent,",
        "  sc.status,",
        "  sc.ended_reason,",
        "  ended_by.id,",
        "  sc.ended_at,",
        "  NOW(),",
        "  NOW()",
        "FROM `stg_contracts` sc",
        "JOIN `stg_requests` sr ON sr.request_code = sc.request_code",
        "LEFT JOIN `stg_users` ended_by ON ended_by.user_code = sc.ended_by_user_code;",
        "",
        "INSERT INTO `contract_extensions`",
        "(`id`,`contract_id`,`old_end_date`,`new_end_date`,`extended_at`,`months_requested`,`monthly_rent_snapshot`,`amount`,`status`,`approved_by`,`approved_at`,`payment_due_at`,`rejected_at`,`cancelled_at`,`paid_at`,`created_at`,`updated_at`)",
        "SELECT",
        "  se.id,",
        "  sc.id,",
        "  se.old_end_date,",
        "  se.new_end_date,",
        "  se.extended_at,",
        "  se.months_requested,",
        "  se.monthly_rent_snapshot,",
        "  se.amount,",
        "  se.status,",
        "  approved_by.id,",
        "  se.approved_at,",
        "  se.payment_due_at,",
        "  se.rejected_at,",
        "  se.cancelled_at,",
        "  se.paid_at,",
        "  NOW(),",
        "  NOW()",
        "FROM `stg_contract_extensions` se",
        "JOIN `stg_contracts` sc ON sc.contract_code = se.contract_code",
        "LEFT JOIN `stg_users` approved_by ON approved_by.user_code = se.approved_by_user_code;",
        "",
        "INSERT INTO `transactions`",
        "(`id`,`rental_request_id`,`property_id`,`tenant_id`,`agent_id`,`contract_extension_id`,`amount`,`type`,`status`,`created_at`,`updated_at`)",
        "SELECT",
        "  stx.id,",
        "  sr.id,",
        "  sp.id,",
        "  tenant.id,",
        "  agent.id,",
        "  se.id,",
        "  stx.amount,",
        "  stx.type,",
        "  stx.status,",
        "  NOW(),",
        "  NOW()",
        "FROM `stg_transactions` stx",
        "JOIN `stg_requests` sr ON sr.request_code = stx.request_code",
        "JOIN `stg_properties` sp ON sp.property_code = sr.property_code",
        "JOIN `stg_users` tenant ON tenant.user_code = sr.tenant_code AND tenant.role = 'tenant'",
        "JOIN `stg_users_agent` agent ON agent.user_code = sp.agent_code AND agent.role = 'agent'",
        "LEFT JOIN `stg_contract_extensions` se ON se.extension_code = stx.extension_code;",
        "",
        "INSERT INTO `conversations`",
        "(`id`,`property_id`,`tenant_id`,`agent_id`,`rental_request_id`,`status`,`last_message_at`,`created_at`,`updated_at`)",
        "SELECT",
        "  scv.id,",
        "  sp.id,",
        "  tenant.id,",
        "  agent.id,",
        "  sr.id,",
        "  scv.status,",
        "  scv.last_message_at,",
        "  NOW(),",
        "  NOW()",
        "FROM `stg_conversations` scv",
        "JOIN `stg_properties` sp ON sp.property_code = scv.property_code",
        "JOIN `stg_users` tenant ON tenant.user_code = scv.tenant_code AND tenant.role = 'tenant'",
        "JOIN `stg_users_agent` agent ON agent.user_code = scv.agent_code AND agent.role = 'agent'",
        "LEFT JOIN `stg_requests` sr ON sr.request_code = scv.request_code;",
        "",
        "INSERT INTO `conversation_messages`",
        "(`id`,`conversation_id`,`sender_id`,`property_id`,`rental_request_id`,`message`,`created_at`,`updated_at`)",
        "SELECT",
        "  scm.id,",
        "  scv.id,",
        "  sender.id,",
        "  sp.id,",
        "  sr.id,",
        "  scm.message,",
        "  scm.created_at,",
        "  scm.created_at",
        "FROM `stg_conversation_messages` scm",
        "JOIN `stg_conversations` scv ON scv.conversation_code = scm.conversation_code",
        "JOIN `stg_users` sender ON sender.user_code = scm.sender_code",
        "LEFT JOIN `stg_properties` sp ON sp.property_code = scm.property_code",
        "LEFT JOIN `stg_requests` sr ON sr.request_code = scm.request_code;",
        "",
        "SET FOREIGN_KEY_CHECKS=1;",
        "COMMIT;",
        "",
    ])

    out_path.write_text("\n".join(lines), encoding="utf-8")

    print(f"Generated: {out_path}")
    print(f"Users: {len(users)}")
    print(f"Properties: {len(properties)}")
    print(f"Property facilities: {len(property_facility)}")
    print(f"Cycles: {len(cycles)}")
    print(f"Requests: {len(requests)}")
    print(f"Transactions: {len(transactions)}")
    print(f"Contracts: {len(contracts)}")
    print(f"Contract extensions: {len(extensions)}")
    print(f"Conversations: {len(conversations)}")
    print(f"Conversation messages: {len(messages)}")


if __name__ == "__main__":
    main()
