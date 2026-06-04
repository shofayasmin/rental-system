@extends('layouts.app')

@section('content')
@php
    $orderedPhotos = $property->photos
        ->sortBy(function ($photo) {
            return preg_match('/^front[_-]/i', basename((string) $photo->path)) === 1 ? 0 : 1;
        })
        ->values();

    $photoUrls = $orderedPhotos->map(function ($photo) {
        return \Illuminate\Support\Facades\Storage::disk('public')->exists($photo->path)
            ? asset('storage/' . $photo->path)
            : asset('images/property-placeholder.svg');
    })->values();

    if ($photoUrls->isEmpty()) {
        $photoUrls = collect([asset('images/property-placeholder.svg')]);
    }

    $initialPhotoIndex = max(0, min((int) request('photo', 0), max($photoUrls->count() - 1, 0)));
@endphp

<style>
    .tenant-breadcrumb .breadcrumb {
        margin-bottom: 0;
    }
    .tenant-breadcrumb .breadcrumb-item,
    .tenant-breadcrumb .breadcrumb-item a {
        color: #64748b;
        font-size: .9rem;
        text-decoration: none;
    }
    .tenant-breadcrumb .breadcrumb-item.active {
        color: #334155;
    }
    .photo-gallery-wrapper {
        max-width: 1200px;
        margin: 0 auto;
    }
    .photo-gallery-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 16px;
    }
    .photo-gallery-grid {
        display: grid;
        grid-template-columns: repeat(12, minmax(0, 1fr));
        gap: 12px;
    }
    .photo-gallery-item {
        border: 0;
        background: #f3f5f9;
        padding: 0;
        border-radius: 12px;
        overflow: hidden;
        cursor: pointer;
        min-height: 180px;
    }
    .photo-gallery-item img {
        width: 100%;
        height: 100%;
        min-height: 180px;
        object-fit: cover;
        display: block;
    }
    .photo-gallery-item.large {
        grid-column: span 6;
        min-height: 290px;
    }
    .photo-gallery-item.large img {
        min-height: 290px;
    }
    .photo-gallery-item.small {
        grid-column: span 4;
    }
    .photo-lightbox {
        position: fixed;
        inset: 0;
        background: rgba(5, 9, 20, 0.96);
        z-index: 1300;
        display: none;
        flex-direction: column;
    }
    .photo-lightbox.open {
        display: flex;
    }
    .photo-lightbox-topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 14px;
    }
    .photo-lightbox-close {
        border: 0;
        background: rgba(255, 255, 255, 0.14);
        color: #fff;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        font-size: 1.5rem;
        line-height: 1;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
    .photo-lightbox-main {
        flex: 1;
        min-height: 0;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 8px 64px 14px;
    }
    .photo-lightbox-image-wrap {
        width: min(92vw, 1580px);
        height: min(73vh, 980px);
        border-radius: 20px;
        overflow: hidden;
        background: #111a2e;
    }
    .photo-lightbox-image-wrap img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }
    .photo-lightbox-arrow {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 58px;
        height: 58px;
        border: 0;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid rgba(148, 163, 184, 0.45);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
    .photo-lightbox-arrow img {
        width: 26px;
        height: 26px;
        object-fit: contain;
    }
    .photo-lightbox-arrow.prev {
        left: 14px;
    }
    .photo-lightbox-arrow.next {
        right: 14px;
    }
    .photo-lightbox-counter {
        position: absolute;
        left: 78px;
        bottom: 24px;
        color: #fff;
        font-weight: 700;
        font-size: 1.1rem;
        background: rgba(7, 12, 25, 0.7);
        border-radius: 9px;
        padding: 7px 10px;
    }
    .photo-lightbox-strip {
        flex-shrink: 0;
        padding: 8px 16px 16px;
        overflow-x: auto;
        white-space: nowrap;
        display: flex;
        gap: 8px;
        scrollbar-width: thin;
    }
    .photo-lightbox-thumb {
        border: 2px solid transparent;
        background: transparent;
        padding: 0;
        border-radius: 8px;
        overflow: hidden;
        width: 156px;
        height: 100px;
        flex: 0 0 auto;
        cursor: pointer;
    }
    .photo-lightbox-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .photo-lightbox-thumb.active {
        border-color: #4b86ff;
    }
    @media (max-width: 991px) {
        .photo-gallery-item.large {
            grid-column: span 12;
        }
        .photo-gallery-item.small {
            grid-column: span 6;
        }
        .photo-lightbox-main {
            padding-inline: 8px;
        }
        .photo-lightbox-arrow {
            width: 46px;
            height: 46px;
        }
        .photo-lightbox-arrow.prev {
            left: 10px;
        }
        .photo-lightbox-arrow.next {
            right: 10px;
        }
    }
    @media (max-width: 575px) {
        .photo-gallery-item.small {
            grid-column: span 12;
        }
        .photo-lightbox-image-wrap {
            height: min(62vh, 560px);
        }
        .photo-lightbox-thumb {
            width: 120px;
            height: 78px;
        }
    }
</style>

<div class="container">
    <div class="photo-gallery-wrapper">
        <nav class="tenant-breadcrumb mb-3" aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/houses">Browse Houses</a></li>
                <li class="breadcrumb-item"><a href="/houses/{{ $property->id }}">Property Detail</a></li>
                <li class="breadcrumb-item active" aria-current="page">Gallery</li>
            </ol>
        </nav>

        <div class="photo-gallery-header">
            <div>
                <h4 class="mb-0">{{ $property->title }}</h4>
                <div class="text-primary fw-semibold">Rp {{ number_format((float) $property->rent_price, 0, ',', '.') }}/month</div>
            </div>
            <div class="text-muted align-self-end">{{ $photoUrls->count() }} photos</div>
        </div>

        <div class="photo-gallery-grid">
            @foreach($photoUrls as $index => $photoUrl)
                @php
                    $sizeClass = $index < 2 ? 'large' : 'small';
                @endphp
                <button type="button"
                        class="photo-gallery-item {{ $sizeClass }}"
                        data-open-lightbox-index="{{ $index }}">
                    <img src="{{ $photoUrl }}" alt="Property photo {{ $index + 1 }}">
                </button>
            @endforeach
        </div>
    </div>
</div>

<div class="photo-lightbox" id="photo-lightbox" aria-hidden="true">
    <div class="photo-lightbox-topbar">
        <div class="text-white fw-semibold">{{ $property->title }}</div>
        <button type="button" class="photo-lightbox-close" id="photo-lightbox-close" aria-label="Close gallery">×</button>
    </div>

    <div class="photo-lightbox-main" id="photo-lightbox-main">
        <button type="button" class="photo-lightbox-arrow prev" id="photo-lightbox-prev" aria-label="Previous photo">
            <img src="{{ asset('icons/arrow-left-icon.svg') }}" alt="">
        </button>
        <div class="photo-lightbox-image-wrap">
            <img src="{{ $photoUrls[$initialPhotoIndex] }}" alt="Selected property photo" id="photo-lightbox-image">
        </div>
        <button type="button" class="photo-lightbox-arrow next" id="photo-lightbox-next" aria-label="Next photo">
            <img src="{{ asset('icons/arrow-right-icon.svg') }}" alt="">
        </button>

        <div class="photo-lightbox-counter" id="photo-lightbox-counter">
            {{ $initialPhotoIndex + 1 }}/{{ $photoUrls->count() }}
        </div>
    </div>

    <div class="photo-lightbox-strip" id="photo-lightbox-strip">
        @foreach($photoUrls as $index => $photoUrl)
            <button type="button"
                    class="photo-lightbox-thumb {{ $index === $initialPhotoIndex ? 'active' : '' }}"
                    data-lightbox-thumb-index="{{ $index }}"
                    aria-label="Open photo {{ $index + 1 }}">
                <img src="{{ $photoUrl }}" alt="Thumbnail {{ $index + 1 }}">
            </button>
        @endforeach
    </div>
</div>

<script>
    (function () {
        const photoUrls = @json($photoUrls->values()->all());
        if (!photoUrls.length) {
            return;
        }

        const modal = document.getElementById('photo-lightbox');
        const mainStage = document.getElementById('photo-lightbox-main');
        const image = document.getElementById('photo-lightbox-image');
        const counter = document.getElementById('photo-lightbox-counter');
        const prevBtn = document.getElementById('photo-lightbox-prev');
        const nextBtn = document.getElementById('photo-lightbox-next');
        const closeBtn = document.getElementById('photo-lightbox-close');
        const strip = document.getElementById('photo-lightbox-strip');
        const openButtons = Array.from(document.querySelectorAll('[data-open-lightbox-index]'));
        const thumbButtons = Array.from(document.querySelectorAll('[data-lightbox-thumb-index]'));

        const initialIndex = Number(@json($initialPhotoIndex)) || 0;
        const hasInitialOpen = (new URLSearchParams(window.location.search)).has('photo');

        let activeIndex = initialIndex;
        let touchStartX = null;
        let wheelLock = false;
        let previousActiveElement = null;

        const focusableSelector = [
            'button:not([disabled])',
            '[href]',
            'input:not([disabled])',
            'select:not([disabled])',
            'textarea:not([disabled])',
            '[tabindex]:not([tabindex="-1"])'
        ].join(',');

        const normalizeIndex = (index) => {
            if (index < 0) {
                return photoUrls.length - 1;
            }
            if (index >= photoUrls.length) {
                return 0;
            }
            return index;
        };

        const scrollThumbIntoView = (index) => {
            const target = thumbButtons.find((btn) => Number(btn.dataset.lightboxThumbIndex) === index);
            if (target) {
                target.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
            }
        };

        const render = () => {
            image.src = photoUrls[activeIndex];
            counter.textContent = `${activeIndex + 1}/${photoUrls.length}`;

            thumbButtons.forEach((btn) => {
                const idx = Number(btn.dataset.lightboxThumbIndex);
                btn.classList.toggle('active', idx === activeIndex);
            });

            scrollThumbIntoView(activeIndex);
        };

        const goTo = (index) => {
            activeIndex = normalizeIndex(index);
            render();
        };

        const focusablesInModal = () => {
            return Array.from(modal.querySelectorAll(focusableSelector))
                .filter((el) => el.offsetParent !== null || el === document.activeElement);
        };

        const openModal = (index) => {
            previousActiveElement = document.activeElement;
            goTo(index);
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';

            const focusables = focusablesInModal();
            (focusables[0] || closeBtn).focus();
        };

        const closeModal = () => {
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';

            if (previousActiveElement && typeof previousActiveElement.focus === 'function') {
                previousActiveElement.focus();
            }
        };

        openButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const idx = Number(btn.dataset.openLightboxIndex || 0);
                openModal(idx);
            });
        });

        thumbButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const idx = Number(btn.dataset.lightboxThumbIndex || 0);
                goTo(idx);
            });
        });

        prevBtn.addEventListener('click', () => goTo(activeIndex - 1));
        nextBtn.addEventListener('click', () => goTo(activeIndex + 1));
        closeBtn.addEventListener('click', closeModal);

        document.addEventListener('keydown', (event) => {
            if (!modal.classList.contains('open')) {
                return;
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                closeModal();
                return;
            }

            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                goTo(activeIndex - 1);
                return;
            }

            if (event.key === 'ArrowRight') {
                event.preventDefault();
                goTo(activeIndex + 1);
                return;
            }

            if (event.key !== 'Tab') {
                return;
            }

            const focusables = focusablesInModal();
            if (!focusables.length) {
                event.preventDefault();
                return;
            }

            const first = focusables[0];
            const last = focusables[focusables.length - 1];
            const active = document.activeElement;

            if (event.shiftKey && active === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && active === last) {
                event.preventDefault();
                first.focus();
            }
        });

        mainStage.addEventListener('touchstart', (event) => {
            touchStartX = event.changedTouches[0]?.clientX ?? null;
        }, { passive: true });

        mainStage.addEventListener('touchend', (event) => {
            if (touchStartX === null) {
                return;
            }

            const touchEndX = event.changedTouches[0]?.clientX ?? touchStartX;
            const delta = touchEndX - touchStartX;
            touchStartX = null;

            if (Math.abs(delta) < 40) {
                return;
            }

            if (delta < 0) {
                goTo(activeIndex + 1);
            } else {
                goTo(activeIndex - 1);
            }
        }, { passive: true });

        mainStage.addEventListener('wheel', (event) => {
            if (Math.abs(event.deltaX) < 12 || wheelLock) {
                return;
            }

            event.preventDefault();
            wheelLock = true;
            setTimeout(() => {
                wheelLock = false;
            }, 240);

            if (event.deltaX > 0) {
                goTo(activeIndex + 1);
            } else {
                goTo(activeIndex - 1);
            }
        }, { passive: false });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });

        render();

        if (hasInitialOpen) {
            openModal(initialIndex);
        }
    })();
</script>
@endsection
