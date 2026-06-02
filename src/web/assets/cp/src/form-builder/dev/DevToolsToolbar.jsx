import { useMemo, useState } from 'react';
import { getDevToolsConfig, setDevToolsConfig } from '@form-builder/dev/config';
import { parseStressPattern } from '@form-builder/dev/scenarios/stressTestScenario';

const ModeSelect = ({ value, onChange, disabled }) => {
    return (
        <select
            className="border border-gray-300 rounded px-2 py-1 text-xs w-full"
            value={value}
            onChange={(event) => { onChange(event.target.value); }}
            disabled={disabled}
        >
            <option value="none">None</option>
            <option value="fieldPreview">Field Preview Matrix</option>
            <option value="stressTest">Field Layout Stress Test</option>
            <option value="existingFieldsStress">Existing Fields Stress Test</option>
        </select>
    );
};

const PreviewValueModeSelect = ({ value, onChange, disabled }) => {
    return (
        <select
            className="border border-gray-300 rounded px-2 py-1 text-xs w-full"
            value={value}
            onChange={(event) => { onChange(event.target.value); }}
            disabled={disabled}
        >
            <option value="normal">Normal</option>
            <option value="placeholder">Force Placeholder</option>
            <option value="default">Force Default Value</option>
            <option value="empty">Force Empty</option>
        </select>
    );
};

