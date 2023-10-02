import defer from './defer';

const ownProp = Object.prototype.hasOwnProperty;

export function createTurnstile() {
    const deferred = defer();

    // In order to handle multiple recaptchas on a page, store all renderers (promises)
    // in a central store. When reCAPTCHA is loaded, notify all promises that it's ready.
    if (!window.turnstileRenderers) {
        window.turnstileRenderers = [];
    }

    // Store the promise in our renderers store
    window.turnstileRenderers.push(deferred);

    return {
        notify() {
            // Be sure to notify all renderers that reCAPTCHA is ready, as soon as at least one is ready
            // As is - as soon as `window.turnstile` is available.
            for (let i = 0, len = window.turnstileRenderers.length; i < len; i++) {
                window.turnstileRenderers[i].resolve();
            }
        },

        wait() {
            return deferred.promise;
        },

        render(ele, options, cb) {
            this.wait().then(() => {
                cb(window.turnstile.render(ele, options));
            });
        },

        reset(widgetId) {
            if (typeof widgetId === 'undefined') {
                return;
            }

            this.assertLoaded();

            this.wait().then(() => { return window.turnstile.reset(widgetId); });
        },

        remove(widgetId) {
            if (typeof widgetId === 'undefined') {
                return;
            }

            this.assertLoaded();

            this.wait().then(() => { return window.turnstile.remove(widgetId); });
        },

        execute(widgetId) {
            if (typeof widgetId === 'undefined') {
                return;
            }

            this.assertLoaded();
            this.wait().then(() => { return window.turnstile.execute(widgetId); });
        },

        checkCaptchaLoad() {
            if (ownProp.call(window, 'turnstile') && ownProp.call(window.turnstile, 'render')) {
                this.notify();
            }
        },

        assertLoaded() {
            if (!deferred.resolved()) {
                throw new Error('Turnstile has not been loaded');
            }
        },
    };
}

export const turnstile = createTurnstile();

if (typeof window !== 'undefined') {
    window.formieTurnstileOnLoadCallback = turnstile.notify;
}
