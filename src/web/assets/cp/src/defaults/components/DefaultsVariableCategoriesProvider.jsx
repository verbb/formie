import { useCallback, useMemo } from 'react';

import { VariableCategoriesProvider } from '@verbb/plugin-kit-react/forms';

import { resolveStaticVariableCategories } from '@defaults/utils/variableCategories';

export const DefaultsVariableCategoriesProvider = ({ settings, children }) => {
    const variableCategoriesConfig = settings?.variableCategoriesConfig ?? {};
    const variableCategoryLabels = settings?.variableCategoryLabels ?? {};
    const variableCategoryOrder = settings?.variableCategoryOrder ?? [];
    const variableTransformerRegistry = variableCategoriesConfig?.transformerRegistry ?? {};

    const getVariableCategories = useCallback((variableConfig) => {
        return resolveStaticVariableCategories(variableCategoriesConfig, variableConfig);
    }, [variableCategoriesConfig]);

    const value = useMemo(() => {
        return {
            getVariableCategories,
            variableCategoryLabels,
            variableCategoryOrder,
            variableTransformerRegistry,
        };
    }, [
        getVariableCategories,
        variableCategoryLabels,
        variableCategoryOrder,
        variableTransformerRegistry,
    ]);

    return (
        <VariableCategoriesProvider value={value}>
            {children}
        </VariableCategoriesProvider>
    );
};