export const DevToolsToolbar = () => {
    const initialConfig = useMemo(() => { return getDevToolsConfig(); }, []);
    const [expanded, setExpanded] = useState(false);
    const [enabled, setEnabled] = useState(initialConfig.enabled);
    const [mode, setMode] = useState(initialConfig.mode);
    const [stressPattern, setStressPattern] = useState(initialConfig.stressPattern);
    const [previewValueMode, setPreviewValueMode] = useState(initialConfig.previewValueMode || 'normal');
    const [autoOpenFirstField, setAutoOpenFirstField] = useState(Boolean(initialConfig.autoOpenFirstField));
    const [autoOpenFirstNotification, setAutoOpenFirstNotification] = useState(Boolean(initialConfig.autoOpenFirstNotification));
    const [autoOpenPageSettings, setAutoOpenPageSettings] = useState(Boolean(initialConfig.autoOpenPageSettings));
    const [autoOpenExistingFields, setAutoOpenExistingFields] = useState(Boolean(initialConfig.autoOpenExistingFields));
    const [showExpandedDropzoneHitboxes, setShowExpandedDropzoneHitboxes] = useState(Boolean(initialConfig.showExpandedDropzoneHitboxes));
    const [showRowAndFieldIds, setShowRowAndFieldIds] = useState(Boolean(initialConfig.showRowAndFieldIds));
    const [showDropzoneRegistryDebugPanel, setShowDropzoneRegistryDebugPanel] = useState(Boolean(initialConfig.showDropzoneRegistryDebugPanel));
    const [showExpandedNestedDropzoneHitboxes, setShowExpandedNestedDropzoneHitboxes] = useState(Boolean(initialConfig.showExpandedNestedDropzoneHitboxes));
    const [showNestedRowAndFieldIds, setShowNestedRowAndFieldIds] = useState(Boolean(initialConfig.showNestedRowAndFieldIds));
    const [existingFieldsPattern, setExistingFieldsPattern] = useState(initialConfig.existingFieldsPattern || '100x5x24');

    const stressError = mode === 'stressTest' && !parseStressPattern(stressPattern)
        ? 'Use NxRxF (e.g. 20x5x5).'
        : '';
    const shouldValidateExistingFieldsPattern = mode === 'existingFieldsStress';
    const existingFieldsPatternError = shouldValidateExistingFieldsPattern && !parseStressPattern(existingFieldsPattern)
        ? 'Use NxRxF (e.g. 100x5x24).'
        : '';

    const saveAndReload = (next) => {
        setDevToolsConfig(next);
        window.location.reload();
    };

    const apply = () => {
        const next = {
            enabled,
            mode: enabled ? mode : 'none',
            stressPattern,
            previewValueMode,
            autoOpenFirstField,
            autoOpenFirstNotification,
            autoOpenPageSettings,
            autoOpenExistingFields,
            showExpandedDropzoneHitboxes,
            showRowAndFieldIds,
            showDropzoneRegistryDebugPanel,
            showExpandedNestedDropzoneHitboxes,
            showNestedRowAndFieldIds,
            existingFieldsPattern,
        };

        saveAndReload(next);
    };

    const reset = () => {
        const next = {
            enabled: false,
            mode: 'none',
            stressPattern: '20x5x5',
            previewValueMode: 'normal',
            autoOpenFirstField: false,
            autoOpenFirstNotification: false,
            autoOpenPageSettings: false,
            autoOpenExistingFields: false,
            showExpandedDropzoneHitboxes: false,
            showRowAndFieldIds: false,
            showDropzoneRegistryDebugPanel: false,
            showExpandedNestedDropzoneHitboxes: false,
            showNestedRowAndFieldIds: false,
            existingFieldsPattern: '100x5x24',
        };

        setEnabled(next.enabled);
        setMode(next.mode);
        setStressPattern(next.stressPattern);
        setPreviewValueMode(next.previewValueMode);
        setAutoOpenFirstField(next.autoOpenFirstField);
        setAutoOpenFirstNotification(next.autoOpenFirstNotification);
        setAutoOpenPageSettings(next.autoOpenPageSettings);
        setAutoOpenExistingFields(next.autoOpenExistingFields);
        setShowExpandedDropzoneHitboxes(next.showExpandedDropzoneHitboxes);
        setShowRowAndFieldIds(next.showRowAndFieldIds);
        setShowDropzoneRegistryDebugPanel(next.showDropzoneRegistryDebugPanel);
        setShowExpandedNestedDropzoneHitboxes(next.showExpandedNestedDropzoneHitboxes);
        setShowNestedRowAndFieldIds(next.showNestedRowAndFieldIds);
        setExistingFieldsPattern(next.existingFieldsPattern);
        saveAndReload(next);
    };

    return (
        <div className="fixed bottom-3 right-3 z-[9999] pointer-events-auto">
            <div className="rounded-md border border-gray-300 bg-white shadow-md min-w-[260px]">
                <button
                    type="button"
                    className="w-full text-left px-3 py-2 text-xs font-semibold border-b border-gray-200"
                    onClick={() => { setExpanded((value) => { return !value; }); }}
                >
                    Formie Dev Tools {expanded ? '▲' : '▼'}
                </button>

                {expanded && (
                    <div className="p-3 space-y-2 text-xs">
                        <label className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                checked={enabled}
                                onChange={(event) => { setEnabled(event.target.checked); }}
                            />
                            Enable dev scenarios
                        </label>

                        <div className="space-y-1">
                            <div className="text-gray-600">Scenario</div>
                            <ModeSelect
                                value={mode}
                                onChange={setMode}
                                disabled={!enabled}
                            />
                        </div>

                        {mode === 'fieldPreview' && (
                            <div className="space-y-1">
                                <div className="text-gray-600">Preview Values</div>
                                <PreviewValueModeSelect
                                    value={previewValueMode}
                                    onChange={setPreviewValueMode}
                                    disabled={!enabled}
                                />
                            </div>
                        )}

                        <div className="space-y-1 pt-1">
                            <div className="text-gray-600">Auto Open</div>

                            <label className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={autoOpenFirstField}
                                    onChange={(event) => { setAutoOpenFirstField(event.target.checked); }}
                                    disabled={!enabled}
                                />
                                First field (page 1, row 1, col 1)
                            </label>

                            <label className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={autoOpenFirstNotification}
                                    onChange={(event) => { setAutoOpenFirstNotification(event.target.checked); }}
                                    disabled={!enabled}
                                />
                                First email notification
                            </label>

                            <label className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={autoOpenPageSettings}
                                    onChange={(event) => { setAutoOpenPageSettings(event.target.checked); }}
                                    disabled={!enabled}
                                />
                                Page settings
                            </label>

                            <label className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={autoOpenExistingFields}
                                    onChange={(event) => { setAutoOpenExistingFields(event.target.checked); }}
                                    disabled={!enabled}
                                />
                                Existing fields modal
                            </label>
                        </div>

                        <div className="space-y-1 pt-1">
                            <div className="text-gray-600">Drag + Drop</div>

                            <label className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={showExpandedDropzoneHitboxes}
                                    onChange={(event) => { setShowExpandedDropzoneHitboxes(event.target.checked); }}
                                    disabled={!enabled}
                                />
                                Show top-level expanded dropzone hitboxes
                            </label>

                            <label className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={showRowAndFieldIds}
                                    onChange={(event) => { setShowRowAndFieldIds(event.target.checked); }}
                                    disabled={!enabled}
                                />
                                Show top-level row + field ids
                            </label>

                            <label className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={showDropzoneRegistryDebugPanel}
                                    onChange={(event) => { setShowDropzoneRegistryDebugPanel(event.target.checked); }}
                                    disabled={!enabled}
                                />
                                Show top-level DnD registry panel
                            </label>

                            <label className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={showExpandedNestedDropzoneHitboxes}
                                    onChange={(event) => { setShowExpandedNestedDropzoneHitboxes(event.target.checked); }}
                                    disabled={!enabled}
                                />
                                Show nested expanded dropzone hitboxes
                            </label>

                            <label className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={showNestedRowAndFieldIds}
                                    onChange={(event) => { setShowNestedRowAndFieldIds(event.target.checked); }}
                                    disabled={!enabled}
                                />
                                Show nested row + field ids
                            </label>
                        </div>

                        {mode === 'stressTest' && (
                            <div className="space-y-3">
                                <div className="space-y-1">
                                    <div className="text-gray-600">Form Layout Stress Pattern</div>
                                    <input
                                        className="border border-gray-300 rounded px-2 py-1 w-full"
                                        value={stressPattern}
                                        onChange={(event) => { setStressPattern(event.target.value); }}
                                        placeholder="20x5x5"
                                        disabled={!enabled}
                                    />
                                </div>

                                {(stressError || existingFieldsPatternError) && (
                                    <div className="text-red-600">
                                        {stressError || existingFieldsPatternError}
                                    </div>
                                )}
                            </div>
                        )}

                        {mode === 'existingFieldsStress' && (
                            <div className="space-y-3">
                                <div className="space-y-1">
                                    <div className="text-gray-600">Existing Fields Pattern</div>
                                    <input
                                        className="border border-gray-300 rounded px-2 py-1 w-full"
                                        value={existingFieldsPattern}
                                        onChange={(event) => { setExistingFieldsPattern(event.target.value); }}
                                        placeholder="100x5x24"
                                        disabled={!enabled}
                                    />
                                </div>

                                {existingFieldsPatternError && (
                                    <div className="text-red-600">
                                        {existingFieldsPatternError}
                                    </div>
                                )}
                            </div>
                        )}

                        <div className="flex gap-2 pt-1">
                            <button
                                type="button"
                                className="px-2 py-1 border border-gray-300 rounded"
                                onClick={apply}
                                disabled={Boolean(stressError || existingFieldsPatternError)}
                            >
                                Apply
                            </button>

                            <button
                                type="button"
                                className="px-2 py-1 border border-gray-300 rounded"
                                onClick={reset}
                            >
                                Reset
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
};
