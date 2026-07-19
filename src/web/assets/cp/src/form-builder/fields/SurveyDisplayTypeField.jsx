import { evaluateCondition } from '@verbb/plugin-kit-forms';
import { useEngineField, FieldLayout } from '@verbb/plugin-kit-react/forms';
import { SelectInput } from '@verbb/plugin-kit-react/components';
import { useMemo, useSyncExternalStore } from 'react';

import { getSurveyDisplayDefaultOptions } from '@form-builder/utils/surveyDisplayDefaults';

function SurveyDisplayTypeField({ form, field }) {
    const {
        value, setValue, setTouched, errors, isInvalid,
    } = useEngineField(form, field.name);
    const { setValue: setOptions } = useEngineField(form, 'options');
    const values = useSyncExternalStore(
        form.store.subscribe.bind(form.store),
        () => { return form.store.state.values; },
        () => { return form.store.state.values; },
    );

    const conditionData = useMemo(() => {
        const scopePath = typeof field._scopePath === 'string' ? field._scopePath : '';
        const scopedValues = scopePath ? form?.getFieldValue?.(scopePath) : null;
        const scopedObject = scopedValues && typeof scopedValues === 'object' ? scopedValues : {};
        const fieldData = (field._data && typeof field._data === 'object') ? field._data : {};

        return {
            ...(values || {}),
            ...scopedObject,
            ...fieldData,
        };
    }, [field, form, values]);

    const filteredOptions = useMemo(() => {
        const options = Array.isArray(field.options) ? field.options : [];

        return options.filter((option) => {
            if (!option?.if) {
                return true;
            }

            return evaluateCondition(option.if, conditionData);
        });
    }, [conditionData, field.options]);

    const handleChange = (nextDisplayType) => {
        setValue(nextDisplayType);

        const currentOptions = form.getFieldValue?.('options');
        const defaults = getSurveyDisplayDefaultOptions(nextDisplayType);

        if (defaults && (!Array.isArray(currentOptions) || currentOptions.length === 0)) {
            setOptions(defaults.map((option) => ({ ...option })));
        }
    };

    return (
        <FieldLayout
            name={field.name}
            label={field.label}
            instructions={field.instructions}
            warning={field.warning}
            required={field.required}
            errors={errors}
        >
            <SelectInput
                options={filteredOptions}
                placeholder={field.placeholder}
                value={value ?? ''}
                onChange={handleChange}
                isInvalid={isInvalid}
                onBlur={setTouched}
                disabled={field.disabled}
            />
        </FieldLayout>
    );
}

export { SurveyDisplayTypeField };
