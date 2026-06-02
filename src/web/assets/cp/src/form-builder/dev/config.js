const DEV_TOOLS_STORAGE_KEY = 'formie:formBuilder:devTools';

const DEFAULT_CONFIG = {
    enabled: false,
    mode: 'none', // none | fieldPreview | stressTest | existingFieldsStress
    stressPattern: '20x5x5',
    existingFieldsPattern: '100x5x24', // forms x pages x fields
    previewValueMode: 'normal', // normal | placeholder | default | empty
    autoOpenFirstField: false,
    autoOpenFirstNotification: false,
    autoOpenPageSettings: false,
    autoOpenExistingFields: false,
    showExpandedDropzoneHitboxes: false,
    showRowAndFieldIds: false,
    showDropzoneRegistryDebugPanel: false,
    showExpandedNestedDropzoneHitboxes: false,
    showNestedRowAndFieldIds: false,
};

const safeParse = (value, fallback) => {
    try {
        return JSON.parse(value);
    } catch (error) {
        return fallback;
    }
};

const normalizeConfig = (config = {}) => {
    const merged = { ...DEFAULT_CONFIG, ...(config || {}) };

    if (!['none', 'fieldPreview', 'stressTest', 'existingFieldsStress'].includes(merged.mode)) {
        merged.mode = 'none';
    }

    if (typeof merged.stressPattern !== 'string' || !merged.stressPattern.trim()) {
        merged.stressPattern = DEFAULT_CONFIG.stressPattern;
    }

    if (typeof merged.existingFieldsPattern !== 'string' || !merged.existingFieldsPattern.trim()) {
        merged.existingFieldsPattern = DEFAULT_CONFIG.existingFieldsPattern;
    }

    if (!['normal', 'placeholder', 'default', 'empty'].includes(merged.previewValueMode)) {
        merged.previewValueMode = DEFAULT_CONFIG.previewValueMode;
    }

    merged.autoOpenFirstField = Boolean(merged.autoOpenFirstField);
    merged.autoOpenFirstNotification = Boolean(merged.autoOpenFirstNotification);
    merged.autoOpenPageSettings = Boolean(merged.autoOpenPageSettings);
    merged.autoOpenExistingFields = Boolean(merged.autoOpenExistingFields);
    merged.showExpandedDropzoneHitboxes = Boolean(merged.showExpandedDropzoneHitboxes);
    merged.showRowAndFieldIds = Boolean(merged.showRowAndFieldIds);
    merged.showDropzoneRegistryDebugPanel = Boolean(merged.showDropzoneRegistryDebugPanel);
    merged.showExpandedNestedDropzoneHitboxes = Boolean(merged.showExpandedNestedDropzoneHitboxes);
    merged.showNestedRowAndFieldIds = Boolean(merged.showNestedRowAndFieldIds);
    merged.enabled = Boolean(merged.enabled);

    return merged;
};

const readFromStorage = () => {
    const raw = window.localStorage.getItem(DEV_TOOLS_STORAGE_KEY);
    if (!raw) {
        return DEFAULT_CONFIG;
    }

    return normalizeConfig(safeParse(raw, DEFAULT_CONFIG));
};

const writeToStorage = (config) => {
    const normalized = normalizeConfig(config);
    window.localStorage.setItem(DEV_TOOLS_STORAGE_KEY, JSON.stringify(normalized));
};

const getDevToolsConfig = () => {
    return readFromStorage();
};

const setDevToolsConfig = (nextConfig) => {
    writeToStorage(nextConfig);
};

export {
    DEV_TOOLS_STORAGE_KEY,
    DEFAULT_CONFIG,
    getDevToolsConfig,
    setDevToolsConfig,
};
