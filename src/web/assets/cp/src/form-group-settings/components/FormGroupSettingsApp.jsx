import { useEffect, useMemo, useState } from 'react';

import {
    ALL_VALUE,
    CheckboxSelect,
    Input,
    Lightswitch,
    PaneTabs,
    PaneTabsContent,
    PaneTabsList,
    PaneTabsTrigger,
    SelectInput,
} from '@verbb/plugin-kit-react/components';
import { FieldLayout } from '@verbb/plugin-kit-react/forms';
import { useCpFormPayloadSync } from '@utils';
import { FormieErrorsPane } from '@utils/FormieErrorsPane';

import { DefaultsSectionIntro } from '@defaults/components/DefaultsSectionIntro';
import { DefaultsTabPanels } from '@defaults/components/DefaultsTabPanels';
import { setAtPath } from '@defaults/utils/defaultsEditorState';
import { FieldPaletteApp } from '@field-palette/components/FieldPaletteApp';

const DEFAULT_TABS = [
    { id: 'form', label: 'Form Defaults' },
    { id: 'fields', label: 'Field Defaults' },
    { id: 'validation', label: 'Validation Messages' },
    { id: 'notifications', label: 'Notification Defaults' },
    { id: 'integrations', label: 'Integration Defaults' },
];

const syncHiddenInput = (inputId, value) => {
    const input = document.getElementById(inputId);

    if (!input) {
        return;
    }

    input.value = value;
    input.dispatchEvent(new Event('change', { bubbles: true }));
};

const getFieldErrors = (errors, field) => {
    const value = errors?.[field];

    if (Array.isArray(value)) {
        return value.filter(Boolean);
    }

    return value ? [String(value)] : [];
};

const flattenModelErrors = (errors) => {
    if (!errors || typeof errors !== 'object') {
        return [];
    }

    return Object.values(errors).flatMap((value) => {
        if (Array.isArray(value)) {
            return value.filter(Boolean).map(String);
        }

        return value ? [String(value)] : [];
    });
};

