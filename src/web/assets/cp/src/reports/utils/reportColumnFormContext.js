export const buildFormGroupLookups = (fieldColumnGroups = []) => {
    const formGroupMetaByFormId = new Map();
    const formIdsByHandle = new Map();

    fieldColumnGroups.forEach((group) => {
        const formId = String(group.formId);

        formGroupMetaByFormId.set(formId, {
            formTitle: group.formTitle || group.formHandle || '',
            formHandle: group.formHandle || '',
        });

        (group.columns || []).forEach((column) => {
            const handle = column?.handle;

            if (!handle) {
                return;
            }

            const formIds = formIdsByHandle.get(handle) || new Set();
            formIds.add(formId);
            formIdsByHandle.set(handle, formIds);
        });
    });

    return {
        formGroupMetaByFormId,
        formIdsByHandle,
    };
};

export const enrichColumnWithFormContext = (column, lookups) => {
    if ((column?.type || 'attribute') !== 'field') {
        return column;
    }

    const { formGroupMetaByFormId, formIdsByHandle } = lookups;

    if (column.formId) {
        const meta = formGroupMetaByFormId.get(String(column.formId));

        if (meta?.formTitle) {
            return {
                ...column,
                formTitle: meta.formTitle,
            };
        }
    }

    const formIds = formIdsByHandle.get(column.handle);

    if (!formIds?.size) {
        return column;
    }

    const titles = [...formIds]
        .map((formId) => formGroupMetaByFormId.get(formId)?.formTitle)
        .filter(Boolean);
    const uniqueTitles = [...new Set(titles)];

    if (uniqueTitles.length === 1) {
        return {
            ...column,
            formTitle: uniqueTitles[0],
        };
    }

    if (uniqueTitles.length > 1) {
        return {
            ...column,
            formTitle: uniqueTitles.join(' · '),
        };
    }

    return column;
};

export const withEnabledFormContext = (column, sourceColumn) => {
    if (!sourceColumn?.formId) {
        return {
            ...column,
            enabled: true,
        };
    }

    return {
        ...column,
        enabled: true,
        formId: sourceColumn.formId,
    };
};

export const getDuplicateFormTitles = (groups = []) => {
    const counts = new Map();

    groups.forEach((group) => {
        const title = group.formTitle || group.formHandle || '';

        if (!title) {
            return;
        }

        counts.set(title, (counts.get(title) || 0) + 1);
    });

    return new Set(
        [...counts.entries()]
            .filter(([, count]) => count > 1)
            .map(([title]) => title),
    );
};
