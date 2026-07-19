import { DropdownItem } from '@verbb/plugin-kit-react/components';
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
            // Popup panel sizing only — never reuse EditableTable `contentClassName`
            // (that class lands on the cell projection wrapper).
            pickerContentClassName={
                column.pickerContentClassName
                || 'min-w-[260px] max-w-[360px] p-0 overflow-hidden flex flex-col'
            }
            // Match EditableTable Condition `pk-select` (`size="sm"`) and v1 picker trigger.
            triggerSize="sm"
            triggerClassName="min-w-0 flex-1 justify-between"
            // Match select/combobox cell inset (ET td padding-inline 0.5rem).
            wrapperClassName="px-2"
            alwaysShowActionsMenu={false}
            renderActionItems={({ canShowSettings, openSettings, t }) => {
                return (
                    <DropdownItem
                        disabled={!canShowSettings}
                        onPkSelect={openSettings}
                    >
                        {t('Configure Value')}
                    </DropdownItem>
                );
            }}
        />
    );
}
