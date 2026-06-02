const STRESS_PATTERN_REGEX = /^(\d+)x(\d+)x(\d+)$/i;
const DEFAULT_FIELD_TYPE = 'verbb\\formie\\fields\\SingleLineText';
const getPageHandle = (pageIndex) => {
    return `page${pageIndex + 1}`;
};

const parseStressPattern = (value) => {
    const match = String(value || '').trim().match(STRESS_PATTERN_REGEX);
    if (!match) {
        return null;
    }

    const pages = Number(match[1]);
    const rowsPerPage = Number(match[2]);
    const fieldsPerRow = Number(match[3]);

    if (pages <= 0 || rowsPerPage <= 0 || fieldsPerRow <= 0) {
        return null;
    }

    return {
        pages,
        rowsPerPage,
        fieldsPerRow,
    };
};

const createStressTestPages = (settings, config) => {
    const singleLineTextTemplate = (settings?.fieldTypeGroups || [])
        .flatMap((group) => { return group?.fields || []; })
        .find((fieldTypeConfig) => { return fieldTypeConfig?.type === DEFAULT_FIELD_TYPE; })
        ?.newField;

    if (!singleLineTextTemplate) {
        console.warn('FormBuilder stress test scenario could not find the SingleLineText field template.');
        return settings?.data?.pages ?? [];
    }

    const pages = [];
    let fieldIndex = 1;

    for (let pageIndex = 0; pageIndex < config.pages; pageIndex += 1) {
        const rows = [];

        for (let rowIndex = 0; rowIndex < config.rowsPerPage; rowIndex += 1) {
            const rowFields = [];

            for (let columnIndex = 0; columnIndex < config.fieldsPerRow; columnIndex += 1) {
                const clonedField = JSON.parse(JSON.stringify(singleLineTextTemplate));
                const baseSettings = (clonedField?.settings && typeof clonedField.settings === 'object')
                    ? clonedField.settings
                    : {};
                const labelBase = baseSettings.label || clonedField.label || 'Field';
                const handle = `devStressField${fieldIndex}`;

                clonedField.id = fieldIndex;
                clonedField.tempId = `dev-stress-${fieldIndex}`;
                clonedField.elementId = `dev-stress-${fieldIndex}`;
                clonedField.label = `${labelBase} ${fieldIndex}`;
                clonedField.handle = handle;
                clonedField.settings = {
                    ...baseSettings,
                    label: `${labelBase} ${fieldIndex}`,
                    handle,
                };

                rowFields.push(clonedField);
                fieldIndex += 1;
            }

            rows.push({
                id: `${pageIndex + 1}-${rowIndex + 1}`,
                fields: rowFields,
            });
        }

        pages.push({
            id: pageIndex + 1,
            tempId: `dev-stress-page-${pageIndex + 1}`,
            label: `Stress Page ${pageIndex + 1}`,
            _handle: getPageHandle(pageIndex),
            rows,
            settings: {
                submitButtonLabel: 'Submit',
                showBackButton: pageIndex > 0,
            },
        });
    }

    return pages;
};

export {
    parseStressPattern,
    createStressTestPages,
};
