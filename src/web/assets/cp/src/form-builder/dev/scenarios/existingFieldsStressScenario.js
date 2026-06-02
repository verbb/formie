import { parseStressPattern } from '@form-builder/dev/scenarios/stressTestScenario';

const DEFAULT_FIELD_TYPE = 'verbb\\formie\\fields\\SingleLineText';

const deepClone = (value) => {
    return JSON.parse(JSON.stringify(value));
};

const getFormEntries = (forms = []) => {
    return (forms || []).filter((item) => { return Array.isArray(item?.pages); });
};

const getTemplateFields = (formEntries = []) => {
    const templates = [];

    formEntries.forEach((form) => {
        (form.pages || []).forEach((page) => {
            (page.fields || []).forEach((field) => {
                if (field && typeof field === 'object') {
                    templates.push(field);
                }
            });
        });
    });

    return templates;
};

const createFallbackFieldTemplate = () => {
    return {
        type: DEFAULT_FIELD_TYPE,
        settings: {
            label: 'Mock Field',
            handle: 'mockField',
        },
    };
};

const createMockExistingFieldsData = (existingFields = [], pattern = '100x5x24') => {
    const config = parseStressPattern(pattern);
    if (!config) {
        return existingFields;
    }

    const formEntries = getFormEntries(existingFields);
    const templateForms = formEntries.length ? formEntries : [{
        key: 'template',
        label: 'Template Form',
        pages: [{ label: 'Page 1', fields: [createFallbackFieldTemplate()] }],
    }];
    const templateFields = getTemplateFields(templateForms);
    const fieldTemplates = templateFields.length ? templateFields : [createFallbackFieldTemplate()];

    const mockedForms = [];
    let globalFieldIndex = 1;

    for (let formIndex = 0; formIndex < config.pages; formIndex += 1) {
        const templateForm = templateForms[formIndex % templateForms.length];
        const formPages = [];

        for (let pageIndex = 0; pageIndex < config.rowsPerPage; pageIndex += 1) {
            const pageFields = [];

            for (let fieldIndex = 0; fieldIndex < config.fieldsPerRow; fieldIndex += 1) {
                const template = deepClone(fieldTemplates[(globalFieldIndex - 1) % fieldTemplates.length]);
                const settings = template.settings && typeof template.settings === 'object' ? template.settings : {};
                const labelBase = settings.label || template.label || 'Mock Field';

                template.id = `mock-field-${globalFieldIndex}`;
                template.reference = `fields.mockField${globalFieldIndex}`;
                template.type = template.type || settings.type || DEFAULT_FIELD_TYPE;
                template.settings = {
                    ...settings,
                    label: `${labelBase} ${globalFieldIndex}`,
                    handle: `mockField${globalFieldIndex}`,
                };

                pageFields.push(template);
                globalFieldIndex += 1;
            }

            formPages.push({
                label: `Page ${pageIndex + 1}`,
                fields: pageFields,
            });
        }

        mockedForms.push({
            key: `mock-form-${formIndex + 1}`,
            label: `Mock Form ${formIndex + 1}`,
            source: templateForm.source || null,
            pages: formPages,
        });
    }

    return mockedForms;
};

export {
    createMockExistingFieldsData,
};
