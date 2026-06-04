<style>
    .property-detail-breadcrumb .breadcrumb {
        margin-bottom: 0;
    }
    .property-detail-breadcrumb .breadcrumb-item,
    .property-detail-breadcrumb .breadcrumb-item a {
        color: #64748b;
        font-size: .9rem;
        text-decoration: none;
    }
    .property-detail-breadcrumb .breadcrumb-item.active {
        color: #334155;
    }
    .detail-gallery-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 250px;
        gap: 14px;
    }
    .detail-gallery-grid.single-photo {
        grid-template-columns: minmax(0, 1fr);
    }
    .detail-main-photo {
        position: relative;
        width: 100%;
        height: 520px;
        border-radius: 16px;
        overflow: hidden;
        background: #f2f4f8;
    }
    .detail-main-photo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        cursor: pointer;
    }
    .detail-side-stack {
        display: grid;
        grid-template-rows: repeat(4, 1fr);
        gap: 10px;
        height: 520px;
    }
    .detail-side-thumb {
        border: 0;
        padding: 0;
        border-radius: 12px;
        overflow: hidden;
        background: #f2f4f8;
        position: relative;
        cursor: pointer;
    }
    .detail-side-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .detail-side-thumb.active {
        outline: 3px solid #2f6dd6;
    }
    .detail-see-all {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }
    .detail-see-all::after {
        content: "";
        position: absolute;
        inset: 0;
        background: rgba(11, 19, 43, 0.45);
    }
    .detail-see-all span {
        position: absolute;
        inset: 0;
        z-index: 1;
        color: #fff;
        font-weight: 600;
        font-size: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding-inline: 8px;
    }
    .detail-arrow-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 44px;
        height: 44px;
        border: 0;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid rgba(148, 163, 184, 0.45);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
    .detail-arrow-btn img {
        width: 20px;
        height: 20px;
        object-fit: contain;
    }
    .detail-arrow-btn.prev {
        left: 12px;
    }
    .detail-arrow-btn.next {
        right: 12px;
    }
    .detail-photo-counter {
        position: absolute;
        left: 12px;
        bottom: 12px;
        padding: 6px 10px;
        border-radius: 9px;
        background: rgba(16, 24, 40, 0.68);
        color: #fff;
        font-size: .9rem;
        font-weight: 600;
    }
    .listing-summary {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 340px;
        gap: 16px;
        align-items: start;
        margin-bottom: 18px;
    }
    .listing-meta-badge {
        display: inline-block;
        border-radius: 999px;
        background: #eef2f7;
        color: #334155;
        font-size: .8rem;
        font-weight: 700;
        padding: 6px 12px;
        margin-bottom: 8px;
    }
    .listing-price {
        color: #1f4da8;
        font-weight: 800;
        font-size: 2rem;
        line-height: 1.1;
        margin-bottom: 6px;
    }
    .listing-title {
        font-size: 1.45rem;
        font-weight: 700;
        margin-bottom: 8px;
    }
    .listing-location {
        color: #64748b;
        margin-bottom: 10px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .listing-location img {
        width: 16px;
        height: 16px;
        object-fit: contain;
        flex-shrink: 0;
    }
    .listing-updated {
        color: #64748b;
        font-size: .9rem;
    }
    .home-info-section {
        margin-top: 20px;
    }
    .home-info-title {
        font-size: 1.45rem;
        font-weight: 700;
        margin-bottom: 14px;
    }
    .home-info-section .section-title {
        font-size: 1.2rem;
        font-weight: 700;
        margin-bottom: 12px;
    }
    .home-info-block + .home-info-block {
        margin-top: 14px;
        padding-top: 14px;
        border-top: 1px solid #e2e8f0;
    }
    .spec-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px 22px;
    }
    .spec-item {
        display: grid;
        grid-template-columns: 26px 1fr;
        gap: 8px;
        font-size: 1.03rem;
        line-height: 1.4;
        margin: 0;
        align-items: start;
    }
    .spec-item-icon {
        width: 22px;
        height: 22px;
        object-fit: contain;
        margin-top: 1px;
    }
    .spec-label {
        color: #64748b;
        font-weight: 600;
        margin-right: 6px;
    }
    .spec-value {
        color: #0f172a;
        font-weight: 600;
    }
    .facility-category + .facility-category {
        margin-top: 12px;
    }
    .facility-category-title {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 8px;
    }
    .facility-list {
        margin: 0;
        padding-left: 18px;
        column-count: 2;
        column-gap: 24px;
    }
    .facility-list li {
        break-inside: avoid;
        margin-bottom: 4px;
        font-size: 16px;
        line-height: 1.5;
    }
    .facility-item-value {
        font-size: 14px;
        color: #475569;
    }
    #property-map {
        width: 100%;
        height: 320px;
        background: #e2e8f0;
        border: 1px solid #dbe3ee;
        border-radius: 12px;
        overflow: hidden;
    }
    .location-coordinates {
        color: #64748b;
        font-size: .85rem;
        margin-top: 8px;
        margin-bottom: 8px;
    }
    .location-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px 16px;
        margin-bottom: 12px;
    }
    .location-item {
        min-width: 0;
    }
    .location-item-full {
        grid-column: 1 / -1;
    }
    .location-label {
        color: #64748b;
        font-weight: 600;
        font-size: .85rem;
        margin-bottom: 2px;
    }
    .location-value {
        color: #0f172a;
        word-break: break-word;
        font-size: 1rem;
        line-height: 1.35;
    }
    .agent-contact-card {
        border: 1px solid #dbe3ee;
        border-radius: 14px;
        background: #fff;
        overflow: hidden;
    }
    .agent-contact-header {
        padding: 16px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        align-items: flex-start;
        gap: 14px;
    }
    .agent-contact-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: #eef2f7;
        flex-shrink: 0;
        object-fit: cover;
        border: 1px solid #dbe3ee;
    }
    .agent-contact-meta {
        min-width: 0;
    }
    .agent-contact-name {
        font-weight: 700;
        font-size: 1.05rem;
        line-height: 1.2;
        margin-bottom: 3px;
    }
    .agent-contact-role {
        color: #64748b;
        font-size: .95rem;
        margin-bottom: 6px;
    }
    .agent-contact-email {
        color: #64748b;
        font-size: .95rem;
        line-height: 1.35;
        margin-bottom: 2px;
    }
    .agent-contact-actions {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0;
        padding: 12px 16px 16px;
    }
    .agent-detail-page .agent-contact-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }
    .agent-contact-actions .btn {
        height: 56px;
        width: 100%;
        padding: 0 16px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        text-align: center;
    }
    .agent-detail-page .agent-contact-actions .btn {
        min-height: 40px;
        height: auto;
        width: auto;
        flex: 1 1 calc(50% - 4px);
    }
    .detail-sidebar {
        position: static;
        align-self: start;
        display: grid;
        gap: 12px;
    }
    .apply-rent-card {
        border: 1px solid #dbe3ee;
        border-radius: 14px;
        background: #fff;
        overflow: hidden;
    }
    .apply-rent-body {
        padding: 16px;
    }
    .apply-rent-title {
        font-size: 1.45rem;
        line-height: 1.2;
        margin: 0 0 10px;
    }
    .apply-rent-note {
        color: #64748b;
        font-size: .92rem;
        margin: 0 0 12px;
        line-height: 1.4;
    }
    .apply-rent-card .btn {
        height: 56px;
        width: 100%;
        padding: 0 16px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        text-align: center;
    }
    .agent-detail-page .apply-rent-card .btn {
        min-height: 48px;
        height: auto;
        width: auto;
    }
    @media (max-width: 991px) {
        .detail-gallery-grid {
            grid-template-columns: 1fr;
        }
        .detail-main-photo {
            height: 420px;
        }
        .detail-side-stack {
            height: auto;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            grid-template-rows: 120px;
        }
        .listing-summary {
            grid-template-columns: 1fr;
        }
        .spec-grid {
            grid-template-columns: 1fr;
        }
        .facility-list {
            column-count: 1;
        }
        .location-grid {
            grid-template-columns: 1fr;
            gap: 10px;
        }
        .detail-sidebar {
            position: static;
        }
    }
    @media (max-width: 575px) {
        .detail-main-photo {
            height: 320px;
        }
        .detail-side-stack {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            grid-template-rows: repeat(2, 110px);
        }
        .agent-detail-page .agent-contact-actions .btn {
            flex: 1 1 100%;
        }
    }
</style>
