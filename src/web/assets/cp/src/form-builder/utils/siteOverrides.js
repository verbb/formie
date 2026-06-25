import { cloneDeep } from 'lodash-es';

import useAppStore from '@form-builder/hooks/useAppStore';

const EMPTY_TRANSLATABLE_CONFIG = {
    form: [],
    formSettings: [],
    page: [],
    pageSettings: [],
    notification: [],
    fieldTypes: {},
    nestedKeys: ['options', 'columns'],
};

const getTranslatableConfig = () => {
    return {
        ...EMPTY_TRANSLATABLE_CONFIG,
        ...(useAppStore.getState().translatableProperties || {}),
    };
};

const getNestedKeys = () => {
    return getTranslatableConfig().nestedKeys || [];
};

const resolveFieldTranslatableProperties = (field) => {
    const fieldType = field?.type;
    const { fieldTypes } = getTranslatableConfig();

    if (fieldType && fieldTypes?.[fieldType]) {
        return fieldTypes[fieldType];
    }

    return [];
};

const getFieldDefinitionId = (field) => {
    const fieldId = field?.fieldId ?? field?.settings?.fieldId ?? field?.syncId ?? field?.settings?.syncId;

    if (fieldId === null || fieldId === undefined || String(fieldId).trim() === '') {
        return null;
    }

    return String(fieldId);
};

const getFieldStorageKey = (field) => {
    const reference = String(field?.reference ?? '').trim();

    if (reference) {
        return reference;
    }

    const uid = String(field?.uid ?? '').trim();

    return uid || null;
};

const getNestedLayoutRowSources = (field) => {
    if (!field || typeof field !== 'object') {
        return [];
    }

    const sources = [];

    if (Array.isArray(field?.settings?.rows)) {
        sources.push(field.settings.rows);
    }

    if (Array.isArray(field.rows) && field.rows !== field.settings?.rows) {
        sources.push(field.rows);
    }

    return sources;
};

const getNestedLayoutRows = (field) => {
    const sources = getNestedLayoutRowSources(field);

    return sources.length ? sources[sources.length - 1] : null;
};

const indexCollectedField = (fields, field) => {
    const storageKey = getFieldStorageKey(field);

    if (!storageKey) {
        return;
    }

    fields[storageKey] = field;
};

const resolveCollectedField = (fieldsByKey = {}, fieldKey, field = null) => {
    if (fieldsByKey[fieldKey]) {
        return fieldsByKey[fieldKey];
    }

    if (field && typeof field === 'object') {
        const reference = String(field?.reference ?? '').trim();
        const uid = String(field?.uid ?? '').trim();

        if (reference && fieldsByKey[reference]) {
            return fieldsByKey[reference];
        }

        if (uid && fieldsByKey[uid]) {
            return fieldsByKey[uid];
        }
    }

    for (const candidate of Object.values(fieldsByKey)) {
        if (!candidate || typeof candidate !== 'object') {
            continue;
        }

        const candidateReference = String(candidate?.reference ?? '').trim();
        const candidateUid = String(candidate?.uid ?? '').trim();

        if (fieldKey === candidateReference || fieldKey === candidateUid) {
            return candidate;
        }

        if (field && typeof field === 'object') {
            const fieldReference = String(field?.reference ?? '').trim();
            const fieldUid = String(field?.uid ?? '').trim();

            if (fieldReference !== '' && (fieldReference === candidateReference || fieldReference === candidateUid)) {
                return candidate;
            }

            if (fieldUid !== '' && (fieldUid === candidateReference || fieldUid === candidateUid)) {
                return candidate;
            }
        }
    }

    return {};
};

const getPageStorageKey = (page) => {
    const uid = String(page?.uid ?? '').trim();

    if (uid) {
        return uid;
    }

    const id = page?.id;

    if (id !== null && id !== undefined && String(id).trim() !== '') {
        return String(id);
    }

    return null;
};

