/**
 * SiteTour — lightweight, dependency-free spotlight tour.
 * Dims the page with four rectangles framing a gap over the target element,
 * plus a positioned tooltip card. No library, no build step.
 * Steps: [{ target: '#selector'|null, title, body }]
 */
(function () {
    if (window.SiteTour) return;

    const STYLE_ID = 'site-tour-styles';

    function injectStyles() {
        if (document.getElementById(STYLE_ID)) return;
        const style = document.createElement('style');
        style.id = STYLE_ID;
        style.textContent = `
            .st-dim { position: fixed; background: rgba(10,26,47,0.65); z-index: 99998; pointer-events: auto; }
            .st-ring { position: fixed; z-index: 99998; border: 2px solid #D4AF37; border-radius: 10px; box-shadow: 0 0 0 4px rgba(212,175,55,0.25); pointer-events: none; }
            .st-card { position: fixed; z-index: 100000; background: #fff; border-radius: 14px; box-shadow: 0 20px 45px rgba(10,26,47,0.35); padding: 18px 20px; width: 320px; max-width: 88vw; font-family: Inter, system-ui, sans-serif; }
            .st-card h3 { margin: 0 0 6px; font-size: 15px; font-weight: 700; color: #0A1A2F; }
            .st-card p { margin: 0; font-size: 13px; line-height: 1.55; color: #475569; }
            .st-footer { display: flex; align-items: center; justify-content: space-between; margin-top: 14px; }
            .st-progress { font-size: 11px; color: #94a3b8; font-weight: 600; }
            .st-btns { display: flex; gap: 8px; }
            .st-btn { border: none; cursor: pointer; font-size: 12px; font-weight: 700; padding: 6px 12px; border-radius: 8px; font-family: inherit; }
            .st-btn-next { background: linear-gradient(135deg,#D4AF37,#C9A227); color: #0A1A2F; }
            .st-btn-prev { background: #f1f5f9; color: #334155; }
            .st-btn-skip { background: transparent; color: #94a3b8; }
            .st-btn-skip:hover { color: #64748b; }
        `;
        document.head.appendChild(style);
    }

    class SiteTour {
        constructor(steps, opts = {}) {
            this.steps = (steps || []).filter(function (s) {
                return !s.target || document.querySelector(s.target);
            });
            this.index = 0;
            this.storageKey = opts.storageKey || 'site_tour_seen';
            this.onFinish = opts.onFinish || null;
            this.els = {};
            this._reposition = this._reposition.bind(this);
        }

        start(force) {
            if (!this.steps.length) return;
            if (!force && window.localStorage.getItem(this.storageKey)) return;
            injectStyles();
            this.index = 0;
            this._mount();
            this._render();
            window.addEventListener('resize', this._reposition);
            window.addEventListener('scroll', this._reposition, true);
        }

        next() {
            if (this.index < this.steps.length - 1) {
                this.index++;
                this._render();
            } else {
                this.end(true);
            }
        }

        prev() {
            if (this.index > 0) {
                this.index--;
                this._render();
            }
        }

        end(markSeen) {
            if (markSeen) {
                try { window.localStorage.setItem(this.storageKey, '1'); } catch (e) {}
            }
            window.removeEventListener('resize', this._reposition);
            window.removeEventListener('scroll', this._reposition, true);
            this._unmount();
            if (this.onFinish) this.onFinish();
        }

        _mount() {
            const frag = document.createDocumentFragment();
            const dimTop = document.createElement('div'); dimTop.className = 'st-dim';
            const dimBottom = document.createElement('div'); dimBottom.className = 'st-dim';
            const dimLeft = document.createElement('div'); dimLeft.className = 'st-dim';
            const dimRight = document.createElement('div'); dimRight.className = 'st-dim';
            const ring = document.createElement('div'); ring.className = 'st-ring';
            const card = document.createElement('div'); card.className = 'st-card';
            [dimTop, dimBottom, dimLeft, dimRight, ring, card].forEach((el) => frag.appendChild(el));
            document.body.appendChild(frag);
            this.els = { dimTop, dimBottom, dimLeft, dimRight, ring, card };

            const onKey = (e) => {
                if (e.key === 'Escape') this.end(true);
                if (e.key === 'ArrowRight') this.next();
                if (e.key === 'ArrowLeft') this.prev();
            };
            document.addEventListener('keydown', onKey);
            this._onKey = onKey;
        }

        _unmount() {
            Object.values(this.els).forEach((el) => {
                if (el && el.parentNode) el.parentNode.removeChild(el);
            });
            this.els = {};
            if (this._onKey) document.removeEventListener('keydown', this._onKey);
        }

        _render() {
            const step = this.steps[this.index];
            const target = step.target ? document.querySelector(step.target) : null;

            if (target) {
                target.scrollIntoView({ block: 'center', behavior: 'smooth' });
                window.setTimeout(() => this._reposition(), 260);
            } else {
                this._reposition();
            }

            const card = this.els.card;
            const isLast = this.index === this.steps.length - 1;
            card.innerHTML =
                '<h3>' + step.title + '</h3>' +
                '<p>' + step.body + '</p>' +
                '<div class="st-footer">' +
                    '<span class="st-progress">Step ' + (this.index + 1) + ' of ' + this.steps.length + '</span>' +
                    '<div class="st-btns">' +
                        '<button type="button" class="st-btn st-btn-skip" data-act="skip">Skip</button>' +
                        (this.index > 0 ? '<button type="button" class="st-btn st-btn-prev" data-act="prev">Back</button>' : '') +
                        '<button type="button" class="st-btn st-btn-next" data-act="next">' + (isLast ? 'Done' : 'Next') + '</button>' +
                    '</div>' +
                '</div>';

            card.querySelector('[data-act="skip"]').onclick = () => this.end(true);
            card.querySelector('[data-act="next"]').onclick = () => this.next();
            const prevBtn = card.querySelector('[data-act="prev"]');
            if (prevBtn) prevBtn.onclick = () => this.prev();

            this._reposition();
        }

        _reposition() {
            const step = this.steps[this.index];
            if (!step) return;
            const target = step.target ? document.querySelector(step.target) : null;
            const vw = window.innerWidth;
            const vh = window.innerHeight;
            const { dimTop, dimBottom, dimLeft, dimRight, ring, card } = this.els;

            const setRect = (el, t, l, w, h) => {
                el.style.top = t + 'px'; el.style.left = l + 'px';
                el.style.width = Math.max(w, 0) + 'px'; el.style.height = Math.max(h, 0) + 'px';
            };

            if (!target) {
                setRect(dimTop, 0, 0, vw, vh);
                setRect(dimBottom, 0, 0, 0, 0);
                setRect(dimLeft, 0, 0, 0, 0);
                setRect(dimRight, 0, 0, 0, 0);
                ring.style.width = '0'; ring.style.height = '0';
                window.requestAnimationFrame(() => {
                    card.style.top = Math.max((vh / 2 - card.offsetHeight / 2), 12) + 'px';
                    card.style.left = (vw / 2 - card.offsetWidth / 2) + 'px';
                });
                return;
            }

            const rect = target.getBoundingClientRect();
            const pad = 6;
            const top = Math.max(rect.top - pad, 0);
            const left = Math.max(rect.left - pad, 0);
            const width = rect.width + pad * 2;
            const height = rect.height + pad * 2;
            const bottom = top + height;
            const right = left + width;

            // Four rectangles framing the target — the target's own rect is left
            // untouched (no dark overlay drawn over it), so it stays fully visible
            // regardless of where it sits in the page's stacking order.
            setRect(dimTop, 0, 0, vw, top);
            setRect(dimBottom, bottom, 0, vw, vh - bottom);
            setRect(dimLeft, top, 0, left, height);
            setRect(dimRight, top, right, vw - right, height);

            ring.style.top = top + 'px'; ring.style.left = left + 'px';
            ring.style.width = width + 'px'; ring.style.height = height + 'px';

            const cardW = card.offsetWidth || 320;
            const gap = 14;
            let cardTop = bottom + gap;
            let cardLeft = Math.min(Math.max(left, 12), vw - cardW - 12);

            window.requestAnimationFrame(() => {
                const cardH = card.offsetHeight || 120;
                if (cardTop + cardH > vh - 12) cardTop = Math.max(top - cardH - gap, 12);
                card.style.top = cardTop + 'px';
                card.style.left = cardLeft + 'px';
            });
        }
    }

    window.SiteTour = SiteTour;
})();
