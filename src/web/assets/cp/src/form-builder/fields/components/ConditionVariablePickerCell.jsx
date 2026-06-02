import { DropdownMenuItem } from '@verbb/plugin-kit-react/components';
import { FormBuilderVariablePickerControl } from './FormBuilderVariablePickerControl';

export function ConditionVariablePickerCell({
    column,
    value,
    isInvalid,
    updateValue,
}) {
    return (
        <FormBuilderVariablePickerControl
            value={value}
            onChange={updateValue}
            isInvalid={isInvalid}
            variableCategories={column.variableCategories || {}}
            variableCategoryLabels={column.variableCategoryLabels || {}}
            variableCategoryOrder={column.variableCategoryOrder || []}
            variableTransformerRegistry={column.variableTransformerRegistry || {}}
            noneOptionLabel={column.noneOptionLabel}
            pickerContentClassName={column.contentClassName || 'min-w-[260px] max-w-[360px] p-0 overflow-hidden flex flex-col'}
            wrapperClassName="px-2"
            alwaysShowActionsMenu={false}
            renderActionItems={({ canShowSettings, openSettings, t }) => {
                return (
                    <DropdownMenuItem
                        disabled={!canShowSettings}
                        onClick={openSettings}
                    >
                        {t('Configure Value')}
                    </DropdownMenuItem>
                );
            }}
        />
    );
}
