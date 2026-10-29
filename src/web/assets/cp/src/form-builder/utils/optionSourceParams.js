// Defaults initialize missing parameters. A saved empty value is an intentional
// removal and must survive reopening or refreshing the provider settings.
export const applyOptionSourceParamDefaults = (params, fields, defaults) => {
    const next = { ...params };
    fields.forEach((field) => {
        const handle = String(field?.handle || '');
        if (!handle || Object.prototype.hasOwnProperty.call(next, handle)) {
            return;
        }
        if (defaults[handle] !== undefined && defaults[handle] !== null) {
            next[handle] = defaults[handle];
        }
    });
    return next;
};
