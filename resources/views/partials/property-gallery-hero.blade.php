<div class="mb-4">
    <div class="detail-gallery-grid {{ $hasSidePanel ? '' : 'single-photo' }}">
        <div class="detail-main-photo" id="detail-main-photo-wrap">
            <img src="{{ $initialMainPhoto }}"
                 alt="Main property photo"
                 id="detail-main-photo"
                 role="button"
                 tabindex="0"
                 aria-label="Open all photos">

            @if($photoUrls->count() > 1)
                <button type="button"
                        class="detail-arrow-btn prev"
                        id="detail-prev-photo"
                        aria-label="Previous photo">
                    <img src="{{ asset('icons/arrow-left-icon.svg') }}" alt="">
                </button>
                <button type="button"
                        class="detail-arrow-btn next"
                        id="detail-next-photo"
                        aria-label="Next photo">
                    <img src="{{ asset('icons/arrow-right-icon.svg') }}" alt="">
                </button>
            @endif
            <div class="detail-photo-counter" id="detail-photo-counter">1/{{ $photoUrls->count() }}</div>
        </div>

        @if($hasSidePanel)
            <div class="detail-side-stack" id="detail-side-stack" style="grid-template-rows: repeat({{ $sideSlotCount }}, minmax(0, 1fr));">
                @foreach($sideIndexes as $idx)
                    @php($thumbUrl = $photoUrls->get($idx))
                    @if($thumbUrl)
                        <button type="button"
                                class="detail-side-thumb"
                                data-photo-index="{{ $idx }}">
                            <img src="{{ $thumbUrl }}" alt="Property preview {{ $idx + 1 }}">
                        </button>
                    @endif
                @endforeach

                <a href="{{ $galleryUrl }}"
                   class="detail-side-thumb detail-see-all">
                    <img src="{{ $seeAllPreview }}" alt="{{ $seeAllAlt ?? 'See all photos' }}">
                    <span>{{ $seeAllLabel ?? 'See All' }}</span>
                </a>
            </div>
        @endif
    </div>
</div>
