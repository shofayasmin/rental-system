<script>
    (function () {
        const photoUrls = @json($photoUrls->values()->all());
        const photoGalleryBaseUrl = @json($photoGalleryBaseUrl);
        const mainPhoto = document.getElementById('detail-main-photo');
        const counter = document.getElementById('detail-photo-counter');
        const prevBtn = document.getElementById('detail-prev-photo');
        const nextBtn = document.getElementById('detail-next-photo');
        const sideThumbButtons = Array.from(document.querySelectorAll('.detail-side-thumb[data-photo-index]'));
        const mainWrap = document.getElementById('detail-main-photo-wrap');

        if (!mainPhoto || !counter || !mainWrap || photoUrls.length === 0) {
            return;
        }

        let activeIndex = 0;
        let touchStartX = null;
        let wheelLock = false;

        const normalizeIndex = (index) => {
            if (index < 0) {
                return photoUrls.length - 1;
            }
            if (index >= photoUrls.length) {
                return 0;
            }
            return index;
        };

        const render = () => {
            mainPhoto.src = photoUrls[activeIndex];
            counter.textContent = `${activeIndex + 1}/${photoUrls.length}`;

            sideThumbButtons.forEach((btn) => {
                const idx = Number(btn.dataset.photoIndex);
                btn.classList.toggle('active', idx === activeIndex);
            });
        };

        const goTo = (index) => {
            activeIndex = normalizeIndex(index);
            render();
        };

        const openGallery = () => {
            window.location.href = `${photoGalleryBaseUrl}?photo=${activeIndex}`;
        };

        if (prevBtn && nextBtn && photoUrls.length > 1) {
            prevBtn.addEventListener('click', () => goTo(activeIndex - 1));
            nextBtn.addEventListener('click', () => goTo(activeIndex + 1));
        }

        mainPhoto.addEventListener('click', openGallery);
        mainPhoto.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openGallery();
            }
        });

        sideThumbButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                const idx = Number(btn.dataset.photoIndex);
                goTo(idx);
            });
        });

        if (photoUrls.length > 1) {
            mainWrap.addEventListener('touchstart', (event) => {
                touchStartX = event.changedTouches[0]?.clientX ?? null;
            }, { passive: true });

            mainWrap.addEventListener('touchend', (event) => {
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

            mainWrap.addEventListener('wheel', (event) => {
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
        }

        render();
    })();
</script>
