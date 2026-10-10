import { Lightswitch } from '@verbb/plugin-kit-react/components';
import { FieldLayout, useEngineField } from '@verbb/plugin-kit-react/forms';

export const isContentEncryptionControlDisabled = (value, field) => {
    return Boolean(field.disabled || (field.disabledWhenOff && !value));
};

export function ContentEncryptionField({ form, field }) {
    const { value, setValue, errors } = useEngineField(form, field.name);
    const disabled = isContentEncryptionControlDisabled(value, field);

    return (
        <FieldLayout
            name={field.name}
            label={field.label}
            instructions={field.instructions}
            warning={field.warning}
            required={field.required}
            errors={errors}
        >
            <Lightswitch
                checked={Boolean(value)}
                disabled={disabled}
                onCheckedChange={(checked) => {
                    if (!disabled) {
                        setValue(checked);
                    }
                }}
                aria-label={field.label}
            />
        </FieldLayout>
    );
}
