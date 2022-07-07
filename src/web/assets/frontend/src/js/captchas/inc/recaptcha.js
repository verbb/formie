import defer from './defer';

const ownProp = Object.prototype.hasOwnProperty;

export function createRecaptcha(enterprise = false) {
    const deferred = defer();

    // In order to handle multiple recaptchas on a page, store all renderers (promises)
    // in a central store. When reCAPTCHA is loaded, notify all promises that it's ready.
    if (!window.recaptchaRenderers) {
        window.recaptchaRenderers = [];
    }

    // Store the promise in our renderers store
    window.recaptchaRenderers.push(deferred);

    return {
        notify() {
            // Be sure to notify all renderers that reCAPTCHA is ready, as soon as at least one is ready
            // As is - as soon as `window.grecaptcha` is available.
            for (let i = 0, len = window.recaptchaRenderers.length; i < len; i++) {
                window.recaptchaRenderers[i].resolve();
            }
        },

        wait() {
            return deferred.promise;
        },

        render(ele, options, cb) {
            this.wait().then(() => {
                if (enterprise) {
                    cb(window.grecaptcha.enterprise.render(ele, options));
                } else {
                    cb(window.grecaptcha.render(ele, options));
                }
            });
        },

        reset(widgetId) {
            if (typeof widgetId === 'undefined') {
                return;
            }

            this.assertLoaded();

            if (enterprise) {
                this.wait().then(() => { return window.grecaptcha.enterprise.reset(widgetId); });
            } else {
                this.wait().then(() => { return window.grecaptcha.reset(widgetId); });
            }
        },

        execute(widgetId) {
            if (typeof widgetId === 'undefined') {
                return;
            }

            this.assertLoaded();

            if (enterprise) {
                this.wait().then(() => { return window.grecaptcha.enterprise.execute(widgetId); });
            } else {
                this.wait().then(() => { return window.grecaptcha.execute(widgetId); });
            }
        },

        executeV3(siteKey) {
            if (typeof siteKey === 'undefined') {
                return;
            }

            this.assertLoaded();
            return window.grecaptcha.execute(siteKey);
        },

        checkRecaptchaLoad() {
            if (ownProp.call(window, 'grecaptcha') && ownProp.call(window.grecaptcha, 'render')) {
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

export const recaptcha = createRecaptcha();
export const recaptchaEnterprise = createRecaptcha(true);

if (typeof window !== 'undefined') {
    window.formieRecaptchaOnLoadCallback = recaptcha.notify;
}
