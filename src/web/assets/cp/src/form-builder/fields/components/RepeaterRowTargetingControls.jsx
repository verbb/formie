import { useEffect, useRef, useState } from 'react';
import { useTranslation } from '@verbb/plugin-kit-react/hooks';
import { Input, SelectInput } from '@verbb/plugin-kit-react/components';
import {
    getRepeaterRowPresetOptions,
    parseRepeaterRowTargeting,
    shouldShowRepeaterRowTargeting,
} from '@form-builder/fields/utils/repeaterRowTargeting';

const stopEditorKeyPropagation = (event) => {
    event.stopPropagation();
};

export function RepeaterRowTargetingControls({
    tokenValue = '',
    variableOption = null,
    onTargetingChange,
    targetingRef = null,
    resetKey = '',
}) {
    const t = useTranslation();
    const localTargetingRef = useRef(null);
    const onTargetingChangeRef = useRef(onTargetingChange);
    const lastResetKeyRef = useRef('');

    onTargetingChangeRef.current = onTargetingChange;

    const [localTargeting, setLocalTargeting] = useState(() => {
        const initial = parseRepeaterRowTargeting(tokenValue);
        localTargetingRef.current = initial;

        return initial;
    });

    const syncTargeting = (nextTargeting) => {
        localTargetingRef.current = nextTargeting;

        if (targetingRef) {
            targetingRef.current = nextTargeting;
        }
    };

    useEffect(() => {
        if (resetKey === 'closed') {
            lastResetKeyRef.current = '';
            return;
        }

        if (lastResetKeyRef.current === resetKey) {
            return;
        }

        lastResetKeyRef.current = resetKey;
        const parsed = parseRepeaterRowTargeting(tokenValue);
        setLocalTargeting(parsed);
        syncTargeting(parsed);
    }, [resetKey, tokenValue]);

    if (!shouldShowRepeaterRowTargeting(tokenValue, variableOption)) {
        return null;
    }

    const presetOptions = getRepeaterRowPresetOptions(t);
    const showIndexInput = localTargeting.preset === 'index';
    const showCustomInput = localTargeting.preset === 'custom';

    const updateTargeting = (patch) => {
        const nextTargeting = {
            ...(localTargetingRef.current || localTargeting),
            ...patch,
        };

        setLocalTargeting(nextTargeting);
        syncTargeting(nextTargeting);
        onTargetingChangeRef.current?.(nextTargeting);
    };

    return (
        <div className="mb-3">
            <label className="mb-1 block text-[11px] text-gray-500">
                {t('Rows')}
            </label>
            <SelectInput
                size="sm"
                value={localTargeting.preset}
                onChange={(nextPreset) => {
                    const preset = String(nextPreset || 'first');
                    const current = localTargetingRef.current || localTargeting;
                    updateTargeting({
                        preset,
                        index: preset === 'index' ? (current.index || '1') : '',
                        rowsExpression: preset === 'custom' ? (current.rowsExpression || '') : '',
                    });
                }}
                options={presetOptions}
            />

            {showIndexInput && (
                <div className="mt-2">
                    <label className="mb-1 block text-[11px] text-gray-500">
                        {t('Row number')}
                    </label>
                    <Input
                        type="number"
                        min={1}
                        value={localTargeting.index}
                        onChange={(event) => {
                            updateTargeting({ index: event.target.value });
                        }}
                        onKeyDown={stopEditorKeyPropagation}
                        onKeyUp={stopEditorKeyPropagation}
                    />
                </div>
            )}

            {showCustomInput && (
                <div className="mt-2">
                    <label className="mb-1 block text-[11px] text-gray-500">
                        {t('Row selection')}
                    </label>
                    <Input
                        type="text"
                        value={localTargeting.rowsExpression}
                        placeholder={t('e.g. 1,3,5 or 1-3,5 or even or every:2')}
                        onChange={(event) => {
                            updateTargeting({ rowsExpression: event.target.value });
                        }}
                        onKeyDown={stopEditorKeyPropagation}
                        onKeyUp={stopEditorKeyPropagation}
                    />
                    <p className="mt-1 text-[10px] leading-snug text-gray-500">
                        {t('Use 1-based row numbers. Supports comma lists, ranges, even, odd, and every:N.')}
                    </p>
                </div>
            )}
        </div>
    );
}
