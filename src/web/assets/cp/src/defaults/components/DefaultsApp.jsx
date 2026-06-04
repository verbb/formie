import {
    useCallback, useEffect, useMemo, useRef, useState,
} from 'react';

import {
    PaneTabs,
    PaneTabsContent,
    PaneTabsList,
    PaneTabsTrigger,
    SelectInput,
} from '@verbb/plugin-kit-react/components';
import { FieldLayout } from '@verbb/plugin-kit-react/forms/Field';
import { SchemaFormEngine, useSchemaFormEngine } from '@verbb/plugin-kit-react/forms';

const TABS = [
    { id: 'form', label: 'Form Defaults' },
    { id: 'fields', label: 'Field Defaults' },
    { id: 'notifications', label: 'Notification Defaults' },
    { id: 'integrations', label: 'Integration Defaults' },
];

const setAtPath = (values, path, value) => {
    const keys = path.split('.');
    const next = { ...values };
    let cursor = next;

    keys.forEach((key, index) => {
        if (index === keys.length - 1) {
            cursor[key] = value;
            return;
        }

        cursor[key] = { ...(cursor[key] || {}) };
        cursor = cursor[key];
    });

    return next;
};

const getAtPath = (values, path, fallback = '') => {
    return path.split('.').reduce((carry, key) => {
        return carry && Object.prototype.hasOwnProperty.call(carry, key) ? carry[key] : undefined;
    }, values) ?? fallback;
};

const DefaultsSectionIntro = ({ title, description }) => {
    return (
        <div>
            <h3 className="formie-defaults-section-title">{title}</h3>
            <p className="formie-defaults-section-description">{description}</p>
        </div>
    );
};

const SchemaDefaultsPanel = ({
    panelKey, schema, schemaIndex, values, onChange,
}) => {
    const hasHandledInitialChangeRef = useRef(false);
    const initialValues = useMemo(() => {
        return values || {};
    }, [panelKey, values]);

    const form = useSchemaFormEngine({
        schema: schema || [],
        schemaIndex: schemaIndex || null,
        defaultValues: initialValues,
        onChange: (data) => {
            if (!hasHandledInitialChangeRef.current) {
                hasHandledInitialChangeRef.current = true;

                if (JSON.stringify(data || {}) === JSON.stringify(initialValues || {})) {
                    return;
                }
            }

            onChange(data || {});
        },
    });

    useEffect(() => {
        hasHandledInitialChangeRef.current = false;
        form.store.reset(initialValues);
    }, [panelKey, form, initialValues]);

    if (!schema?.length) {
        return null;
    }

    return <SchemaFormEngine form={form} withoutForm className="space-y-4" />;
};

