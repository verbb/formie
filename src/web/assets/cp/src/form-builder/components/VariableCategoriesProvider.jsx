import { useCallback } from 'react';
import { useTranslation } from '@verbb/plugin-kit-react/hooks';
import { VariableCategoriesProvider as Provider } from '@utils/VariableCategoriesProvider';
import useAppStore from '@form-builder/hooks/useAppStore';
import { useVariableCategoriesResolver } from '@form-builder/hooks/useVariableCategories';
import { useRepeaterVariableConfigureSection } from '@form-builder/hooks/useRepeaterVariableConfigureSection';
import {
    createSyntheticRepeaterSubFieldOption,
    isRepeaterScopedFieldToken,
    isRepeaterSubFieldOption,
    resolveRepeaterVariableDisplayLabel,
} from '@form-builder/fields/utils/repeaterRowTargeting';

/**
 * Provides variable categories resolution and metadata to rich text fields in the form builder.
 * Labels and order come from the server (Variables::getFormBuilderVariableConfig).
 * Must be inside FormBuilderFormProvider and FormBuilderAppProvider.
 */
export function VariableCategoriesProvider({ children }) {
    const t = useTranslation();
    const getVariableCategories = useVariableCategoriesResolver();
    const renderVariableConfigureSection = useRepeaterVariableConfigureSection();
    const resolveVariableTagLabel = useCallback(({ tokenValue, variableOption, defaultLabel, storedLabel }) => {
        if (isRepeaterSubFieldOption(variableOption)) {
            return resolveRepeaterVariableDisplayLabel(tokenValue, variableOption, t) || defaultLabel;
        }

        if (isRepeaterScopedFieldToken(tokenValue)) {
            const option = createSyntheticRepeaterSubFieldOption(tokenValue, storedLabel || defaultLabel);

            return resolveRepeaterVariableDisplayLabel(tokenValue, option, t) || defaultLabel;
        }

        return defaultLabel;
    }, [t]);
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
                renderVariableConfigureSection,
                resolveVariableTagLabel,
            }}
        >
            {children}
        </Provider>
    );
}