const resolveCanonicalPage = (canonicalPages = [], page, pageIndex = -1) => {
    if (!Array.isArray(canonicalPages) || !page || typeof page !== 'object') {
        return null;
    }

    const pageId = page?.id;

    if (pageId !== null && pageId !== undefined && String(pageId).trim() !== '') {
        const matchById = canonicalPages.find((canonicalPage) => {
            return String(canonicalPage?.id) === String(pageId);
        });

        if (matchById) {
            return matchById;
        }
    }

    const uid = String(page?.uid ?? '').trim();

    if (uid) {
        const matchByUid = canonicalPages.find((canonicalPage) => {
            return String(canonicalPage?.uid ?? '') === uid;
        });

        if (matchByUid) {
            return matchByUid;
        }
    }

    const handle = String(page?._handle ?? page?.handle ?? '').trim();

    if (handle) {
        const matchByHandle = canonicalPages.find((canonicalPage) => {
            const canonicalHandle = String(canonicalPage?._handle ?? canonicalPage?.handle ?? '').trim();

            return canonicalHandle === handle;
        });

        if (matchByHandle) {
            return matchByHandle;
        }
    }

    if (pageIndex >= 0 && pageIndex < canonicalPages.length) {
        return canonicalPages[pageIndex];
    }

    return null;
};

const getNotificationStorageKey = (notification) => {
    const handle = String(notification?.handle ?? '').trim();

    if (handle) {
        return handle;
    }

    const uid = String(notification?.uid ?? '').trim();

    return uid || null;
};

const resolveFieldOverride = (fieldOverrides = {}, field) => {
    const fieldDefinitionId = getFieldDefinitionId(field);

    if (fieldDefinitionId && fieldOverrides[fieldDefinitionId]) {
        return fieldOverrides[fieldDefinitionId];
    }

    return null;
};

const resolvePageOverride = (pageOverrides = {}, page) => {
    const storageKey = getPageStorageKey(page);

    if (storageKey && pageOverrides[storageKey]) {
        return pageOverrides[storageKey];
    }

    const id = page?.id;

    if (id !== null && id !== undefined && pageOverrides[String(id)]) {
        return pageOverrides[String(id)];
    }

    const handle = String(page?._handle ?? page?.handle ?? '').trim();

    if (handle && pageOverrides[handle]) {
        return pageOverrides[handle];
    }

    return null;
};

const resolveNotificationOverride = (notificationOverrides = {}, notification) => {
    const storageKey = getNotificationStorageKey(notification);

    if (storageKey && notificationOverrides[storageKey]) {
        return notificationOverrides[storageKey];
    }

    const uid = String(notification?.uid ?? '').trim();

    return uid ? notificationOverrides[uid] : null;
};

const isOptionRow = (option) => {
    return option
        && typeof option === 'object'
        && !Object.prototype.hasOwnProperty.call(option, 'optgroup');
};

const resolveCanonicalOptionAtIndex = (canonicalOptions = [], index = -1) => {
    const canonical = canonicalOptions[index];

    return isOptionRow(canonical) ? canonical : null;
};

const resolveLegacyOptionOverride = (option, legacyOverrides = []) => {
    const canonicalLabel = String(option.label ?? '');

    return legacyOverrides.find((candidate) => {
        if (!isOptionRow(candidate)) {
            return false;
        }

        const candidateLabel = String(candidate.label ?? '');

        return canonicalLabel !== ''
            && (candidateLabel === canonicalLabel
                || candidateLabel.startsWith(`${canonicalLabel} `)
                || candidateLabel.startsWith(`${canonicalLabel}(`));
    }) || null;
};

