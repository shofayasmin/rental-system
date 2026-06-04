@extends('layouts.app')

@section('content')
@php($isPrintMode = request()->boolean('print'))
<style>
    /* breadcrumb removed */
    .contract-view-page {
        max-width: 900px;
        margin: 0 auto;
    }
    .contract-paper {
        border: 1px solid #dbe3ee;
        border-radius: 12px;
        background: #fff;
        padding: 28px;
    }
    .contract-header-title {
        font-weight: 800;
        letter-spacing: .4px;
        margin-bottom: 4px;
    }
    .contract-subtle {
        color: #64748b;
        font-size: .95rem;
    }
    .contract-section + .contract-section {
        margin-top: 18px;
        padding-top: 18px;
        border-top: 1px solid #e2e8f0;
    }
    .contract-section-title {
        font-size: 1.02rem;
        font-weight: 700;
        margin-bottom: 10px;
    }
    .contract-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px 20px;
    }
    .contract-item {
        margin: 0;
        display: flex;
        gap: 8px;
        align-items: baseline;
    }
    .contract-label {
        color: #64748b;
        font-size: .86rem;
        font-weight: 600;
        margin-bottom: 0;
        min-width: 130px;
    }
    .contract-value {
        font-size: 1rem;
        font-weight: 600;
        color: #0f172a;
        word-break: break-word;
    }
    .contract-note {
        margin-top: 20px;
        color: #475569;
        font-size: .92rem;
        font-style: italic;
    }
    .signature-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
        margin-top: 8px;
    }
    .signature-item {
        padding-top: 8px;
    }
    .signature-line {
        height: 1px;
        background: #94a3b8;
        margin-bottom: 8px;
    }
    .contract-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 14px;
    }
    @media (max-width: 768px) {
        .contract-paper {
            padding: 18px;
        }
        .contract-grid {
            grid-template-columns: 1fr;
        }
        .signature-grid {
            grid-template-columns: 1fr;
        }
    }

    @page {
        size: A4;
        margin: 12mm;
    }
    @media print {
        nav, footer, .no-print {
            display: none !important;
        }
        .alert {
            display: none !important;
        }
        body, .container, main {
            background: #fff !important;
        }
        .contract-view-page {
            max-width: none;
            margin: 0;
        }
        .contract-paper {
            border: 0;
            border-radius: 0;
            padding: 0;
        }
        .contract-section + .contract-section {
            margin-top: 14px;
            padding-top: 14px;
        }
    }
</style>

