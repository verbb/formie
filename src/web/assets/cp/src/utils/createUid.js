// `crypto.randomUUID()` is only available in secure contexts (HTTPS/localhost).
// Craft CP can be served over plain HTTP on LAN hostnames, so fall back when needed.
const createUid = () => {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }

    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (char) => {
        const random = Math.floor(Math.random() * 16);
        const value = char === 'x' ? random : ((random & 0x3) | 0x8);

        return value.toString(16);
    });
};

export { createUid };
