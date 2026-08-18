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
            // Match EditableTable Condition/Value `pk-select` (`size="xs"`) chip height.
            // Tokens / popover align-self overrides live in style.css (see DECISIONS).
            triggerSize="none"
            triggerClassName="form-builder-condition-field-trigger min-w-0 w-full justify-between"
            // Popover :host uses align-self:flex-start — class restores row centering + flex-1.
            popoverClassName="form-builder-condition-field-popover"
            // Explicit 34px row (ET cell) — do not rely on h-full vs min-height on the slot wrapper.
            wrapperClassName="box-border"
            rowClassName="h-[34px] px-2"
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
