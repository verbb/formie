export const generateHandle = function(sourceValue, handleCasing = 'camelCase', allowNonAlphaStart = false) {
    // Remove HTML tags
    let handle = sourceValue.replace('/<(.*?)>/g', '');

    // Remove inner-word punctuation
    // eslint-disable-next-line
    handle = handle.replace(/['"‘’“”\[\]\(\)\{\}:]/g, '');

    // Make it lowercase
    handle = handle.toLowerCase();

    // Convert extended ASCII characters to basic ASCII
    handle = Craft.asciiString(handle);

    if (!allowNonAlphaStart) {
        // Handle must start with a letter
        handle = handle.replace(/^[^a-z]+/, '');
    }

    // Get the "words"
    const words = Craft.filterArray(handle.split(/[^a-z0-9]+/));
    handle = '';

    if (handleCasing === 'snake') {
        return words.join('_');
    }

    if (handleCasing === 'kebab') {
        return words.join('-');
    }

    // Make it camelCase
    for (let i = 0; i < words.length; i++) {
        if (handleCasing !== 'pascal' && i === 0) {
            handle += words[i];
        } else {
            handle += words[i].charAt(0).toUpperCase() + words[i].substr(1);
        }
    }

    return handle;
};

export const getNextAvailableHandle = function(handleCollection, handle, suffix) {
    let newHandle = handle;

    if (suffix) {
        newHandle = handle + suffix;
    }

    if (handleCollection.includes(newHandle)) {
        return getNextAvailableHandle(handleCollection, handle, suffix + 1);
    }

    return newHandle;
};

export const generateKebab = function(sourceValue) {
    // Remove HTML tags
    let kebab = sourceValue.replace('/<(.*?)>/g', '');

    // Remove inner-word punctuation
    kebab = kebab.replace(/['"‘’“”[]\(\){}:]/g, '');

    // Make it lowercase
    kebab = kebab.toLowerCase();

    // Convert extended ASCII characters to basic ASCII
    kebab = Craft.asciiString(kebab);

    // Get the "words"
    const words = Craft.filterArray(kebab.split(/[^a-z0-9]+/));
    kebab = words.join('-');

    return kebab;
};

export const newId = function() {
    return `new${Math.floor(9999 * Math.random())}-${Math.floor(9999 * Math.random())}`;
};

export const getId = function(prefix = '') {
    return prefix + Craft.randomString(10);
};

export const token = function(length = 13) {
    return Math.random().toString(36).substring(2, length + 2);
};

export const has = function(ctx, prop) {
    return Object.prototype.hasOwnProperty.call(ctx, prop);
};

export const setId = function(o, id) {
    if (!has(o, '__id') || id) {
        return Object.defineProperty(o, '__id', Object.assign(Object.create(null), { value: id || token(9) }));
    }
    return o;
};

export const getDisplayName = function(type) {
    const parts = type.split('\\');
    return parts[parts.length - 1];
};

export const parseDate = function(date) {
    if (date === null) {
        return '';
    }

    if (typeof date === 'object') {
        if (has(date, 'jsDate')) {
            return date.jsDate;
        }

        if (has(date, 'date')) {
            return date.date;
        }
    }

    return date;
};
