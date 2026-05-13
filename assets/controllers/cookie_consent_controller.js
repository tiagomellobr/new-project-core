import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['banner'];
    static values = { gaId: String };

    connect() {
        const consent = localStorage.getItem('lgpd_consent');
        if (!consent) {
            this.bannerTarget.classList.remove('hidden');
        } else if (consent === 'accepted') {
            this._loadGA();
        }

        this._turboHandler = () => this._trackPageView();
        document.addEventListener('turbo:load', this._turboHandler);
    }

    disconnect() {
        document.removeEventListener('turbo:load', this._turboHandler);
    }

    accept() {
        localStorage.setItem('lgpd_consent', 'accepted');
        this._loadGA();
        this._dismiss();
    }

    decline() {
        localStorage.setItem('lgpd_consent', 'declined');
        this._dismiss();
    }

    _loadGA() {
        const id = this.gaIdValue;
        if (!id || window.gaLoaded) return;
        window.gaLoaded = true;

        window.dataLayer = window.dataLayer || [];
        window.gtag = function() { window.dataLayer.push(arguments); };
        window.gtag('js', new Date());
        window.gtag('config', id, { send_page_view: false });

        const script = document.createElement('script');
        script.async = true;
        script.src = `https://www.googletagmanager.com/gtag/js?id=${id}`;
        document.head.appendChild(script);

        this._trackPageView();
    }

    _trackPageView() {
        const id = this.gaIdValue;
        if (!id || !window.gtag || localStorage.getItem('lgpd_consent') !== 'accepted') return;
        window.gtag('event', 'page_view', {
            page_path: window.location.pathname + window.location.search,
            page_title: document.title,
        });
    }

    _dismiss() {
        this.bannerTarget.classList.add('opacity-0', 'translate-y-4');
        setTimeout(() => this.bannerTarget.classList.add('hidden'), 300);
    }
}