const mergeOptionOverrides = (options = [], optionOverrides = []) => {
    const overridesByCanonicalValue = new Map(
        optionOverrides
            .filter((option) => isOptionRow(option))
            .map((option) => [String(option.value ?? ''), option]),
    );
    const legacyOverrides = optionOverrides.filter((option) => {
        if (!isOptionRow(option)) {
            return false;
        }

        return !options.some((canonicalOption) => {
            return isOptionRow(canonicalOption)
                && String(canonicalOption.value ?? '') === String(option.value ?? '');
        });
    });

    return options.map((option) => {
        if (!isOptionRow(option)) {
            return option;
        }

        const canonicalValue = String(option.value ?? '');
        let optionOverride = overridesByCanonicalValue.get(canonicalValue);

        if (!optionOverride) {
            optionOverride = resolveLegacyOptionOverride(option, legacyOverrides);
        }

        if (!optionOverride) {
            return option;
        }

        const merged = {
            ...option,
        };

        if (Object.prototype.hasOwnProperty.call(optionOverride, 'label')) {
            merged.label = optionOverride.label;
        }

        if (Object.prototype.hasOwnProperty.call(optionOverride, 'optionValue')) {
            merged.value = optionOverride.optionValue;
        } else if (
            Object.prototype.hasOwnProperty.call(optionOverride, 'value')
            && String(optionOverride.value ?? '') !== canonicalValue
        ) {
            // Legacy overrides stored the translated value in `value`.
            merged.value = optionOverride.value;
        }

        return merged;
    });
};

const mergeFieldArray = (field, override = {}) => {
    if (!field || typeof field !== 'object') {
        return field;
    }

    const merged = {
        ...field,
    };

    Object.entries(override).forEach(([key, value]) => {
        if (getNestedKeys().includes(key)) {
            return;
        }

        merged[key] = value;
    });

    if (getNestedKeys().includes('options') && Array.isArray(override.options) && Array.isArray(merged.options)) {
        merged.options = mergeOptionOverrides(merged.options, override.options);
    }

    if (getNestedKeys().includes('columns') && Array.isArray(override.columns) && Array.isArray(merged.columns)) {
        const overridesByHandle = new Map(
            override.columns
                .filter((column) => column && typeof column === 'object')
                .map((column) => [String(column.handle ?? ''), column]),
        );

        merged.columns = merged.columns.map((column) => {
            if (!column || typeof column !== 'object') {
                return column;
            }

            const handle = String(column.handle ?? '');
            const columnOverride = overridesByHandle.get(handle);

            if (!columnOverride || !Object.prototype.hasOwnProperty.call(columnOverride, 'heading')) {
                return column;
            }

            return {
                ...column,
                heading: columnOverride.heading,
            };
        });
    }

    return merged;
};

const mergePageFields = (page, fieldOverrides = {}) => {
    if (!page || typeof page !== 'object' || !Array.isArray(page.rows)) {
        return page;
    }

    return {
        ...page,
        rows: page.rows.map((row) => {
            if (!row || typeof row !== 'object' || !Array.isArray(row.fields)) {
                return row;
            }

            return {
                ...row,
                fields: row.fields.map((field) => {
                    if (!field || typeof field !== 'object') {
                        return field;
                    }

                    const override = resolveFieldOverride(fieldOverrides, field);

                    let mergedField = override ? mergeFieldArray(field, override) : field;

                    if (Array.isArray(field.rows)) {
                        mergedField = {
                            ...mergedField,
                            rows: field.rows.map((nestedRow) => {
                                if (!nestedRow || typeof nestedRow !== 'object' || !Array.isArray(nestedRow.fields)) {
                                    return nestedRow;
                                }

                                return {
                                    ...nestedRow,
                                    fields: nestedRow.fields.map((nestedField) => {
                                        const nestedOverride = resolveFieldOverride(fieldOverrides, nestedField);

                                        return nestedOverride
                                            ? mergeFieldArray(nestedField, nestedOverride)
                                            : nestedField;
                                    }),
                                };
                            }),
                        };
                    }

                    if (Array.isArray(field?.settings?.rows)) {
                        mergedField = {
                            ...mergedField,
                            settings: {
                                ...(mergedField.settings || {}),
                                rows: field.settings.rows.map((nestedRow) => {
                                    if (!nestedRow || typeof nestedRow !== 'object' || !Array.isArray(nestedRow.fields)) {
                                        return nestedRow;
                                    }

                                    return {
                                        ...nestedRow,
                                        fields: nestedRow.fields.map((nestedField) => {
                                            const nestedOverride = resolveFieldOverride(fieldOverrides, nestedField);

                                            return nestedOverride
                                                ? mergeFieldArray(nestedField, nestedOverride)
                                                : nestedField;
                                        }),
                                    };
                                }),
                            },
                        };
                    }

                    return mergedField;
                }),
            };
        }),
    };
};

