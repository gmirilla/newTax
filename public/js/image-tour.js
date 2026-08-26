/**
 * ImageTour — lightweight, dependency-free screenshot carousel.
 * Full-screen lightbox stepping through { src, title, body } slides,
 * with a persistent CTA. No library, no build step.
 */
(function () {
    if (window.ImageTour) return;

    const STYLE_ID = 'image-tour-styles';

    function injectStyles() {
        if (document.getElementById(STYLE_ID)) return;
        const style = document.createElement('style');
        style.id = STYLE_ID;
        style.textContent = `
            .it-overlay { position: fixed; inset: 0; z-index: 100000; background: rgba(10,26,47,0.94); display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 24px; font-family: Inter, system-ui, sans-serif; }
            .it-close { position: absolute; top: 20px; right: 24px; width: 40px; height: 40px; border-radius: 999px; background: rgba(255,255,255,0.08); border: none; color: #fff; font-size: 20px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.15s; }
            .it-close:hover { background: rgba(255,255,255,0.18); }
            .it-stage { position: relative; display: flex; align-items: center; justify-content: center; width: 100%; max-width: 1000px; }
            .it-img-wrap { position: relative; width: 100%; border-radius: 14px; overflow: hidden; box-shadow: 0 30px 70px rgba(0,0,0,0.5); background: #0d1b2f; }
            .it-img-wrap img { display: block; width: 100%; height: auto; max-height: 62vh; object-fit: contain; background: #fff; }
            .it-arrow { flex-shrink: 0; width: 44px; height: 44px; border-radius: 999px; background: rgba(255,255,255,0.08); border: none; color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.15s; margin: 0 14px; }
            .it-arrow:hover:not(:disabled) { background: rgba(255,255,255,0.2); }
            .it-arrow:disabled { opacity: 0.25; cursor: default; }
            .it-caption { max-width: 640px; text-align: center; margin-top: 22px; }
            .it-caption h3 { color: #fff; font-size: 18px; font-weight: 800; margin: 0 0 8px; font-family: Manrope, Inter, sans-serif; }
            .it-caption p { color: #cbd5e1; font-size: 14px; line-height: 1.6; margin: 0; }
            .it-footer { margin-top: 26px; display: flex; flex-direction: column; align-items: center; gap: 14px; }
            .it-dots { display: flex; gap: 6px; }
            .it-dot { width: 7px; height: 7px; border-radius: 999px; background: rgba(255,255,255,0.25); border: none; padding: 0; cursor: pointer; transition: background 0.15s, transform 0.15s; }
            .it-dot.active { background: #D4AF37; transform: scale(1.3); }
            .it-cta { display: inline-flex; align-items: center; gap: 8px; background: linear-gradient(135deg,#D4AF37,#C9A227); color: #0A1A2F; font-weight: 700; font-size: 14px; padding: 10px 22px; border-radius: 10px; text-decoration: none; }
            @media (max-width: 640px) { .it-arrow { width: 36px; height: 36px; margin: 0 8px; } .it-img-wrap img { max-height: 46vh; } }
        `;
        document.head.appendChild(style);
    }

    class ImageTour {
        constructor(slides, opts = {}) {
            this.slides = slides || [];
            this.index = 0;
            this.ctaHref = opts.ctaHref || '#';
            this.ctaLabel = opts.ctaLabel || 'Start Free Trial';
        }

        start() {
            if (!this.slides.length) return;
            injectStyles();
            this.index = 0;
            this._mount();
            this._render();
        }

        next() { if (this.index < this.slides.length - 1) { this.index++; this._render(); } }
        prev() { if (this.index > 0) { this.index--; this._render(); } }
        goTo(i) { this.index = i; this._render(); }

        close() {
            document.removeEventListener('keydown', this._onKey);
            if (this.el && this.el.parentNode) this.el.parentNode.removeChild(this.el);
            document.body.style.overflow = '';
        }

        _mount() {
            const overlay = document.createElement('div');
            overlay.className = 'it-overlay';
            overlay.innerHTML = `
                <button type="button" class="it-close" data-act="close" aria-label="Close">&times;</button>
                <div class="it-stage">
                    <button type="button" class="it-arrow" data-act="prev" aria-label="Previous">&larr;</button>
                    <div class="it-img-wrap"><img alt=""></div>
                    <button type="button" class="it-arrow" data-act="next" aria-label="Next">&rarr;</button>
                </div>
                <div class="it-caption"><h3></h3><p></p></div>
                <div class="it-footer">
                    <div class="it-dots"></div>
                    <a class="it-cta" data-act="cta" href="${this.ctaHref}">${this.ctaLabel}</a>
                </div>
            `;
            document.body.appendChild(overlay);
            document.body.style.overflow = 'hidden';
            this.el = overlay;

            overlay.addEventListener('click', (e) => {
                const act = e.target.closest('[data-act]')?.dataset.act;
                if (act === 'close') this.close();
                if (act === 'prev') this.prev();
                if (act === 'next') this.next();
                if (e.target === overlay) this.close();
            });

            const dots = overlay.querySelector('.it-dots');
            this.slides.forEach((_, i) => {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'it-dot';
                dot.addEventListener('click', () => this.goTo(i));
                dots.appendChild(dot);
            });

            this._onKey = (e) => {
                if (e.key === 'Escape') this.close();
                if (e.key === 'ArrowRight') this.next();
                if (e.key === 'ArrowLeft') this.prev();
            };
            document.addEventListener('keydown', this._onKey);
        }

        _render() {
            const slide = this.slides[this.index];
            const img = this.el.querySelector('.it-img-wrap img');
            img.src = slide.src;
            img.alt = slide.title;
            this.el.querySelector('.it-caption h3').textContent = slide.title;
            this.el.querySelector('.it-caption p').textContent = slide.body;
            this.el.querySelector('[data-act="prev"]').disabled = this.index === 0;
            this.el.querySelector('[data-act="next"]').disabled = this.index === this.slides.length - 1;
            [...this.el.querySelectorAll('.it-dot')].forEach((d, i) => d.classList.toggle('active', i === this.index));
        }
    }

    window.ImageTour = ImageTour;
})();