export const DefaultsApp = ({ settings }) => {
    const [values, setValues] = useState(settings.values || {});
    const [selectedFieldType, setSelectedFieldType] = useState(settings.initialFieldType || settings.fieldTypes?.[0]?.type || '');
    const [isSaving, setIsSaving] = useState(false);
    const valuesRef = useRef(values);
    const isSavingRef = useRef(false);

    valuesRef.current = values;

    const options = settings.options || {};
    const fieldTypes = settings.fieldTypes || [];
    const integrationCaptchas = options.integrationCaptchas || [];

    const selectedFieldTypeConfig = useMemo(() => {
        return fieldTypes.find((fieldType) => {
            return fieldType.type === selectedFieldType;
        }) || null;
    }, [fieldTypes, selectedFieldType]);

    const fieldTypeOptions = useMemo(() => {
        return fieldTypes.map((fieldType) => {
            return {
                label: fieldType.label,
                value: fieldType.type,
            };
        });
    }, [fieldTypes]);

    const inheritBooleanOptions = useMemo(() => {
        return [
            { label: Craft.t('formie', 'Inherit'), value: '' },
            { label: Craft.t('app', 'Yes'), value: '1' },
            { label: Craft.t('app', 'No'), value: '0' },
        ];
    }, []);

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

    const handleSave = useCallback(async () => {
        if (isSavingRef.current) {
            return;
        }

        isSavingRef.current = true;
        setIsSaving(true);

        try {
            const response = await Craft.sendActionRequest('POST', settings.saveAction, {
                data: {
                    settings: valuesRef.current,
                    redirect: settings.redirect,
                },
            });

            if (response?.data?.errors) {
                Craft.cp.displayError(Craft.t('formie', 'Couldn’t save settings.'));
                return;
            }

            Craft.cp.displayNotice(Craft.t('formie', 'Settings saved.'));
        } catch (error) {
            console.error('Failed to save defaults settings.', error);
            Craft.cp.displayError(Craft.t('formie', 'Couldn’t save settings.'));
        } finally {
            isSavingRef.current = false;
            setIsSaving(false);
        }
    }, [settings.redirect, settings.saveAction]);

    useEffect(() => {
        const form = document.getElementById('main-form');

        if (!form) {
            return undefined;
        }

        const onSubmit = (event) => {
            event.preventDefault();
            handleSave();
        };

        form.addEventListener('submit', onSubmit);

        return () => {
            form.removeEventListener('submit', onSubmit);
        };
    }, [handleSave]);

    useEffect(() => {
        const submitButton = document.querySelector('#main-form button.submit[type="submit"]');

        if (!submitButton) {
            return;
        }

        submitButton.disabled = isSaving;
        submitButton.textContent = isSaving ? Craft.t('formie', 'Saving…') : Craft.t('app', 'Save');
    }, [isSaving]);

    return (
        <div className="formie-defaults-app">
            <PaneTabs defaultValue="form" className="w-full">
                <PaneTabsList aria-label={Craft.t('formie', 'Defaults sections')}>
                    {TABS.map((tab) => {
                        return (
                            <PaneTabsTrigger key={tab.id} value={tab.id}>
                                {Craft.t('formie', tab.label)}
                            </PaneTabsTrigger>
                        );
                    })}
                </PaneTabsList>

                <PaneTabsContent value="form" className="formie-defaults-panel">
                    <DefaultsSectionIntro
                        title={Craft.t('formie', 'New Form Defaults')}
                        description={Craft.t('formie', 'These values are applied when a new form or stencil is created. Choosing a stencil when creating a form always overrides these defaults.')}
                    />

                    <FieldLayout
                        name="defaultFormStencil"
                        label={Craft.t('formie', 'Default Stencil')}
                        instructions={Craft.t('formie', 'Optionally apply a stencil automatically when creating a new form without choosing one.')}
                    >
                        <SelectInput
                            value={getAtPath(values, 'defaultFormStencil')}
                            options={options.stencils || []}
                            onChange={(value) => { updateValue('defaultFormStencil', String(value ?? '')); }}
                        />
                    </FieldLayout>

                    <FieldLayout
                        name="defaultFormTemplate"
                        label={Craft.t('formie', 'Formatting Template')}
                        instructions={Craft.t('formie', 'Select a form template to be used as the default for all new forms.')}
                    >
                        <SelectInput
                            value={getAtPath(values, 'defaultFormTemplate')}
                            options={options.formTemplates || []}
                            onChange={(value) => { updateValue('defaultFormTemplate', String(value ?? '')); }}
                        />
                    </FieldLayout>

                    <SchemaDefaultsPanel
                        panelKey="form-defaults"
                        schema={settings.formDefaultsSchema || []}
                        schemaIndex={settings.formDefaultsSchemaIndex || null}
                        values={values.formDefaults || {}}
                        onChange={updateFormDefaults}
                    />
                </PaneTabsContent>

                <PaneTabsContent value="fields" className="formie-defaults-panel">
                    <DefaultsSectionIntro
                        title={Craft.t('formie', 'Field Defaults')}
                        description={Craft.t('formie', 'Configure defaults applied when authors add new fields to a form.')}
                    />

                    <FieldLayout
                        name="defaultLabelPosition"
                        label={Craft.t('formie', 'Default Label Position')}
                        instructions={Craft.t('formie', 'Fields will by default have their label position set to this option.')}
                    >
                        <SelectInput
                            value={getAtPath(values, 'defaultLabelPosition')}
                            options={options.labelPositions || []}
                            onChange={(value) => { updateValue('defaultLabelPosition', String(value ?? '')); }}
                        />
                    </FieldLayout>

                    <FieldLayout
                        name="defaultInstructionsPosition"
                        label={Craft.t('formie', 'Default Instructions Position')}
                        instructions={Craft.t('formie', 'Fields will by default have their instructions position set to this option.')}
                    >
                        <SelectInput
                            value={getAtPath(values, 'defaultInstructionsPosition')}
                            options={options.instructionsPositions || []}
                            onChange={(value) => { updateValue('defaultInstructionsPosition', String(value ?? '')); }}
                        />
                    </FieldLayout>

                    {fieldTypeOptions.length ? (
                        <>
                            <FieldLayout
                                name="selectedFieldType"
                                label={Craft.t('formie', 'Field Type')}
                                instructions={Craft.t('formie', 'Choose a field type to configure its default settings.')}
                            >
                                <SelectInput
                                    value={selectedFieldType}
                                    options={fieldTypeOptions}
                                    onChange={(value) => { setSelectedFieldType(String(value ?? '')); }}
                                />
                            </FieldLayout>

                            <SchemaDefaultsPanel
                                panelKey={selectedFieldType}
                                schema={selectedFieldTypeConfig?.schema || []}
                                schemaIndex={selectedFieldTypeConfig?.schemaIndex || null}
                                values={(values.fieldDefaults || {})[selectedFieldType] || {}}
                                onChange={(fieldValues) => { updateFieldDefaults(selectedFieldType, fieldValues); }}
                            />
                        </>
                    ) : (
                        <p className="formie-defaults-note">
                            {Craft.t('formie', 'No field types expose default settings yet.')}
                        </p>
                    )}
                </PaneTabsContent>

                <PaneTabsContent value="notifications" className="formie-defaults-panel">
                    <DefaultsSectionIntro
                        title={Craft.t('formie', 'Notification Defaults')}
                        description={Craft.t('formie', 'Set defaults applied when a new notification is created. Leave fields empty to inherit Formie’s built-in behaviour.')}
                    />

                    <FieldLayout
                        name="defaultEmailTemplate"
                        label={Craft.t('formie', 'Default Email Template')}
                        instructions={Craft.t('formie', 'Select an email template to be used as the default for all new notifications.')}
                    >
                        <SelectInput
                            value={getAtPath(values, 'defaultEmailTemplate')}
                            options={options.emailTemplates || []}
                            onChange={(value) => { updateValue('defaultEmailTemplate', String(value ?? '')); }}
                        />
                    </FieldLayout>

                    <SchemaDefaultsPanel
                        panelKey="notification-defaults"
                        schema={settings.notificationDefaultsSchema || []}
                        schemaIndex={settings.notificationDefaultsSchemaIndex || null}
                        values={values.notificationDefaults || {}}
                        onChange={updateNotificationDefaults}
                    />
                </PaneTabsContent>

                <PaneTabsContent value="integrations" className="formie-defaults-panel">
                    <DefaultsSectionIntro
                        title={Craft.t('formie', 'Integration Defaults')}
                        description={Craft.t('formie', 'Control whether captcha integrations are enabled by default on new forms. Inherit uses each integration’s global enabled state.')}
                    />

                    {integrationCaptchas.length ? integrationCaptchas.map((captcha) => {
                        const path = `integrationDefaults.captchas.${captcha.handle}`;

                        return (
                            <FieldLayout
                                key={captcha.handle}
                                name={path}
                                label={captcha.label}
                                instructions={Craft.t('formie', 'Whether this captcha should be enabled when a new form is created.')}
                            >
                                <SelectInput
                                    value={getAtPath(values, path, '')}
                                    options={inheritBooleanOptions}
                                    onChange={(value) => { updateValue(path, String(value ?? '')); }}
                                />
                            </FieldLayout>
                        );
                    }) : (
                        <p className="formie-defaults-note">
                            {Craft.t('formie', 'No captcha integrations with form settings are available.')}
                        </p>
                    )}
                </PaneTabsContent>
            </PaneTabs>
        </div>
    );
};
