export const fieldSupportsQuestionnaireResults = (field, fieldType) => {
    if (!fieldType?.isQuestionnaireField) {
        return false;
    }

    const when = fieldType?.questionnaireResultsWhen;

    if (!when?.property || !Array.isArray(when.values) || when.values.length === 0) {
        return true;
    }

    return when.values.includes(field?.[when.property]);
};

export const formHasQuestionnaireFields = (values = {}, getFieldTypeByType = null) => {
    const pages = Array.isArray(values?.pages) ? values.pages : [];

    for (const page of pages) {
        for (const row of page?.rows || []) {
            for (const field of row?.fields || []) {
                const fieldType = typeof getFieldTypeByType === 'function'
                    ? getFieldTypeByType(field?.type)
                    : null;

                if (fieldSupportsQuestionnaireResults(field, fieldType)) {
                    return true;
                }
            }
        }
    }

    return false;
};

export const formHasQuizFields = (values = {}, getFieldTypeByType = null) => {
    const pages = Array.isArray(values?.pages) ? values.pages : [];

    for (const page of pages) {
        for (const row of page?.rows || []) {
            for (const field of row?.fields || []) {
                const fieldType = typeof getFieldTypeByType === 'function'
                    ? getFieldTypeByType(field?.type)
                    : null;

                if (fieldType?.type?.includes('\\Quiz') || field?.type?.includes('\\Quiz')) {
                    return true;
                }
            }
        }
    }

    return false;
};
