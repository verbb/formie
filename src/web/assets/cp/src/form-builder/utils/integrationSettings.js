import { cloneDeep, get as getValue, set as setValue } from 'lodash-es';

const updateIntegrationSettingMapping = (settings, path, handle, value, fallbackMapping = {}) => {
    const nextSettings = cloneDeep(settings && typeof settings === 'object' && !Array.isArray(settings) ? settings : {});
    const currentMapping = getValue(nextSettings, path);
    const mapping = currentMapping && typeof currentMapping === 'object' && !Array.isArray(currentMapping)
        ? currentMapping
        : fallbackMapping;

    setValue(nextSettings, path, {
        ...(mapping || {}),
        [handle]: value,
    });

    return nextSettings;
};

export { updateIntegrationSettingMapping };
