<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Contract</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Times-Roman", "Times New Roman", Times, serif;
            color: #0f172a;
            font-size: 12px;
            line-height: 1.5;
            background: #fff;
        }
        * {
            font-family: "Times-Roman", "Times New Roman", Times, serif !important;
        }
        .contract-view-page {
            max-width: 900px;
            margin: 0 auto;
            padding: 0;
        }
        .contract-paper {
            border: 0;
            border-radius: 0;
            background: transparent;
            padding: 0;
        }
        .contract-header-title {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: .4px;
            margin: 0 0 6px;
        }
        .contract-subtle {
            color: #64748b;
            font-size: 12px;
            margin-bottom: 2px;
        }
        .contract-section {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
        }
        .contract-section-title {
            font-size: 14px;
            font-weight: 700;
            margin: 0 0 8px;
        }
        .contract-table {
            width: 100%;
            border-collapse: collapse;
        }
        .contract-table td {
            width: 50%;
            vertical-align: top;
            padding: 4px 8px 6px 0;
        }
        .contract-label {
            color: #64748b;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 0;
            display: inline;
        }
        .contract-value {
            font-size: 13px;
            font-weight: 600;
            color: #0f172a;
            word-break: break-word;
            display: inline;
        }
        .contract-row {
            margin-bottom: 2px;
        }
        .contract-note {
            margin-top: 18px;
            color: #475569;
            font-size: 11px;
            font-style: italic;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .signature-table td {
            width: 50%;
            vertical-align: top;
            padding-right: 14px;
        }
        .signature-line {
            height: 1px;
            background: #94a3b8;
            margin: 4px 0 8px;
        }
    </style>
</head>
<body>
<div class="contract-view-page">
    <article class="contract-paper">
        <header>
            <h1 class="contract-header-title">HOUSE RENTAL CONTRACT</h1>
            <div class="contract-subtle">Issued: {{ $date }}</div>
        </header>

        <section class="contract-section">
            <h2 class="contract-section-title">Parties</h2>
            <table class="contract-table">
                <tr>
                    <td>
                        <div class="contract-row"><span class="contract-label">Tenant: </span><span class="contract-value">{{ $tenant->name }} ({{ $tenant->email }})</span></div>
                    </td>
                    <td>
                        <div class="contract-row"><span class="contract-label">Agent: </span><span class="contract-value">{{ $agent->name }} ({{ $agent->email }})</span></div>
                    </td>
                </tr>
            </table>
        </section>

        <section class="contract-section">
            <h2 class="contract-section-title">Property Information</h2>
            <table class="contract-table">
                <tr>
                    <td>
                        <div class="contract-row"><span class="contract-label">Title: </span><span class="contract-value">{{ $property->title }}</span></div>
                    </td>
                    <td>
                        <div class="contract-row"><span class="contract-label">Province: </span><span class="contract-value">{{ $property->province?->name ?? '-' }}</span></div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="contract-row"><span class="contract-label">Regency: </span><span class="contract-value">{{ $property->regency?->name ?? '-' }}</span></div>
                    </td>
                    <td>
                        <div class="contract-row"><span class="contract-label">District: </span><span class="contract-value">{{ $property->district?->name ?? '-' }}</span></div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="contract-row"><span class="contract-label">Village: </span><span class="contract-value">{{ $property->village?->name ?? '-' }}</span></div>
                    </td>
                    <td>
                        <div class="contract-row"><span class="contract-label">Address: </span><span class="contract-value">{{ $property->address }}</span></div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="contract-row"><span class="contract-label">Bedrooms / Bathrooms: </span><span class="contract-value">{{ (int) $property->bedrooms }} / {{ (int) $property->bathrooms }}</span></div>
                    </td>
                    <td>
                        <div class="contract-row"><span class="contract-label">Land Area: </span><span class="contract-value">{{ number_format((float) ($property->land_area ?? 0), 0, ',', '.') }} m²</span></div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="contract-row"><span class="contract-label">Building Area: </span><span class="contract-value">{{ number_format((float) ($property->building_area ?? 0), 0, ',', '.') }} m²</span></div>
                    </td>
                    <td>
                        <div class="contract-row"><span class="contract-label">Floors: </span><span class="contract-value">{{ (int) ($property->floors ?? 0) }}</span></div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="contract-row"><span class="contract-label">Monthly Rent: </span><span class="contract-value">Rp {{ number_format((float) $monthlyRent, 0, ',', '.') }}</span></div>
                    </td>
                    <td></td>
                </tr>
            </table>
        </section>

        <section class="contract-section">
            <h2 class="contract-section-title">{{ $periodLabel }}</h2>
            <table class="contract-table">
                <tr>
                    <td>
                        <div class="contract-row"><span class="contract-label">Start Date: </span><span class="contract-value">{{ $periodStart }}</span></div>
                    </td>
                    <td>
                        <div class="contract-row"><span class="contract-label">End Date: </span><span class="contract-value">{{ $periodEnd }}</span></div>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div class="contract-row"><span class="contract-label">Months: </span><span class="contract-value">{{ $periodMonths }}</span></div>
                    </td>
                    <td>
                        <div class="contract-row"><span class="contract-label">Contract Amount: </span><span class="contract-value">Rp {{ number_format((float) $periodAmount, 0, ',', '.') }}</span></div>
                    </td>
                </tr>
            </table>
        </section>

        <section class="contract-section">
            <h2 class="contract-section-title">Agreement</h2>
            <p style="margin:0;">
                This contract confirms that the tenant agrees to rent the above property under the terms approved by the property agent.
            </p>
        </section>

        <section class="contract-section">
            <h2 class="contract-section-title">Signatures</h2>
            <table class="signature-table">
                <tr>
                    <td>
                        <div class="signature-line"></div>
                        <div class="contract-row"><span class="contract-label">Tenant: </span><span class="contract-value">{{ $tenant->name }}</span></div>
                        <div class="contract-row"><span class="contract-label">Date: </span><span class="contract-value">{{ $date }}</span></div>
                    </td>
                    <td>
                        <div class="signature-line"></div>
                        <div class="contract-row"><span class="contract-label">Agent: </span><span class="contract-value">{{ $agent->name }}</span></div>
                        <div class="contract-row"><span class="contract-label">Date: </span><span class="contract-value">{{ $date }}</span></div>
                    </td>
                </tr>
            </table>
        </section>

        <p class="contract-note">System-generated contract document.</p>
    </article>
</div>
</body>
</html>