const mergeSettings = (settings = {}, settingsOverrides = {}) => {
    const merged = {
        ...settings,
    };

    getTranslatableConfig().formSettings.forEach((key) => {
        if (Object.prototype.hasOwnProperty.call(settingsOverrides, key)) {
            merged[key] = settingsOverrides[key];
        }
    });

    return merged;
};

const mergePageSettings = (settings = {}, settingsOverrides = {}) => {
    const merged = {
        ...settings,
    };

    getTranslatableConfig().pageSettings.forEach((key) => {
        if (Object.prototype.hasOwnProperty.call(settingsOverrides, key)) {
            merged[key] = settingsOverrides[key];
        }
    });

    return merged;
};

const mergeNotifications = (notifications = [], notificationOverrides = {}) => {
    if (!Array.isArray(notifications)) {
        return notifications;
    }

    return notifications.map((notification) => {
        if (!notification || typeof notification !== 'object') {
            return notification;
        }

        const override = resolveNotificationOverride(notificationOverrides, notification);

        if (!override) {
            return notification;
        }

        const merged = {
            ...notification,
        };

        getTranslatableConfig().notification.forEach((key) => {
            if (Object.prototype.hasOwnProperty.call(override, key)) {
                merged[key] = override[key];
            }
        });

        return merged;
    });
};

const mergePages = (pages = [], pageOverrides = {}, fieldOverrides = {}) => {
    if (!Array.isArray(pages)) {
        return pages;
    }

    return pages.map((page) => {
        if (!page || typeof page !== 'object') {
            return page;
        }

        const pageOverride = resolvePageOverride(pageOverrides, page) || {};
        let mergedPage = page;

        if (Object.prototype.hasOwnProperty.call(pageOverride, 'label')) {
            mergedPage = {
                ...mergedPage,
                label: pageOverride.label,
            };
        }

        if (pageOverride.settings && typeof pageOverride.settings === 'object') {
            mergedPage = {
                ...mergedPage,
                settings: mergePageSettings(mergedPage.settings || {}, pageOverride.settings),
            };
        }

        return mergePageFields(mergedPage, fieldOverrides);
    });
};

export const mergeSiteOverridesIntoFormData = (canonicalData = {}, overrides = {}, fieldOverrides = {}) => {
    if (!canonicalData || typeof canonicalData !== 'object' || !overrides || typeof overrides !== 'object') {
        return canonicalData;
    }

    const merged = cloneDeep(canonicalData);
    const resolvedFieldOverrides = fieldOverrides && typeof fieldOverrides === 'object'
        ? fieldOverrides
        : {};

    getTranslatableConfig().form.forEach((key) => {
        if (Object.prototype.hasOwnProperty.call(overrides, key)) {
            merged[key] = overrides[key];
        }
    });

    if (overrides.settings && typeof overrides.settings === 'object') {
        merged.settings = mergeSettings(merged.settings || {}, overrides.settings);
    }

    if (Array.isArray(merged.pages)) {
        merged.pages = mergePages(
            merged.pages,
            overrides.pages || {},
            resolvedFieldOverrides,
        );
    }

    if (Array.isArray(merged.notifications)) {
        merged.notifications = mergeNotifications(merged.notifications, overrides.notifications || {});
    }

    return merged;
};

export const getSiteOverrideForSite = (overridesBySite = {}, siteId) => {
    if (!overridesBySite || typeof overridesBySite !== 'object') {
        return {};
    }

    return overridesBySite[String(siteId)] || overridesBySite[siteId] || {};
};

export const getFieldOverrideForSite = (fieldOverridesBySite = {}, siteId) => {
    if (!fieldOverridesBySite || typeof fieldOverridesBySite !== 'object') {
        return {};
    }

    return fieldOverridesBySite[String(siteId)] || fieldOverridesBySite[siteId] || {};
};


const normalizeComparableValue = (value) => {
    if (value === null || value === undefined) {
        return '';
    }

    if (typeof value === 'object') {
        try {
            return JSON.stringify(value);
        } catch (error) {
            return String(value);
        }
    }

    return String(value);
};

