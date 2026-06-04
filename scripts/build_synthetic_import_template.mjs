import fs from "node:fs/promises";
import path from "node:path";
import { Workbook, SpreadsheetFile } from "@oai/artifact-tool";

const baseDir = path.resolve("storage/app/synthetic-import/templates");

const sheets = [
  {
    name: "00_config",
    headers: ["key", "value"],
    sample: [
      ["period_start", "2025-01-01 00:00:00"],
      ["period_end", "2026-04-30 23:59:59"],
      ["total_properties", "600"],
      ["total_agents", "30"],
      ["total_tenants", "1500"],
      ["target_requests", "6000"],
      ["random_seed", "20260524"],
    ],
  },
  {
    name: "01_users",
    headers: [
      "user_code",
      "name",
      "email",
      "password_plain",
      "role",
      "enabled",
      "phone",
    ],
    sample: [
      [
        "USR_ADM_0001",
        "Admin Local",
        "admin@local.test",
        "password123",
        "admin",
        "1",
        "081200000001",
      ],
      [
        "USR_AGT_0001",
        "Agent 1",
        "agent0001@local.test",
        "password123",
        "agent",
        "1",
        "081200100001",
      ],
      [
        "USR_TNT_0001",
        "Tenant 1",
        "tenant0001@local.test",
        "password123",
        "tenant",
        "1",
        "081200200001",
      ],
    ],
  },
  {
    name: "02_properties",
    headers: [
      "property_code",
      "agent_code",
      "city_bucket",
      "province_code",
      "regency_code",
      "district_code",
      "village_code",
      "title",
      "address",
      "description",
      "bedrooms",
      "bathrooms",
      "floors",
      "area",
      "building_area",
      "rent_price",
      "status",
      "latitude",
      "longitude",
    ],
    sample: [
      [
        "PRP_JKT_0001",
        "USR_AGT_0001",
        "Jakarta",
        "31",
        "3171",
        "3171020",
        "3171020001",
        "Kost Strategis Menteng",
        "Jl. Contoh No. 1, Menteng",
        "Dekat halte dan minimarket",
        "2",
        "1.0",
        "2",
        "90",
        "75",
        "4500000",
        "to-let",
        "-6.1976000",
        "106.8342000",
      ],
    ],
  },
  {
    name: "03_property_facility",
    headers: ["property_code", "facility_slug", "value"],
    sample: [
      ["PRP_JKT_0001", "wifi", "100 Mbps"],
      ["PRP_JKT_0001", "electricity", "2200 VA"],
    ],
  },
  {
    name: "04_availability_cycles",
    headers: [
      "cycle_code",
      "property_code",
      "available_from_at",
      "unavailable_at",
      "closed_by",
    ],
    sample: [["CYC_PRP_JKT_0001_01", "PRP_JKT_0001", "2025-01-03 09:00:00", "", ""]],
  },
  {
    name: "05_rental_requests",
    headers: [
      "request_code",
      "property_code",
      "tenant_code",
      "cycle_code",
      "status",
      "created_at",
      "awaiting_payment_at",
      "payment_due_at",
      "paid_at",
      "rejected_at",
      "cancelled_at",
    ],
    sample: [
      [
        "REQ_000001",
        "PRP_JKT_0001",
        "USR_TNT_0001",
        "CYC_PRP_JKT_0001_01",
        "pending_review",
        "2025-01-05 10:30:00",
        "",
        "",
        "",
        "",
        "",
      ],
      [
        "REQ_000002",
        "PRP_JKT_0001",
        "USR_TNT_0001",
        "CYC_PRP_JKT_0001_01",
        "paid",
        "2025-01-07 12:00:00",
        "2025-01-08 09:00:00",
        "2025-01-15 09:00:00",
        "2025-01-10 14:00:00",
        "",
        "",
      ],
    ],
  },
  {
    name: "06_transactions",
    headers: ["tx_code", "request_code", "type", "status", "amount", "extension_code"],
    sample: [["TX_000001", "REQ_000002", "initial_rent", "paid", "4500000", ""]],
  },
  {
    name: "07_contracts",
    headers: [
      "contract_code",
      "request_code",
      "start_date",
      "end_date",
      "monthly_rent",
      "total_price",
      "status",
      "ended_reason",
      "ended_by_user_code",
      "ended_at",
    ],
    sample: [
      [
        "CTR_000001",
        "REQ_000002",
        "2025-01-11",
        "2025-07-10",
        "4500000",
        "27000000",
        "active",
        "",
        "",
        "",
      ],
    ],
  },
  {
    name: "08_contract_extensions",
    headers: [
      "extension_code",
      "contract_code",
      "old_end_date",
      "new_end_date",
      "extended_at",
      "months_requested",
      "monthly_rent_snapshot",
      "amount",
      "status",
      "approved_by_user_code",
      "approved_at",
      "payment_due_at",
      "paid_at",
      "rejected_at",
      "cancelled_at",
    ],
    sample: [
      [
        "EXT_000001",
        "CTR_000001",
        "2025-07-10",
        "2025-10-10",
        "2025-06-20 10:00:00",
        "3",
        "4500000",
        "13500000",
        "awaiting_payment",
        "USR_AGT_0001",
        "2025-06-20 10:00:00",
        "2025-06-27 10:00:00",
        "",
        "",
        "",
      ],
    ],
  },
  {
    name: "09_conversations",
    headers: [
      "conversation_code",
      "tenant_code",
      "agent_code",
      "property_code",
      "request_code",
      "status",
      "last_message_at",
    ],
    sample: [
      [
        "CNV_000001",
        "USR_TNT_0001",
        "USR_AGT_0001",
        "PRP_JKT_0001",
        "REQ_000002",
        "open",
        "2025-01-09 09:15:00",
      ],
    ],
  },
  {
    name: "10_conversation_messages",
    headers: [
      "message_code",
      "conversation_code",
      "sender_code",
      "property_code",
      "request_code",
      "message",
      "created_at",
    ],
    sample: [
      [
        "MSG_000001",
        "CNV_000001",
        "USR_TNT_0001",
        "PRP_JKT_0001",
        "REQ_000002",
        "Halo kak, saya sudah transfer DP.",
        "2025-01-09 09:10:00",
      ],
      [
        "MSG_000002",
        "CNV_000001",
        "USR_AGT_0001",
        "PRP_JKT_0001",
        "REQ_000002",
        "Siap, kami verifikasi hari ini ya.",
        "2025-01-09 09:15:00",
      ],
    ],
  },
];

