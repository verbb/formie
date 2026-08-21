import { useState } from 'react';
import { Icon, Input } from '@verbb/plugin-kit-react/components';
import { useEngineField, FieldLayout } from '@verbb/plugin-kit-react/forms';
import { buildUniqueHandleFromSource } from '@verbb/plugin-kit-core';
import { cn } from '@verbb/plugin-kit-react/utils';
import { getRichTextText } from '@utils/tiptapUtils';

/**
 * Reduce TipTap / variablePicker values (arrays, docs, or JSON strings) to plain text.
 * Mirrors useHandleSyncOnChange so refresh matches live name→handle sync.
 */
const normalizeHandleSourceValue = (value) => {
    if (value == null) {
        return value;
    }

    let candidate = value;

    if (typeof value === 'string') {
        const trimmed = value.trim();

        if (trimmed.startsWith('[') || trimmed.startsWith('{')) {
            try {
                candidate = JSON.parse(trimmed);
            } catch {
                return value;
            }
        } else {
            return value;
        }
    }

    if (typeof candidate === 'object') {
        const text = getRichTextText(candidate)
            .replace(/\{[^}]*\}/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();

        if (text) {
            return text;
        }
    }

    return value;
};

/**
 * Formie-owned SchemaForm `$field: 'handle'` — was a kit v1 builtin.
 * Regenerates from `field.source` via kit-core handle helpers.
 *
 * Full-width stock Input with a transparent overlay refresh control (no filled end-cap) so
 * focus chrome matches every other text field.
 */
export function HandleField({ form, field }) {
    const {
        value, setValue, setTouched, errors, isInvalid,
    } = useEngineField(form, field.name);
    const [rotate, setRotate] = useState(0);

    const refreshHandle = (event) => {
        event.preventDefault();

        if (field.disabled) {
            return;
        }

        setRotate((current) => current + 180);

        if (!field.source) {
            return;
        }

        const uniqueHandle = buildUniqueHandleFromSource({
            sourceValue: normalizeHandleSourceValue(form.getFieldValue(field.source)),
            values: form.store.state.values ?? {},
            reservedHandles: field.reservedHandles || [],
            reservedFieldValues: field.reservedFieldValues || [],
            maxLength: field.maxLength,
        });
        setValue(uniqueHandle);
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
            <div className="relative w-full min-w-0">
                <Input
                    value={String(value ?? '')}
                    onChange={(event) => {
                        setValue(event.target.value);
                    }}
                    onBlur={setTouched}
                    placeholder={field.placeholder}
                    disabled={field.disabled}
                    invalid={isInvalid}
                    mono
                    className="w-full [&::part(input)]:pe-9"
                />
                <button
                    type="button"
                    className={cn(
                        'absolute top-1/2 right-0 z-10 p-[7px_10px]',
                        'border-0 bg-transparent',
                        field.disabled ? 'cursor-not-allowed opacity-30' : 'cursor-pointer opacity-50 hover:opacity-100',
                        'text-[#606d7b]',
                        'select-none',
                        'transition-transform duration-500 ease-in-out',
                    )}
                    style={{ transform: `translateY(-50%) rotate(${rotate}deg)` }}
                    onClick={refreshHandle}
                    disabled={field.disabled}
                    aria-label={Craft.t('formie', 'Regenerate handle')}
                    tabIndex={-1}
                >
                    <Icon icon="arrows-rotate" className="block size-3.5" />
                </button>
            </div>
        </FieldLayout>
    );
}
