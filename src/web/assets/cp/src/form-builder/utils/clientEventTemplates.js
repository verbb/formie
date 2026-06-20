import { createItem } from '@verbb/plugin-kit-react/utils';

const PAGE_CONTEXTS = {
    ANY: 'any',
    FIRST: 'first-page',
    MIDDLE: 'middle-page',
    LAST: 'last-page',
    SINGLE: 'single-page',
};

export function getPageContext(pageIndex, pageCount) {
    if (typeof pageIndex !== 'number' || pageIndex < 0) {
        return PAGE_CONTEXTS.ANY;
    }

    if (pageCount <= 1) {
        return PAGE_CONTEXTS.SINGLE;
    }

    if (pageIndex === 0) {
        return PAGE_CONTEXTS.FIRST;
    }

    if (pageIndex === pageCount - 1) {
        return PAGE_CONTEXTS.LAST;
    }

    return PAGE_CONTEXTS.MIDDLE;
}

export function templateMatchesPageContext(template, pageContext) {
    const contexts = Array.isArray(template?.pageContexts) ? template.pageContexts : ['any'];

    return contexts.includes('any') || contexts.includes(pageContext);
}

export function getSuggestedTemplates(templates, pageContext) {
    return (Array.isArray(templates) ? templates : [])
        .filter((template) => template.handle !== 'blank')
        .filter((template) => templateMatchesPageContext(template, pageContext));
}

export function getTemplateFieldSlots(template) {
    return (Array.isArray(template?.payload) ? template.payload : [])
        .filter((row) => row?.kind === 'field');
}

export function templateRequiresFieldMapping(template) {
    return getTemplateFieldSlots(template).length > 0;
}

export function materializeClientEventTemplate(template, fieldMappings = {}) {
    const payload = (Array.isArray(template?.payload) ? template.payload : []).map((row) => {
        const key = String(row?.key || '').trim();

        if (!key) {
            return null;
        }

        if (row.kind === 'field') {
            return {
                key,
                value: String(fieldMappings[key] || ''),
            };
        }

        return {
            key,
            value: String(row.value || ''),
        };
    }).filter(Boolean);

    return {
        ...createItem({}),
        event: template?.event || 'formPageSubmission',
        payload,
        templateHandle: template?.handle || null,
        templateLabel: template?.label || null,
        enableConditions: false,
        conditions: {
            applyRule: 'apply',
            conditionRule: 'all',
            conditions: [],
        },
    };
}

export function getPageIndexFromFieldName(fieldName) {
    const match = /^pages\.(\d+)\./.exec(String(fieldName || ''));

    return match ? Number(match[1]) : null;
}

export function collectMappableFields(pages = [], fieldTypes = []) {
    const allowedTypes = new Set(Array.isArray(fieldTypes) ? fieldTypes : []);
    const fields = [];

    const visitField = (field, pageLabel) => {
        if (!field || typeof field !== 'object') {
            return;
        }

        const type = String(field.type || '');
        const reference = String(field.reference || '').trim();
        const handle = String(field.handle || '').trim();
        const label = String(field.label || field.name || handle || reference).trim();

        if (!reference || !handle) {
            return;
        }

        if (allowedTypes.size > 0 && !allowedTypes.has(type)) {
            return;
        }

        fields.push({
            label: pageLabel ? `${label} (${pageLabel})` : label,
            value: `{field:${reference}}`,
            handle,
            type,
            reference,
        });

        if (Array.isArray(field.rows)) {
            field.rows.forEach((row) => {
                (Array.isArray(row?.fields) ? row.fields : []).forEach((childField) => {
                    visitField(childField, pageLabel);
                });
            });
        }
    };

    (Array.isArray(pages) ? pages : []).forEach((page) => {
        const pageLabel = String(page?.label || page?.name || '').trim();

        (Array.isArray(page?.rows) ? page.rows : []).forEach((row) => {
            (Array.isArray(row?.fields) ? row.fields : []).forEach((field) => {
                visitField(field, pageLabel);
            });
        });
    });

    return fields;
}

export { PAGE_CONTEXTS };