function columnName(index) {
  let value = "";
  let n = index;

  while (n > 0) {
    const mod = (n - 1) % 26;
    value = String.fromCharCode(65 + mod) + value;
    n = Math.floor((n - 1) / 26);
  }

  return value;
}

function escapeCsv(value) {
  const text = String(value ?? "");
  if (text.includes(",") || text.includes('"') || text.includes("\n")) {
    return `"${text.replaceAll('"', '""')}"`;
  }

  return text;
}

await fs.mkdir(baseDir, { recursive: true });

const workbook = Workbook.create();

for (const sheetSpec of sheets) {
  const worksheet = workbook.worksheets.add(sheetSpec.name);
  const rows = [sheetSpec.headers, ...sheetSpec.sample];
  const endColumn = columnName(sheetSpec.headers.length);
  worksheet.getRange(`A1:${endColumn}${rows.length}`).values = rows;

  const csvText = rows
    .map((row) => row.map((col) => escapeCsv(col)).join(","))
    .join("\n");

  await fs.writeFile(path.join(baseDir, `${sheetSpec.name}.csv`), `${csvText}\n`, "utf8");
}

const xlsx = await SpreadsheetFile.exportXlsx(workbook);
const xlsxPath = path.join(baseDir, "synthetic_import_template_v1_1.xlsx");
await xlsx.save(xlsxPath);

console.log(`Templates created in: ${baseDir}`);
console.log(`Workbook: ${xlsxPath}`);