const isEmptyTranslatableValue = (value) => {
    if (value === null || value === undefined || value === '') {
        return true;
    }

    if (Array.isArray(value)) {
        return value.length === 0;
    }

    if (typeof value === 'object') {
        return Object.keys(value).length === 0;
    }

    return false;
};

const translatableValuesAreEquivalent = (canonicalValue, postedValue) => {
    if (normalizeComparableValue(canonicalValue) === normalizeComparableValue(postedValue)) {
        return true;
    }

    return isEmptyTranslatableValue(canonicalValue) && isEmptyTranslatableValue(postedValue);
};

const indexPagesByStorageKey = (pages = []) => {
    const indexed = {};

    pages.forEach((page) => {
        if (!page || typeof page !== 'object') {
            return;
        }

        const storageKey = getPageStorageKey(page);

        if (storageKey) {
            indexed[storageKey] = page;
        }
    });

    return indexed;
};

const indexNotificationsByStorageKey = (notifications = []) => {
    const indexed = {};

    notifications.forEach((notification) => {
        if (!notification || typeof notification !== 'object') {
            return;
        }

        const storageKey = getNotificationStorageKey(notification);

        if (storageKey) {
            indexed[storageKey] = notification;
        }
    });

    return indexed;
};

const collectFieldsFromPage = (page, fields = {}) => {
    if (!page || typeof page !== 'object' || !Array.isArray(page.rows)) {
        return fields;
    }

    page.rows.forEach((row) => {
        if (!row || typeof row !== 'object' || !Array.isArray(row.fields)) {
            return;
        }

        row.fields.forEach((field) => {
            collectField(field, fields);
        });
    });

    return fields;
};

const collectField = (field, fields = {}) => {
    if (!field || typeof field !== 'object') {
        return fields;
    }

    indexCollectedField(fields, field);

    getNestedLayoutRowSources(field).forEach((nestedRows) => {
        nestedRows.forEach((row) => {
            if (!row || typeof row !== 'object' || !Array.isArray(row.fields)) {
                return;
            }

            row.fields.forEach((nestedField) => {
                collectField(nestedField, fields);
            });
        });
    });

    return fields;
};

const collectFieldsFromPages = (pages = []) => {
    const fields = {};

    pages.forEach((page) => {
        collectFieldsFromPage(page, fields);
    });

    return fields;
};

const diffOptions = (canonicalOptions = [], postedOptions = []) => {
    const diff = [];

    postedOptions.forEach((option, index) => {
        if (!isOptionRow(option)) {
            return;
        }

        const canonicalOption = resolveCanonicalOptionAtIndex(canonicalOptions, index);
        const postedLabel = option.label ?? null;
        const postedValue = String(option.value ?? '');
        const canonicalLabel = canonicalOption?.label ?? null;
        const canonicalValue = String(canonicalOption?.value ?? '');

        if (postedLabel === canonicalLabel && postedValue === canonicalValue) {
            return;
        }

        const storageValue = canonicalValue !== '' ? canonicalValue : postedValue;

        if (storageValue === '') {
            return;
        }

        const entry = {
            value: storageValue,
        };

        if (postedLabel !== canonicalLabel) {
            entry.label = postedLabel;
        }

        if (postedValue !== canonicalValue && canonicalValue !== '') {
            entry.optionValue = option.value ?? null;
        }

        diff.push(entry);
    });

    return diff;
};

const diffColumns = (canonicalColumns = [], postedColumns = []) => {
    const canonicalByHandle = new Map(
        canonicalColumns
            .filter((column) => column && typeof column === 'object')
            .map((column) => [String(column.handle ?? ''), column]),
    );

    const diff = [];

    postedColumns.forEach((column) => {
        if (!column || typeof column !== 'object') {
            return;
        }

        const handle = String(column.handle ?? '');

        if (!handle) {
            return;
        }

        const canonicalColumn = canonicalByHandle.get(handle) || {};

        if ((column.heading ?? null) !== (canonicalColumn.heading ?? null)) {
            diff.push({
                handle,
                heading: column.heading ?? null,
            });
        }
    });

    return diff;
};

