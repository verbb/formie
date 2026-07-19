import { createContext, useContext } from 'react';

const VariableCategoriesContext = createContext(null);

export const VariableCategoriesProvider = VariableCategoriesContext.Provider;

export const useVariableCategoriesContext = () => {
    const value = useContext(VariableCategoriesContext);

    if (value == null) {
        return { getVariableCategories: null };
    }

    return {
        getVariableCategories: value.getVariableCategories ?? null,
        variableCategoryLabels: value.variableCategoryLabels,
        variableCategoryOrder: value.variableCategoryOrder,
        variableTransformerRegistry: value.variableTransformerRegistry,
        renderVariableConfigureSection: value.renderVariableConfigureSection,
        resolveVariableTagLabel: value.resolveVariableTagLabel,
    };
};
