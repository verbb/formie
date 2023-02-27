import defer from './defer';

const ownProp = Object.prototype.hasOwnProperty;

export function createHcaptcha() {
    const deferred = defer();

    // In order to handle multiple recaptchas on a page, store all renderers (promises)
    // in a central store. When reCAPTCHA is loaded, notify all promises that it's ready.
    if (!window.hcaptchaRenderers) {
        window.hcaptchaRenderers = [];
    }

    // Store the promise in our renderers store
    window.hcaptchaRenderers.push(deferred);

    return {
        notify() {
            // Be sure to notify all renderers that reCAPTCHA is ready, as soon as at least one is ready
            // As is - as soon as `window.hcaptcha` is available.
            for (let i = 0, len = window.hcaptchaRenderers.length; i < len; i++) {
                window.hcaptchaRenderers[i].resolve();
            }
        },

        wait() {
            return deferred.promise;
        },

        render(ele, options, cb) {
            this.wait().then(() => {
                cb(window.hcaptcha.render(ele, options));
            });
        },

        reset(widgetId) {
            if (typeof widgetId === 'undefined') {
                return;
            }

            this.assertLoaded();

            this.wait().then(() => { return window.hcaptcha.reset(widgetId); });
        },

        execute(widgetId) {
            if (typeof widgetId === 'undefined') {
                return;
            }

            this.assertLoaded();
            this.wait().then(() => { return window.hcaptcha.execute(widgetId); });
        },

        executeV3(siteKey) {
            if (typeof siteKey === 'undefined') {
                return;
            }

            this.assertLoaded();
            return window.hcaptcha.execute(siteKey);
        },

        checkRecaptchaLoad() {
            if (ownProp.call(window, 'hcaptcha') && ownProp.call(window.hcaptcha, 'render')) {
                this.notify();
            }
        },

        assertLoaded() {
            if (!deferred.resolved()) {
                throw new Error('ReCAPTCHA has not been loaded');
            }
        },
    };
}

export const hcaptcha = createHcaptcha();

if (typeof window !== 'undefined') {
    window.formieHcaptchaOnLoadCallback = hcaptcha.notify;
}