const diffSettings = (canonicalSettings = {}, postedSettings = {}, keys = getTranslatableConfig().formSettings) => {
    const diff = {};

    keys.forEach((key) => {
        const canonicalValue = normalizeComparableValue(canonicalSettings[key]);
        const postedValue = normalizeComparableValue(postedSettings[key]);

        if (postedValue !== canonicalValue) {
            diff[key] = postedSettings[key] ?? null;
        }
    });

    return diff;
};

const diffPageSettings = (canonicalSettings = {}, postedSettings = {}) => {
    return diffSettings(
        canonicalSettings,
        postedSettings,
        getTranslatableConfig().pageSettings,
    );
};

const diffFields = (canonicalFields = {}, postedFields = {}) => {
    const diff = {};
    const seen = new Set();

    Object.entries(postedFields).forEach(([fieldKey, field]) => {
        if (!field || typeof field !== 'object') {
            return;
        }

        const fieldDefinitionId = getFieldDefinitionId(field);

        if (!fieldDefinitionId || seen.has(fieldDefinitionId)) {
            return;
        }

        seen.add(fieldDefinitionId);

        const canonicalField = resolveCollectedField(canonicalFields, fieldKey, field);
        const fieldDiff = {};
        const translatableProperties = resolveFieldTranslatableProperties(field);

        translatableProperties.forEach((key) => {
            if (getNestedKeys().includes(key)) {
                return;
            }

            if (!Object.prototype.hasOwnProperty.call(field, key)) {
                return;
            }

            if (translatableValuesAreEquivalent(canonicalField[key], field[key])) {
                return;
            }

            fieldDiff[key] = field[key];
        });

        if (translatableProperties.includes('options') && Array.isArray(field.options)) {
            const optionsDiff = diffOptions(
                Array.isArray(canonicalField.options) ? canonicalField.options : [],
                field.options,
            );

            if (optionsDiff.length) {
                fieldDiff.options = optionsDiff;
            }
        }

        if (translatableProperties.includes('columns') && Array.isArray(field.columns)) {
            const columnsDiff = diffColumns(
                Array.isArray(canonicalField.columns) ? canonicalField.columns : [],
                field.columns,
            );

            if (columnsDiff.length) {
                fieldDiff.columns = columnsDiff;
            }
        }

        if (Object.keys(fieldDiff).length) {
            diff[fieldDefinitionId] = fieldDiff;
        }
    });

    return diff;
};

const diffPages = (canonicalPages = [], postedPages = []) => {
    const pages = {};

    postedPages.forEach((page, pageIndex) => {
        if (!page || typeof page !== 'object') {
            return;
        }

        const pageKey = getPageStorageKey(page);

        if (!pageKey) {
            return;
        }

        const canonicalPage = resolveCanonicalPage(canonicalPages, page, pageIndex) || {};
        const pageDiff = {};

        if ((page.label ?? null) !== (canonicalPage.label ?? null)) {
            pageDiff.label = page.label ?? null;
        }

        const settingsDiff = diffPageSettings(
            canonicalPage.settings || {},
            page.settings || {},
        );

        if (Object.keys(settingsDiff).length) {
            pageDiff.settings = settingsDiff;
        }

        if (Object.keys(pageDiff).length) {
            pages[pageKey] = pageDiff;
        }
    });

    return pages;
};

const diffNotifications = (canonicalNotifications = [], postedNotifications = []) => {
    const canonicalByKey = indexNotificationsByStorageKey(canonicalNotifications);
    const diff = {};

    postedNotifications.forEach((notification) => {
        if (!notification || typeof notification !== 'object') {
            return;
        }

        const storageKey = getNotificationStorageKey(notification);

        if (!storageKey) {
            return;
        }

        const canonicalNotification = canonicalByKey[storageKey] || {};
        const notificationDiff = {};

        getTranslatableConfig().notification.forEach((key) => {
            const canonicalValue = normalizeComparableValue(canonicalNotification[key]);
            const postedValue = normalizeComparableValue(notification[key]);

            if (postedValue !== canonicalValue) {
                notificationDiff[key] = notification[key] ?? null;
            }
        });

        if (Object.keys(notificationDiff).length) {
            diff[storageKey] = notificationDiff;
        }
    });

    return diff;
};

