import { cloneDeep } from 'lodash-es';

import {
    normalizeFormData,
    serializeFormData,
    serializeFormDataForDirtyCheck,
} from '@form-builder/hooks/useFormTools';
import {
    forEachFieldInLayoutMaps,
    getActiveSubFieldReferenceMap,
} from './fieldReferences';

const CONTAINER_SETTING_MIRROR_KEYS = [
    'rows',
    'layouts',
    'displayType',
    'collectMode',
    'dateFormat',
    'timeFormat',
    'defaultOption',
    'defaultValue',
];

const fieldHasLayoutVariants = (field) => {
    if (!field || typeof field !== 'object') {
        return false;
    }

    return Boolean(field.layouts || field?.settings?.layouts);
};

const collapseMirroredContainerSettings = (field) => {
    if (!field?.settings || typeof field.settings !== 'object') {
        return field;
    }

    const next = { ...field };
    const nextSettings = { ...next.settings };
    let changed = false;

    CONTAINER_SETTING_MIRROR_KEYS.forEach((key) => {
        if (!(key in next) || !(key in nextSettings)) {
            return;
        }

        if (JSON.stringify(next[key]) !== JSON.stringify(nextSettings[key])) {
            return;
        }

        delete nextSettings[key];
        changed = true;
    });

    if (!changed) {
        return next;
    }

    if (Object.keys(nextSettings).length === 0) {
        delete next.settings;
    } else {
        next.settings = nextSettings;
    }

    return next;
};

const promoteContainerSettingsToTopLevel = (field) => {
    if (!field?.settings || typeof field.settings !== 'object') {
        return field;
    }

    const next = { ...field };
    const nextSettings = { ...next.settings };
    let changed = false;

    CONTAINER_SETTING_MIRROR_KEYS.forEach((key) => {
        if (key in next || !(key in nextSettings)) {
            return;
        }

        next[key] = nextSettings[key];
        delete nextSettings[key];
        changed = true;
    });

    if (!changed) {
        return next;
    }

    if (Object.keys(nextSettings).length === 0) {
        delete next.settings;
    } else {
        next.settings = nextSettings;
    }

    return next;
};

const syncLayoutReferencesFromActiveRows = (field, referencesByHandle) => {
    const syncSubFieldReference = (subField) => {
        if (!subField || typeof subField !== 'object') {
            return;
        }

        const handle = String(subField?.handle || subField?.settings?.handle || '').trim();
        const activeReference = handle ? referencesByHandle.get(handle) : null;

        if (activeReference) {
            subField.reference = activeReference;
        }
    };

    forEachFieldInLayoutMaps(field.layouts, syncSubFieldReference);
    forEachFieldInLayoutMaps(field?.settings?.layouts, syncSubFieldReference);
};

const canonicalizeContainerFieldForDirtyCheck = (field) => {
    if (!fieldHasLayoutVariants(field)) {
        return field;
    }

    let nextField = promoteContainerSettingsToTopLevel(field);
    nextField = collapseMirroredContainerSettings(nextField);
    const referencesByHandle = getActiveSubFieldReferenceMap(nextField);

    if (referencesByHandle.size) {
        syncLayoutReferencesFromActiveRows(nextField, referencesByHandle);
    }

    return nextField;
};

const canonicalizeFormDataForDirtyCheck = (data = {}) => {
    const next = cloneDeep(data);

    (next.pages || []).forEach((page) => {
        if (!Array.isArray(page?.rows)) {
            return;
        }

        page.rows = page.rows.map((row) => {
            if (!row || typeof row !== 'object' || !Array.isArray(row.fields)) {
                return row;
            }

            return {
                ...row,
                fields: row.fields.map((field) => {
                    return canonicalizeContainerFieldForDirtyCheck(field);
                }),
            };
        });
    });

    return next;
};

const dirtyFormSnapshot = (values = {}) => {
    const normalized = normalizeFormData(values || {});

    return serializeFormDataForDirtyCheck(
        canonicalizeFormDataForDirtyCheck(normalized),
    );
};

const saveFormSnapshot = (values = {}) => {
    return serializeFormData(normalizeFormData(values || {}));
};

export {
    canonicalizeFormDataForDirtyCheck,
    dirtyFormSnapshot,
    saveFormSnapshot,
};