<div class="contract-view-page">
    @if(!$isPrintMode)
    <div class="contract-actions no-print">
        <a href="{{ url('/contracts/' . $transaction->id) . '?print=1' }}"
           class="btn btn-primary">
            Print Contract
        </a>
        <a href="{{ url('/contracts/' . $transaction->id . '/download-pdf') }}" class="btn btn-outline-primary">Download PDF</a>
    </div>
    @endif

    <article class="contract-paper">
        <header class="mb-3">
            <h1 class="h4 contract-header-title">HOUSE RENTAL CONTRACT</h1>
            <div class="contract-subtle">
                Issued: {{ $date }}
            </div>
        </header>

        <section class="contract-section">
            <h2 class="contract-section-title">Parties</h2>
            <div class="contract-grid">
                <div class="contract-item">
                    <div class="contract-label">Tenant:</div>
                    <div class="contract-value">{{ $tenant->name }} ({{ $tenant->email }})</div>
                </div>
                <div class="contract-item">
                    <div class="contract-label">Agent:</div>
                    <div class="contract-value">{{ $agent->name }} ({{ $agent->email }})</div>
                </div>
            </div>
        </section>

        <section class="contract-section">
            <h2 class="contract-section-title">Property Information</h2>
            <div class="contract-grid">
                <div class="contract-item">
                    <div class="contract-label">Title:</div>
                    <div class="contract-value">{{ $property->title }}</div>
                </div>
                <div class="contract-item">
                    <div class="contract-label">Province:</div>
                    <div class="contract-value">{{ $property->province?->name ?? '-' }}</div>
                </div>
                <div class="contract-item">
                    <div class="contract-label">Regency:</div>
                    <div class="contract-value">{{ $property->regency?->name ?? '-' }}</div>
                </div>
                <div class="contract-item">
                    <div class="contract-label">District:</div>
                    <div class="contract-value">{{ $property->district?->name ?? '-' }}</div>
                </div>
                <div class="contract-item">
                    <div class="contract-label">Village:</div>
                    <div class="contract-value">{{ $property->village?->name ?? '-' }}</div>
                </div>
                <div class="contract-item">
                    <div class="contract-label">Address:</div>
                    <div class="contract-value">{{ $property->address }}</div>
                </div>
                <div class="contract-item">
                    <div class="contract-label">Bedrooms / Bathrooms:</div>
                    <div class="contract-value">{{ (int) $property->bedrooms }} / {{ (int) $property->bathrooms }}</div>
                </div>
                <div class="contract-item">
                    <div class="contract-label">Land Area:</div>
                    <div class="contract-value">{{ number_format((float) ($property->land_area ?? 0), 0, ',', '.') }} m²</div>
                </div>
                <div class="contract-item">
                    <div class="contract-label">Building Area:</div>
                    <div class="contract-value">{{ number_format((float) ($property->building_area ?? 0), 0, ',', '.') }} m²</div>
                </div>
                <div class="contract-item">
                    <div class="contract-label">Floors:</div>
                    <div class="contract-value">{{ (int) ($property->floors ?? 0) }}</div>
                </div>
                <div class="contract-item">
                    <div class="contract-label">Monthly Rent:</div>
                    <div class="contract-value">Rp {{ number_format((float) $monthlyRent, 0, ',', '.') }}</div>
                </div>
            </div>
        </section>

        <section class="contract-section">
            <h2 class="contract-section-title">{{ $periodLabel }}</h2>
            <div class="contract-grid">
                <div class="contract-item">
                    <div class="contract-label">Start Date:</div>
                    <div class="contract-value">{{ $periodStart }}</div>
                </div>
                <div class="contract-item">
                    <div class="contract-label">End Date:</div>
                    <div class="contract-value">{{ $periodEnd }}</div>
                </div>
                <div class="contract-item">
                    <div class="contract-label">Months:</div>
                    <div class="contract-value">{{ $periodMonths }}</div>
                </div>
                <div class="contract-item">
                    <div class="contract-label">Contract Amount:</div>
                    <div class="contract-value">Rp {{ number_format((float) $periodAmount, 0, ',', '.') }}</div>
                </div>
            </div>
        </section>

        <section class="contract-section">
            <h2 class="contract-section-title">Agreement</h2>
            <p class="mb-0">
                This contract confirms that the tenant agrees to rent the above property under the terms approved by the property agent.
            </p>
        </section>

        <section class="contract-section">
            <h2 class="contract-section-title">Signatures</h2>
            <div class="signature-grid">
                <div class="signature-item">
                    <div class="signature-line"></div>
                    <div class="contract-item"><div class="contract-label">Tenant:</div><div class="contract-value">{{ $tenant->name }}</div></div>
                    <div class="contract-item"><div class="contract-label">Date:</div><div class="contract-value">{{ $date }}</div></div>
                </div>
                <div class="signature-item">
                    <div class="signature-line"></div>
                    <div class="contract-item"><div class="contract-label">Agent:</div><div class="contract-value">{{ $agent->name }}</div></div>
                    <div class="contract-item"><div class="contract-label">Date:</div><div class="contract-value">{{ $date }}</div></div>
                </div>
            </div>
        </section>

        <p class="contract-note">System-generated contract document.</p>
    </article>
</div>
@if($isPrintMode)
<script>
    window.addEventListener('load', function () {
        const cleanUrl = window.location.href
            .replace(/([?&])print=1(&?)/, function (_, prefix, suffix) {
                if (prefix === '?' && suffix) return '?';
                if (prefix === '&') return suffix ? '&' : '';
                return '';
            })
            .replace(/[?&]$/, '');

        window.addEventListener('afterprint', function () {
            if (window.opener) {
                window.close();
                return;
            }
            window.location.replace(cleanUrl);
        });

        window.print();
    });
</script>
@endif
@endsection