export const extractSiteTranslationsFromFormData = (canonicalData = {}, formData = {}) => {
    if (!canonicalData || typeof canonicalData !== 'object' || !formData || typeof formData !== 'object') {
        return {};
    }

    const translations = {};

    getTranslatableConfig().form.forEach((key) => {
        const postedValue = formData[key] ?? null;
        const canonicalValue = canonicalData[key] ?? '';

        if (typeof postedValue === 'string' && postedValue !== '' && postedValue !== String(canonicalValue)) {
            translations[key] = postedValue;
        }
    });

    const settingsDiff = diffSettings(canonicalData.settings || {}, formData.settings || {});

    if (Object.keys(settingsDiff).length) {
        translations.settings = settingsDiff;
    }

    const canonicalPages = Array.isArray(canonicalData.pages) ? canonicalData.pages : [];
    const postedPages = Array.isArray(formData.pages) ? formData.pages : [];
    const pagesDiff = diffPages(canonicalPages, postedPages);

    if (Object.keys(pagesDiff).length) {
        translations.pages = pagesDiff;
    }

    const fieldsDiff = diffFields(
        collectFieldsFromPages(canonicalPages),
        collectFieldsFromPages(postedPages),
    );

    if (Object.keys(fieldsDiff).length) {
        translations.fieldOverrides = fieldsDiff;
    }

    const notificationsDiff = diffNotifications(
        Array.isArray(canonicalData.notifications) ? canonicalData.notifications : [],
        Array.isArray(formData.notifications) ? formData.notifications : [],
    );

    if (Object.keys(notificationsDiff).length) {
        translations.notifications = notificationsDiff;
    }

    return translations;
};

const revertOptionsLabels = (options = [], canonicalOptions = []) => {
    return options.map((option, index) => {
        if (!isOptionRow(option)) {
            return option;
        }

        const canonicalOption = resolveCanonicalOptionAtIndex(canonicalOptions, index);

        if (!canonicalOption) {
            return option;
        }

        return {
            ...option,
            label: canonicalOption.label,
            value: canonicalOption.value,
        };
    });
};

const revertColumnsHeadings = (columns = [], canonicalColumns = []) => {
    const canonicalByHandle = new Map(
        canonicalColumns
            .filter((column) => column && typeof column === 'object')
            .map((column) => [String(column.handle ?? ''), column]),
    );

    return columns.map((column) => {
        if (!column || typeof column !== 'object') {
            return column;
        }

        const canonicalColumn = canonicalByHandle.get(String(column.handle ?? ''));

        if (!canonicalColumn || !Object.prototype.hasOwnProperty.call(canonicalColumn, 'heading')) {
            return column;
        }

        return {
            ...column,
            heading: canonicalColumn.heading,
        };
    });
};

const revertFieldTranslatables = (field, canonicalField) => {
    if (!field || typeof field !== 'object') {
        return field;
    }

    if (!canonicalField || typeof canonicalField !== 'object') {
        return field;
    }

    const reverted = {
        ...field,
    };
    const translatableProperties = resolveFieldTranslatableProperties(field);

    translatableProperties.forEach((key) => {
        if (getNestedKeys().includes(key)) {
            return;
        }

        if (Object.prototype.hasOwnProperty.call(canonicalField, key)) {
            reverted[key] = canonicalField[key];
        }
    });

    if (translatableProperties.includes('options') && Array.isArray(field.options)) {
        reverted.options = revertOptionsLabels(
            field.options,
            Array.isArray(canonicalField.options) ? canonicalField.options : [],
        );
    }

    if (translatableProperties.includes('columns') && Array.isArray(field.columns)) {
        reverted.columns = revertColumnsHeadings(
            field.columns,
            Array.isArray(canonicalField.columns) ? canonicalField.columns : [],
        );
    }

    return reverted;
};

