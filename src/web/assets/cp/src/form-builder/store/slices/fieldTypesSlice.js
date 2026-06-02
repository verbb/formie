const createFieldTypesSlice = (set, get) => {
    return {
        fieldTypes: [], // Flattened array of all field types
        fieldTypeGroups: [], // Original grouped structure

        // Initialize field types with flattening and normalization
        initFieldTypes: (fieldTypeGroups) => {
            // Flatten the grouped field types into a single array
            const flattenedFieldTypes = fieldTypeGroups.reduce((acc, group) => {
                const groupFieldTypes = group.fields.map((fieldType) => {
                    return {
                        ...fieldType,
                        groupHandle: group.handle,
                        groupLabel: group.label,
                    };
                });

                return [...acc, ...groupFieldTypes];
            }, []);

            set({
                fieldTypes: flattenedFieldTypes,
                fieldTypeGroups,
            });
        },


        // Getter methods for easy access
        getFieldTypeByType: (type) => {
            const { fieldTypes } = get();

            return fieldTypes.find((field) => { return field.type === type; });
        },

        hydrateFieldTypeConfig: (type, fieldTypeConfig) => {
            if (!type || !fieldTypeConfig || typeof fieldTypeConfig !== 'object') {
                return;
            }

            set((state) => {
                return {
                    fieldTypes: state.fieldTypes.map((fieldType) => {
                        return fieldType.type === type ? { ...fieldType, ...fieldTypeConfig } : fieldType;
                    }),
                    fieldTypeGroups: state.fieldTypeGroups.map((group) => {
                        return {
                            ...group,
                            fields: (group.fields || []).map((fieldType) => {
                                return fieldType.type === type ? { ...fieldType, ...fieldTypeConfig } : fieldType;
                            }),
                        };
                    }),
                };
            });
        },

        // getFieldTypeByHandle: (handle) => {
        //     const { fieldTypes } = get();
        //     return fieldTypes.find((field) => { return field.handle === handle; });
        // },

        // getFieldTypesByGroup: (groupHandle) => {
        //     const { fieldTypes } = get();
        //     return fieldTypes.filter((field) => { return field.groupHandle === groupHandle; });
        // },

        // getFieldTypesByGroupLabel: (groupLabel) => {
        //     const { fieldTypes } = get();
        //     return fieldTypes.filter((field) => { return field.groupLabel === groupLabel; });
        // },

        // getPickableFieldTypes: () => {
        //     const { fieldTypes } = get();
        //     return fieldTypes.filter((field) => { return field.isPickable !== false; });
        // },

        // getFieldTypesExcludingGroup: (excludeGroupHandle) => {
        //     const { fieldTypes } = get();
        //     return fieldTypes.filter((field) => { return field.groupHandle !== excludeGroupHandle; });
        // },

        // // Get all available group handles
        // getGroupHandles: () => {
        //     const { fieldTypeGroups } = get();
        //     return fieldTypeGroups.map((group) => { return group.handle; });
        // },

        // // Get all available group labels
        // getGroupLabels: () => {
        //     const { fieldTypeGroups } = get();
        //     return fieldTypeGroups.map((group) => { return group.label; });
        // },

        // // Get a specific group by handle
        // getGroupByHandle: (groupHandle) => {
        //     const { fieldTypeGroups } = get();
        //     return fieldTypeGroups.find((group) => { return group.handle === groupHandle; });
        // },

        // // Get a specific group by label
        // getGroupByLabel: (groupLabel) => {
        //     const { fieldTypeGroups } = get();
        //     return fieldTypeGroups.find((group) => { return group.label === groupLabel; });
        // },
    };
};

export { createFieldTypesSlice };
