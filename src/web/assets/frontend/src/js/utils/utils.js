export const isEmpty = function(obj) {
    return obj && Object.keys(obj).length === 0 && obj.constructor === Object;
};

export const toBoolean = function(val) {
    return !/^(?:f(?:alse)?|no?|0+)$/i.test(val) && !!val;
};

export const eventKey = function(eventName, namespace = null) {
    if (!namespace) {
        namespace = Math.random().toString(36).substr(2, 5);
    }

    return `${eventName}.${namespace}`;
};

export const t = function(string, replacements = {}) {
    if (window.FormieTranslations) {
        string = window.FormieTranslations[string] || string;
    }

    return string.replace(/{([a-zA-Z0-9]+)}/g, (match, p1) => {
        if (replacements[p1]) {
            return replacements[p1];
        }

        return match;
    });
};

export const ensureVariable = function(variable, timeout = 100000) {
    const start = Date.now();

    // Function to allow us to wait for a global variable to be available. Useful for third-party scripts.
    const waitForVariable = function(resolve, reject) {
        if (window[variable]) {
            resolve(window[variable]);
        } else if (timeout && (Date.now() - start) >= timeout) {
            reject(new Error('timeout'));
        } else {
            setTimeout(waitForVariable.bind(this, resolve, reject), 30);
        }
    };

    return new Promise(waitForVariable);
};

export const waitForElement = function(selector, $element) {
    $element = $element || document;

    return new Promise((resolve) => {
        if ($element.querySelector(selector)) {
            return resolve($element.querySelector(selector));
        }

        const observer = new MutationObserver((mutations) => {
            if ($element.querySelector(selector)) {
                observer.disconnect();
                resolve($element.querySelector(selector));
            }
        });

        observer.observe($element, {
            childList: true,
            subtree: true,
        });
    });
};

export const getScriptUrl = function($form, url) {
    const modifyScriptUrlEvent = new CustomEvent('modifyScriptUrl', {
        bubbles: true,
        detail: {
            url,
        },
    });

    $form.dispatchEvent(modifyScriptUrlEvent);

    return modifyScriptUrlEvent.detail.url;
};

export const getAjaxClient = function($form, method, url, async, user, password) {
    const client = new XMLHttpRequest();
    client.open(method, url, async, user, password);
    client.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    client.setRequestHeader('Accept', 'application/json');
    client.setRequestHeader('Cache-Control', 'no-cache');

    const modifyAjaxClientEvent = new CustomEvent('modifyAjaxClient', {
        bubbles: true,
        detail: {
            client,
        },
    });

    $form.dispatchEvent(modifyAjaxClientEvent);

    return modifyAjaxClientEvent.detail.client;
};
