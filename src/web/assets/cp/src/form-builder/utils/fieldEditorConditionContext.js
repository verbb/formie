export const getFieldEditorConditionContext = (field, values, hasSubmissions) => {
    return {
        formBuilder: {
            hasSubmissions: Boolean(hasSubmissions),
            fieldIsPersisted: Boolean(values?.id ?? field?.id) && !field?._isNew,
        },
    };
};
