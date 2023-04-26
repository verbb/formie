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
