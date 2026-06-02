import { VariableCategoriesProvider as Provider } from '@verbb/plugin-kit-react/forms';
import useAppStore from '@form-builder/hooks/useAppStore';
import { useVariableCategoriesResolver } from '@form-builder/hooks/useVariableCategories';

/**
 * Provides variable categories resolution and metadata to rich text fields in the form builder.
 * Labels and order come from the server (Variables::getFormBuilderVariableConfig).
 * Must be inside FormBuilderFormProvider and FormBuilderAppProvider.
 */
export function VariableCategoriesProvider({ children }) {
    const getVariableCategories = useVariableCategoriesResolver();
    const variableCategoryLabels = useAppStore((state) => { return state.variableCategoryLabels; });
    const variableCategoryOrder = useAppStore((state) => { return state.variableCategoryOrder; });
    const variableTransformerRegistry = useAppStore((state) => {
        return state.variableCategoriesConfig?.transformerRegistry || {};
    });

    return (
        <Provider
            value={{
                getVariableCategories,
                variableCategoryLabels,
                variableCategoryOrder,
                variableTransformerRegistry,
            }}
        >
            {children}
        </Provider>
    );
}