export const FormGroupSettingsApp = ({ settings }) => {
    const [values, setValues] = useState(settings.values || {});
    const modelErrors = settings.errors || {};

    useCpFormPayloadSync({
        inputId: settings.payloadInputId,
        payload: values,
        onBeforeSubmit: () => {
            syncHiddenInput('form-group-name', values.name || '');
            syncHiddenInput('form-group-handle', values.handle || '');
        },
    });

    useEffect(() => {
        syncHiddenInput('form-group-name', values.name || '');
        syncHiddenInput('form-group-handle', values.handle || '');
    }, [values.name, values.handle]);

    const errorMessages = useMemo(() => {
        return flattenModelErrors(modelErrors);
    }, [modelErrors]);

    const useCustomFieldPalette = Boolean(values.useCustomFieldPalette);

    const tabs = useMemo(() => {
        return [
            { id: 'general', label: 'General' },
            { id: 'field-palette', label: 'Field Palette' },
            ...DEFAULT_TABS,
        ];
    }, []);

    const statusOptions = settings.statusOptions || [];
    const siteOptions = settings.siteOptions || [];
    const sitePropagationOptions = settings.sitePropagationOptions || [];
    const showSitePolicy = siteOptions.length > 1;

    const updateValue = (path, value) => {
        setValues((currentValues) => {
            return setAtPath(currentValues, path, value);
        });
    };

    const updateFieldDefaults = (fieldType, fieldValues) => {
        setValues((currentValues) => {
            return {
                ...currentValues,
                fieldDefaults: {
                    ...(currentValues.fieldDefaults || {}),
                    [fieldType]: fieldValues,
                },
            };
        });
    };

    const updateFormDefaults = (formValues) => {
        setValues((currentValues) => {
            return {
                ...currentValues,
                formDefaults: formValues,
            };
        });
    };

    const updateNotificationDefaults = (notificationValues) => {
        setValues((currentValues) => {
            return {
                ...currentValues,
                notificationDefaults: notificationValues,
            };
        });
    };

    const updateValidationMessageDefaults = (validationMessageDefaults) => {
        setValues((currentValues) => {
            return {
                ...currentValues,
                validationMessageDefaults,
            };
        });
    };

    const handleCustomPaletteToggle = (enabled) => {
        setValues((currentValues) => {
            const nextValues = {
                ...currentValues,
                useCustomFieldPalette: enabled,
            };

            if (enabled && !currentValues.fieldPalette) {
                nextValues.fieldPalette = settings.fieldPaletteSeed || { groups: [], unassigned: [] };
            }

            if (!enabled) {
                nextValues.fieldPalette = null;
            }

            return nextValues;
        });
    };

    return (
        <div className="formie-form-group-settings-app formie-defaults-app">
            {errorMessages.length ? (
                <div className="mb-4">
                    <FormieErrorsPane errors={errorMessages} />
                </div>
            ) : null}

            <PaneTabs defaultValue="general" className="w-full">
                <PaneTabsList aria-label={Craft.t('formie', 'Form group settings sections')}>
                    {tabs.map((tab) => {
                        return (
                            <PaneTabsTrigger key={tab.id} value={tab.id}>
                                {Craft.t('formie', tab.label)}
                            </PaneTabsTrigger>
                        );
                    })}
                </PaneTabsList>

                <PaneTabsContent value="general" className="formie-defaults-panel">
                    <DefaultsSectionIntro
                        title={Craft.t('formie', 'General')}
                        description={Craft.t('formie', 'Form group details and optional restrictions. Blank settings inherit from global Formie settings.')}
                    />

                    <FieldLayout
                        name="name"
                        label={Craft.t('formie', 'Name')}
                        instructions={Craft.t('formie', 'What this form group will be called in the control panel.')}
                        required
                        errors={getFieldErrors(modelErrors, 'name')}
                    >
                        <Input
                            value={values.name || ''}
                            onChange={(event) => { updateValue('name', event.target.value); }}
                        />
                    </FieldLayout>

                    <FieldLayout
                        name="handle"
                        label={Craft.t('formie', 'Handle')}
                        instructions={Craft.t('formie', 'How you’ll refer to this form group in the templates.')}
                        required
                        errors={getFieldErrors(modelErrors, 'handle')}
                    >
                        <Input
                            className="code"
                            value={values.handle || ''}
                            onChange={(event) => { updateValue('handle', event.target.value); }}
                        />
                    </FieldLayout>

                    <FieldLayout
                        name="allowedStatusIds"
                        label={Craft.t('formie', 'Allowed Submission Statuses')}
                        instructions={Craft.t('formie', 'Select which statuses are available for forms in this group. Choose “All” to allow every status.')}
                        errors={getFieldErrors(modelErrors, 'allowedStatusIds')}
                    >
                        <CheckboxSelect
                            value={values.allowedStatusIds ?? ALL_VALUE}
                            options={statusOptions}
                            showAllOption
                            onChange={(nextValue) => { updateValue('allowedStatusIds', nextValue); }}
                        />
                    </FieldLayout>

                    {showSitePolicy ? (
                        <>
                            <FieldLayout
                                name="sitePolicyEnabledSiteIds"
                                label={Craft.t('formie', 'Enabled Sites')}
                                instructions={Craft.t('formie', 'Choose which sites forms in this group are available on.')}
                                errors={getFieldErrors(modelErrors, 'sitePolicy')}
                            >
                                <CheckboxSelect
                                    value={values.sitePolicyEnabledSiteIds ?? ALL_VALUE}
                                    options={siteOptions.map((option) => {
                                        return {
                                            label: option.label,
                                            value: String(option.value),
                                        };
                                    })}
                                    showAllOption
                                    onChange={(nextValue) => { updateValue('sitePolicyEnabledSiteIds', nextValue); }}
                                />
                            </FieldLayout>

                            <FieldLayout
                                name="sitePolicyPropagation"
                                label={Craft.t('formie', 'Site Propagation')}
                                instructions={Craft.t('formie', 'Control how new and existing forms in this group are propagated across sites.')}
                            >
                                <SelectInput
                                    value={values.sitePolicyPropagation || 'allEnabled'}
                                    options={sitePropagationOptions}
                                    onChange={(nextValue) => { updateValue('sitePolicyPropagation', String(nextValue ?? 'allEnabled')); }}
                                />
                            </FieldLayout>
                        </>
                    ) : null}
                </PaneTabsContent>

                <DefaultsTabPanels
                    settings={settings}
                    values={values}
                    updateValue={updateValue}
                    updateFieldDefaults={updateFieldDefaults}
                    updateFormDefaults={updateFormDefaults}
                    updateNotificationDefaults={updateNotificationDefaults}
                    updateValidationMessageDefaults={updateValidationMessageDefaults}
                    context="group"
                />

                <PaneTabsContent value="field-palette" className="formie-defaults-panel">
                    <DefaultsSectionIntro
                        title={Craft.t('formie', 'Field Palette')}
                        description={Craft.t('formie', 'Organise the field types shown in the form builder. Rename groups and fields, reorder them, and disable field types you do not want authors to add.')}
                    />

                    <FieldLayout
                        name="useCustomFieldPalette"
                        label={Craft.t('formie', 'Use custom field palette')}
                        instructions={Craft.t('formie', 'When enabled, forms in this group use this palette instead of the global palette from Formie settings.')}
                    >
                        <Lightswitch
                            checked={useCustomFieldPalette}
                            onCheckedChange={handleCustomPaletteToggle}
                        />
                    </FieldLayout>

                    {useCustomFieldPalette ? (
                        <FieldPaletteApp
                            settings={{
                                canEdit: settings.canEdit !== false,
                                palette: values.fieldPalette || settings.fieldPaletteSeed || { groups: [], unassigned: [] },
                            }}
                            onPayloadChange={(palette) => { updateValue('fieldPalette', palette); }}
                        />
                    ) : (
                        <p className="formie-defaults-note">
                            {Craft.t('formie', 'Forms in this group use the global field palette from Formie settings.')}
                        </p>
                    )}
                </PaneTabsContent>
            </PaneTabs>
        </div>
    );
};
