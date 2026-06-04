import {
    useCallback, useEffect, useMemo, useRef, useState,
} from 'react';

import {
    Input,
    PaneTabs,
    PaneTabsContent,
    PaneTabsList,
    PaneTabsTrigger,
    SelectInput,
    TiptapInput,
} from '@verbb/plugin-kit-react/components';
import { resolveStaticVariableCategories } from '@defaults/utils/variableCategories';
import { FieldLayout } from '@verbb/plugin-kit-react/forms/Field';
import { SchemaFormEngine, useSchemaFormEngine } from '@verbb/plugin-kit-react/forms';
import { Lightswitch } from '@verbb/plugin-kit-react/components/Lightswitch';

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

const FieldTypeDefaultsPanel = ({
    fieldType, values, onChange,
}) => {
    const hasHandledInitialChangeRef = useRef(false);
    const initialValues = useMemo(() => {
        return values || {};
    }, [fieldType?.type, values]);

    const form = useSchemaFormEngine({
        schema: fieldType?.schema || [],
        schemaIndex: fieldType?.schemaIndex || null,
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
    }, [fieldType?.type, form, initialValues]);

    if (!fieldType) {
        return (
            <p className="formie-defaults-note">
                {Craft.t('formie', 'No field types expose default settings yet.')}
            </p>
        );
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

    const dataRetention = getAtPath(values, 'formDefaults.dataRetention', 'forever');
    const displayPageProgress = !!getAtPath(values, 'formDefaults.displayPageProgress', false);

    const inheritBooleanOptions = useMemo(() => {
        return [
            { label: Craft.t('formie', 'Inherit'), value: '' },
            { label: Craft.t('app', 'Yes'), value: '1' },
            { label: Craft.t('app', 'No'), value: '0' },
        ];
    }, []);

    const toInheritBooleanValue = (value) => {
        if (value === null || value === undefined) {
            return '';
        }

        return value ? '1' : '0';
    };

    const fromInheritBooleanValue = (value) => {
        if (value === '' || value === null || value === undefined) {
            return null;
        }

        return value === '1';
    };

    const submissionTitleFormatCategories = useMemo(() => {
        return resolveStaticVariableCategories(
            settings.variableCategoriesConfig || {},
            settings.submissionTitleFormatVariableConfig || {},
        );
    }, [settings.submissionTitleFormatVariableConfig, settings.variableCategoriesConfig]);

    const variableCategoryLabels = settings.variableCategoryLabels || {};
    const variableCategoryOrder = settings.variableCategoryOrder || [];
    const variableTransformerRegistry = settings.variableCategoriesConfig?.transformerRegistry || {};

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

                    <FieldLayout
                        name="formDefaults.defaultStatus"
                        label={Craft.t('formie', 'Default Status')}
                        instructions={Craft.t('formie', 'The default status to be assigned to new submissions.')}
                    >
                        <SelectInput
                            value={getAtPath(values, 'formDefaults.defaultStatus')}
                            options={options.statuses || []}
                            onChange={(value) => { updateValue('formDefaults.defaultStatus', String(value ?? '')); }}
                        />
                    </FieldLayout>

                    <FieldLayout
                        name="formDefaults.submissionTitleFormat"
                        label={Craft.t('formie', 'Submission Title Format')}
                        instructions={Craft.t('formie', 'Enter the format of the auto-generated submission titles. If left blank, the date/time of submission will be used.')}
                    >
                        <TiptapInput
                            value={getAtPath(values, 'formDefaults.submissionTitleFormat')}
                            onChange={(value) => { updateValue('formDefaults.submissionTitleFormat', value); }}
                            variableCategories={submissionTitleFormatCategories}
                            variableCategoryLabels={variableCategoryLabels}
                            variableCategoryOrder={variableCategoryOrder}
                            variableTransformerRegistry={variableTransformerRegistry}
                            variablePickerTriggerCharacters={['@', '{']}
                        />
                    </FieldLayout>

                    <FieldLayout
                        name="formDefaults.submitMethod"
                        label={Craft.t('formie', 'Submission Method')}
                        instructions={Craft.t('formie', 'Whether to reload the page when this form is submitted, or use Ajax to send this form without reloading the page.')}
                    >
                        <SelectInput
                            value={getAtPath(values, 'formDefaults.submitMethod', 'page-reload')}
                            options={options.submitMethodOptions || []}
                            onChange={(value) => { updateValue('formDefaults.submitMethod', String(value ?? '')); }}
                        />
                    </FieldLayout>

                    <FieldLayout
                        name="formDefaults.collectIp"
                        label={Craft.t('formie', 'Collect IP Addresses')}
                        instructions={Craft.t('formie', 'Whether new forms should collect the users‘ IP address.')}
                    >
                        <Lightswitch
                            checked={!!getAtPath(values, 'formDefaults.collectIp', false)}
                            onCheckedChange={(checked) => { updateValue('formDefaults.collectIp', Boolean(checked)); }}
                        />
                    </FieldLayout>

                    <FieldLayout
                        name="formDefaults.collectUser"
                        label={Craft.t('formie', 'Collect User')}
                        instructions={Craft.t('formie', 'Whether new forms should keep a record of the logged-in user.')}
                    >
                        <Lightswitch
                            checked={!!getAtPath(values, 'formDefaults.collectUser', false)}
                            onCheckedChange={(checked) => { updateValue('formDefaults.collectUser', Boolean(checked)); }}
                        />
                    </FieldLayout>

                    <FieldLayout
                        name="formDefaults.dataRetention"
                        label={Craft.t('formie', 'Data Retention')}
                        instructions={Craft.t('formie', 'How long to retain form submission data for.')}
                    >
                        <SelectInput
                            value={dataRetention}
                            options={options.dataRetentionOptions || []}
                            onChange={(value) => { updateValue('formDefaults.dataRetention', String(value ?? '')); }}
                        />
                    </FieldLayout>

                    {dataRetention !== 'forever' ? (
                        <FieldLayout
                            name="formDefaults.dataRetentionValue"
                            label={Craft.t('formie', 'Data Retention Duration')}
                            instructions={Craft.t('formie', 'After this duration has been met, submissions will be deleted.')}
                        >
                            <Input
                                type="number"
                                min="1"
                                value={getAtPath(values, 'formDefaults.dataRetentionValue')}
                                onChange={(event) => { updateValue('formDefaults.dataRetentionValue', event.target.value); }}
                            />
                        </FieldLayout>
                    ) : null}

                    <FieldLayout
                        name="formDefaults.fileUploadsAction"
                        label={Craft.t('formie', 'File Uploads')}
                        instructions={Craft.t('formie', 'Select how to handle file uploads when a submission is deleted.')}
                    >
                        <SelectInput
                            value={getAtPath(values, 'formDefaults.fileUploadsAction', 'retain')}
                            options={options.fileUploadsActionOptions || []}
                            onChange={(value) => { updateValue('formDefaults.fileUploadsAction', String(value ?? '')); }}
                        />
                    </FieldLayout>

                    <DefaultsSectionIntro
                        title={Craft.t('formie', 'Form Appearance')}
                        description={Craft.t('formie', 'Default appearance settings applied to new forms and stencils.')}
                    />

                    <FieldLayout
                        name="formDefaults.displayFormTitle"
                        label={Craft.t('formie', 'Display Form Title')}
                        instructions={Craft.t('formie', 'Whether the title of this form should be included on the page when rendering the form.')}
                    >
                        <Lightswitch
                            checked={!!getAtPath(values, 'formDefaults.displayFormTitle', false)}
                            onCheckedChange={(checked) => { updateValue('formDefaults.displayFormTitle', Boolean(checked)); }}
                        />
                    </FieldLayout>

                    <FieldLayout
                        name="formDefaults.displayCurrentPageTitle"
                        label={Craft.t('formie', 'Display Current Page Title')}
                        instructions={Craft.t('formie', 'Whether the title of the current page should be included when rendering the form.')}
                    >
                        <Lightswitch
                            checked={!!getAtPath(values, 'formDefaults.displayCurrentPageTitle', false)}
                            onCheckedChange={(checked) => { updateValue('formDefaults.displayCurrentPageTitle', Boolean(checked)); }}
                        />
                    </FieldLayout>

                    <FieldLayout
                        name="formDefaults.displayPageTabs"
                        label={Craft.t('formie', 'Display Page Tabs')}
                        instructions={Craft.t('formie', 'Whether tabs of all pages should be included on the page when rendering the form.')}
                    >
                        <Lightswitch
                            checked={!!getAtPath(values, 'formDefaults.displayPageTabs', false)}
                            onCheckedChange={(checked) => { updateValue('formDefaults.displayPageTabs', Boolean(checked)); }}
                        />
                    </FieldLayout>

                    <FieldLayout
                        name="formDefaults.displayPageProgress"
                        label={Craft.t('formie', 'Display Page Progress')}
                        instructions={Craft.t('formie', 'Whether to show a progress bar of the page completion.')}
                    >
                        <Lightswitch
                            checked={displayPageProgress}
                            onCheckedChange={(checked) => { updateValue('formDefaults.displayPageProgress', Boolean(checked)); }}
                        />
                    </FieldLayout>

                    {displayPageProgress ? (
                        <>
                            <FieldLayout
                                name="formDefaults.progressCalculation"
                                label={Craft.t('formie', 'Page Progress Calculation')}
                                instructions={Craft.t('formie', 'Choose whether the progress bar should reflect completed progress or current page position.')}
                            >
                                <SelectInput
                                    value={getAtPath(values, 'formDefaults.progressCalculation', 'completion')}
                                    options={options.progressCalculationOptions || []}
                                    onChange={(value) => { updateValue('formDefaults.progressCalculation', String(value ?? '')); }}
                                />
                            </FieldLayout>

                            <FieldLayout
                                name="formDefaults.progressPosition"
                                label={Craft.t('formie', 'Page Progress Position')}
                                instructions={Craft.t('formie', 'Select the position of the page progress indicator in the form.')}
                            >
                                <SelectInput
                                    value={getAtPath(values, 'formDefaults.progressPosition', 'end')}
                                    options={options.progressPositionOptions || []}
                                    onChange={(value) => { updateValue('formDefaults.progressPosition', String(value ?? '')); }}
                                />
                            </FieldLayout>
                        </>
                    ) : null}

                    <FieldLayout
                        name="formDefaults.scrollToTop"
                        label={Craft.t('formie', 'Scroll To Top')}
                        instructions={Craft.t('formie', 'Whether for multi-page forms, the page should automatically scroll to the top of the next page after submission.')}
                    >
                        <Lightswitch
                            checked={!!getAtPath(values, 'formDefaults.scrollToTop', true)}
                            onCheckedChange={(checked) => { updateValue('formDefaults.scrollToTop', Boolean(checked)); }}
                        />
                    </FieldLayout>

                    <FieldLayout
                        name="formDefaults.requiredIndicator"
                        label={Craft.t('formie', 'Required Field Indicator')}
                        instructions={Craft.t('formie', 'Select how to show required fields.')}
                    >
                        <SelectInput
                            value={getAtPath(values, 'formDefaults.requiredIndicator', 'asterisk')}
                            options={options.requiredIndicatorOptions || []}
                            onChange={(value) => { updateValue('formDefaults.requiredIndicator', String(value ?? '')); }}
                        />
                    </FieldLayout>
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

                            <FieldTypeDefaultsPanel
                                key={selectedFieldType}
                                fieldType={selectedFieldTypeConfig}
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

                    <FieldLayout
                        name="notificationDefaults.fromName"
                        label={Craft.t('formie', 'From Name')}
                        instructions={Craft.t('formie', 'The name the notification email will be sent from.')}
                    >
                        <Input
                            value={getAtPath(values, 'notificationDefaults.fromName', '')}
                            onChange={(event) => { updateValue('notificationDefaults.fromName', event.target.value); }}
                        />
                    </FieldLayout>

                    <FieldLayout
                        name="notificationDefaults.from"
                        label={Craft.t('formie', 'From Email')}
                        instructions={Craft.t('formie', 'The email address the notification email will be sent from. Leave empty to use the default email address for your site.')}
                    >
                        <Input
                            value={getAtPath(values, 'notificationDefaults.from', '')}
                            onChange={(event) => { updateValue('notificationDefaults.from', event.target.value); }}
                        />
                    </FieldLayout>

                    <FieldLayout
                        name="notificationDefaults.replyToName"
                        label={Craft.t('formie', 'Reply-To Name')}
                        instructions={Craft.t('formie', 'The name to be used as the reply to for the notification email.')}
                    >
                        <Input
                            value={getAtPath(values, 'notificationDefaults.replyToName', '')}
                            onChange={(event) => { updateValue('notificationDefaults.replyToName', event.target.value); }}
                        />
                    </FieldLayout>

                    <FieldLayout
                        name="notificationDefaults.replyTo"
                        label={Craft.t('formie', 'Reply-To Email')}
                        instructions={Craft.t('formie', 'The email address to be used as the reply to address for the notification email.')}
                    >
                        <Input
                            value={getAtPath(values, 'notificationDefaults.replyTo', '')}
                            onChange={(event) => { updateValue('notificationDefaults.replyTo', event.target.value); }}
                        />
                    </FieldLayout>

                    <FieldLayout
                        name="notificationDefaults.subject"
                        label={Craft.t('formie', 'Subject')}
                        instructions={Craft.t('formie', 'The default subject line for new notifications.')}
                    >
                        <Input
                            value={getAtPath(values, 'notificationDefaults.subject', '')}
                            onChange={(event) => { updateValue('notificationDefaults.subject', event.target.value); }}
                        />
                    </FieldLayout>

                    <FieldLayout
                        name="notificationDefaults.attachFiles"
                        label={Craft.t('formie', 'Attach Files')}
                        instructions={Craft.t('formie', 'Whether to attach uploaded files to new notifications by default.')}
                    >
                        <SelectInput
                            value={toInheritBooleanValue(getAtPath(values, 'notificationDefaults.attachFiles', null))}
                            options={inheritBooleanOptions}
                            onChange={(value) => { updateValue('notificationDefaults.attachFiles', fromInheritBooleanValue(value)); }}
                        />
                    </FieldLayout>

                    <FieldLayout
                        name="notificationDefaults.attachPdf"
                        label={Craft.t('formie', 'Attach PDF Template')}
                        instructions={Craft.t('formie', 'Whether to attach a PDF template to new notifications by default.')}
                    >
                        <SelectInput
                            value={toInheritBooleanValue(getAtPath(values, 'notificationDefaults.attachPdf', null))}
                            options={inheritBooleanOptions}
                            onChange={(value) => { updateValue('notificationDefaults.attachPdf', fromInheritBooleanValue(value)); }}
                        />
                    </FieldLayout>

                    <FieldLayout
                        name="notificationDefaults.enabled"
                        label={Craft.t('formie', 'Enabled')}
                        instructions={Craft.t('formie', 'Whether new notifications should be enabled by default.')}
                    >
                        <SelectInput
                            value={toInheritBooleanValue(getAtPath(values, 'notificationDefaults.enabled', null))}
                            options={inheritBooleanOptions}
                            onChange={(value) => { updateValue('notificationDefaults.enabled', fromInheritBooleanValue(value)); }}
                        />
                    </FieldLayout>
                </PaneTabsContent>

                <PaneTabsContent value="integrations" className="formie-defaults-panel">
                    <DefaultsSectionIntro
                        title={Craft.t('formie', 'Integration Defaults')}
                        description={Craft.t('formie', 'Globally enabled captcha integrations are still applied automatically to new forms. Per-integration default profiles can be added here later.')}
                    />

                    <p className="formie-defaults-note">
                        {Craft.t('formie', 'No additional integration defaults are configured yet.')}
                    </p>
                </PaneTabsContent>
            </PaneTabs>
        </div>
    );
};
