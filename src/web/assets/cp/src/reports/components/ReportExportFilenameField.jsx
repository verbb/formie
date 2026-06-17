import { TiptapInput } from '@verbb/plugin-kit-react/components';

export function ReportExportFilenameField({
    value = '',
    onChange,
    disabled = false,
    placeholder = 'formie-report-{handle}-{timestamp}',
    variableCategories = {},
    variableCategoryLabels = {},
    variableCategoryOrder = [],
    variableTransformerRegistry = {},
}) {
    return (
        <TiptapInput
            value={value}
            onChange={onChange}
            className="w-full [&_.ProseMirror]:min-h-[30px] [&_.ProseMirror]:px-1 [&_.ProseMirror]:py-[6px] [&_.ProseMirror]:text-xs"
            placeholder={placeholder}
            disabled={disabled}
            variableCategories={variableCategories}
            variableCategoryLabels={variableCategoryLabels}
            variableCategoryOrder={variableCategoryOrder}
            variableTransformerRegistry={variableTransformerRegistry}
            variablePickerTriggerCharacters={['@', '{']}
        />
    );
}
