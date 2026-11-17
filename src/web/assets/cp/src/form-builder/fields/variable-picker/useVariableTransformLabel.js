import { useCallback } from 'react';
import { resolveTransformLabel } from '@form-builder/fields/utils/variablePicker';

// Plugin Kit's `variableTransformerLabel` resolver, naming each chip's transform badge from its variable's types.
export const useVariableTransformLabel = (variableCategories, variableTransformerRegistry) => {
    return useCallback((attrs) => {
        return resolveTransformLabel(attrs, variableCategories ?? {}, variableTransformerRegistry ?? {});
    }, [variableCategories, variableTransformerRegistry]);
};