const revertFieldTree = (field, canonicalFieldsByKey = {}) => {
    if (!field || typeof field !== 'object') {
        return field;
    }

    const storageKey = getFieldStorageKey(field);
    const canonicalField = resolveCollectedField(canonicalFieldsByKey, storageKey, field);
    let reverted = revertFieldTranslatables(field, canonicalField);

    const nestedRows = getNestedLayoutRows(field);

    if (nestedRows) {
        reverted = {
            ...reverted,
            rows: nestedRows.map((row) => {
                if (!row || typeof row !== 'object' || !Array.isArray(row.fields)) {
                    return row;
                }

                return {
                    ...row,
                    fields: row.fields.map((nestedField) => {
                        return revertFieldTree(nestedField, canonicalFieldsByKey);
                    }),
                };
            }),
        };

        if (reverted.settings && typeof reverted.settings === 'object') {
            reverted.settings = {
                ...reverted.settings,
                rows: reverted.rows,
            };
        }
    }

    return reverted;
};

const revertPagesTranslatables = (pages = [], canonicalPages = []) => {
    const canonicalFieldsByKey = collectFieldsFromPages(canonicalPages);

    return pages.map((page, pageIndex) => {
        if (!page || typeof page !== 'object') {
            return page;
        }

        const canonicalPage = resolveCanonicalPage(canonicalPages, page, pageIndex);
        const revertedPage = {
            ...page,
        };

        if (canonicalPage && Object.prototype.hasOwnProperty.call(canonicalPage, 'label')) {
            revertedPage.label = canonicalPage.label;
        }

        if (canonicalPage?.settings && typeof canonicalPage.settings === 'object') {
            revertedPage.settings = {
                ...(page.settings || {}),
            };

            getTranslatableConfig().pageSettings.forEach((key) => {
                if (Object.prototype.hasOwnProperty.call(canonicalPage.settings, key)) {
                    revertedPage.settings[key] = canonicalPage.settings[key];
                }
            });
        }

        if (Array.isArray(page.rows)) {
            revertedPage.rows = page.rows.map((row) => {
                if (!row || typeof row !== 'object' || !Array.isArray(row.fields)) {
                    return row;
                }

                return {
                    ...row,
                    fields: row.fields.map((field) => {
                        return revertFieldTree(field, canonicalFieldsByKey);
                    }),
                };
            });
        }

        return revertedPage;
    });
};

const revertNotificationsTranslatables = (notifications = [], canonicalNotifications = []) => {
    const canonicalByKey = indexNotificationsByStorageKey(canonicalNotifications);

    return notifications.map((notification) => {
        if (!notification || typeof notification !== 'object') {
            return notification;
        }

        const storageKey = getNotificationStorageKey(notification);
        const canonicalNotification = storageKey ? canonicalByKey[storageKey] : null;

        if (!canonicalNotification) {
            return notification;
        }

        return {
            ...notification,
            ...getTranslatableConfig().notification.reduce((carry, key) => {
                if (Object.prototype.hasOwnProperty.call(canonicalNotification, key)) {
                    carry[key] = canonicalNotification[key];
                }

                return carry;
            }, {}),
        };
    });
};

export const stripTranslatableValuesToCanonical = (formData = {}, canonicalData = {}) => {
    if (!formData || typeof formData !== 'object' || !canonicalData || typeof canonicalData !== 'object') {
        return formData;
    }

    const stripped = {
        ...formData,
    };

    getTranslatableConfig().form.forEach((key) => {
        if (Object.prototype.hasOwnProperty.call(canonicalData, key)) {
            stripped[key] = canonicalData[key];
        }
    });

    stripped.settings = {
        ...(formData.settings || {}),
    };

    getTranslatableConfig().formSettings.forEach((key) => {
        if (canonicalData.settings && Object.prototype.hasOwnProperty.call(canonicalData.settings, key)) {
            stripped.settings[key] = canonicalData.settings[key];
        }
    });

    if (Array.isArray(formData.pages)) {
        stripped.pages = revertPagesTranslatables(
            formData.pages,
            Array.isArray(canonicalData.pages) ? canonicalData.pages : [],
        );
    }

    if (Array.isArray(formData.notifications)) {
        stripped.notifications = revertNotificationsTranslatables(
            formData.notifications,
            Array.isArray(canonicalData.notifications) ? canonicalData.notifications : [],
        );
    }

    return stripped;
};
