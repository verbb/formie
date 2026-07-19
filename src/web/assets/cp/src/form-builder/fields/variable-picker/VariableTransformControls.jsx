import { useTranslation } from '@verbb/plugin-kit-react/hooks';
import { Input, Option, OptionGroup, Select } from '@verbb/plugin-kit-react/components';

/** Empty string — same sentinel as kit v1 SelectItem value="" for "None". */
const TRANSFORM_NONE_VALUE = '';

function TransformParamField({
    param,
    value,
    onChange,
    t,
}) {
    if (!Array.isArray(param.options) || param.options.length === 0) {
        return (
            <Input
                type={param.type === 'number' ? 'number' : 'text'}
                value={value}
                onChange={event => {
                    return onChange(event.target.value);
                }}
                placeholder={param.placeholder || (param.default == null ? '' : String(param.default))}
                className="text-[13px] placeholder:text-slate-400"
            />
        );
    }

    const { options } = param;
    const groupedOptions = options.reduce((acc, option) => {
        const group = option.group ?? '';
        if (!acc[group]) {
            acc[group] = [];
        }
        acc[group].push(option);
        return acc;
    }, {});
    const groupOrder = Object.keys(groupedOptions);
    const preferredDefault = options.some(option => {
        return option.value === 'isoDate';
    }) ? 'isoDate' : options[0]?.value ?? '';
    const resolvedDefault = String(param.default ?? preferredDefault);
    const selectValue = value || resolvedDefault;

    return (
        <div className="space-y-2">
            <Select
                value={selectValue}
                size="sm"
                width="full"
                placeholder={t('Select option')}
                onPkChange={(event) => {
                    onChange(String(event.detail?.value ?? ''));
                }}
            >
                {groupOrder.map((groupKey) => {
                    const entries = groupedOptions[groupKey] ?? [];
                    if (entries.length === 0) {
                        return null;
                    }

                    if (!groupKey) {
                        return entries.map(entry => {
                            return (
                                <Option key={entry.value} value={entry.value}>
                                    {entry.label}
                                </Option>
                            );
                        });
                    }

                    return (
                        <OptionGroup key={groupKey} label={groupKey}>
                            {entries.map(entry => {
                                return (
                                    <Option key={entry.value} value={entry.value}>
                                        {entry.label}
                                    </Option>
                                );
                            })}
                        </OptionGroup>
                    );
                })}
            </Select>
        </div>
    );
}

export function VariableTransformControls({
    transformerId,
    onTransformerIdChange,
    transformOptions,
    hasIncompatibleTransformerSelection,
    selectedTransformer,
    transformerParams,
    onTransformerParamChange,
}) {
    const t = useTranslation();

    if (transformOptions.length === 0) {
        return null;
    }

    const activeParams = (selectedTransformer?.params ?? []).filter((param) => {
        if (!param.showWhen) {
            return true;
        }

        const dependencyValue = transformerParams[param.showWhen.param] ?? '';
        return dependencyValue === param.showWhen.equals;
    });
    const groupedTransformOptions = transformOptions.reduce((groups, option) => {
        const typeKey = option.appliesTo?.[0] || 'other';

        if (!groups[typeKey]) {
            groups[typeKey] = [];
        }

        groups[typeKey].push(option);
        return groups;
    }, {});
    const groupOrder = ['text', 'number', 'date', 'boolean', 'other'];
    const groupLabelByType = {
        text: t('Text'),
        number: t('Number'),
        date: t('Date'),
        boolean: t('Boolean'),
        other: t('Other'),
    };

    // Match kit v1: "None" is a real list option (no clearable X on the trigger).
    const selectValue = transformerId ?? TRANSFORM_NONE_VALUE;

    return (
        <>
            <label className="text-[11px] text-gray-500 block mt-2 mb-1">
                {t('Transform (optional)')}
            </label>
            <Select
                value={selectValue}
                size="sm"
                width="full"
                placeholder={t('None')}
                onPkChange={(event) => {
                    onTransformerIdChange(String(event.detail?.value ?? TRANSFORM_NONE_VALUE));
                }}
            >
                <Option value={TRANSFORM_NONE_VALUE}>{t('None')}</Option>
                {groupOrder.map((typeKey) => {
                    const options = groupedTransformOptions[typeKey] ?? [];
                    if (options.length === 0) {
                        return null;
                    }

                    return (
                        <OptionGroup key={typeKey} label={groupLabelByType[typeKey] ?? typeKey}>
                            {options.map(option => {
                                return (
                                    <Option key={`${typeKey}-${option.value}`} value={option.value}>
                                        {option.label}
                                    </Option>
                                );
                            })}
                        </OptionGroup>
                    );
                })}
            </Select>
            {hasIncompatibleTransformerSelection && (
                <p className="mt-1 text-[11px] text-amber-700">
                    {t('The selected transform is not compatible with this variable.')}
                </p>
            )}
            {activeParams.length > 0 && (
                <div className="mt-2 space-y-2">
                    {activeParams.map(param => {
                        return (
                            <div key={param.name}>
                                <label className="text-[11px] text-gray-500 block mb-1">
                                    {param.label}
                                </label>
                                <TransformParamField
                                    param={param}
                                    value={transformerParams[param.name] ?? ''}
                                    onChange={(value) => {
                                        onTransformerParamChange(param.name, value);
                                        if (param.name === 'preset' && value !== 'custom') {
                                            onTransformerParamChange('pattern', '');
                                        }
                                    }}
                                    t={t}
                                />
                            </div>
                        );
                    })}
                </div>
            )}
        </>
    );
}
