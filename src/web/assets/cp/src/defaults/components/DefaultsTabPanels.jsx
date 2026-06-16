import { useMemo, useState } from 'react';

import {
    ComboboxInput,
    PaneTabsContent,
    SelectInput,
} from '@verbb/plugin-kit-react/components';
import { FieldLayout } from '@verbb/plugin-kit-react/forms';

import { DefaultsSectionIntro } from '@defaults/components/DefaultsSectionIntro';
import { DefaultsVariableCategoriesProvider } from '@defaults/components/DefaultsVariableCategoriesProvider';
import { SchemaDefaultsPanel } from '@defaults/components/SchemaDefaultsPanel';
import { getAtPath } from '@defaults/utils/defaultsEditorState';

const GROUP_FORM_DEFAULTS_DESCRIPTION = 'These values are applied once when a new form is created in this group. Blank values inherit from global Formie defaults.';

export const DefaultsTabPanels = ({
    settings,
    values,
    updateValue,
    updateFieldDefaults,
    updateFormDefaults,
    updateNotificationDefaults,
    updateValidationMessageDefaults,
    context = 'global',
}) => {
    const [selectedFieldType, setSelectedFieldType] = useState('');

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
        const inheritLabel = context === 'group'
            ? Craft.t('formie', 'Inherit global default')
            : Craft.t('formie', 'Inherit');

        return [
            { label: inheritLabel, value: '' },
            { label: Craft.t('app', 'Yes'), value: '1' },
            { label: Craft.t('app', 'No'), value: '0' },
        ];
    }, [context]);

    const formDefaultsDescription = context === 'group'
        ? GROUP_FORM_DEFAULTS_DESCRIPTION
        : Craft.t('formie', 'These values are applied when a new form or stencil is created. Choosing a stencil when creating a form always overrides these defaults.');

    const fieldDefaultsDescription = context === 'group'
        ? Craft.t('formie', 'Configure defaults applied when authors add new fields to a form in this group. Blank values inherit from global defaults.')
        : Craft.t('formie', 'Configure defaults applied when authors add new fields to a form.');

    const validationDescription = context === 'group'
        ? Craft.t('formie', 'Customize validation message defaults for forms in this group. Leave fields empty to inherit global defaults.')
        : Craft.t('formie', 'Customize the default validation error messages used when a field has no message override. Leave fields empty to use Formie’s built-in copy.');

    const notificationDescription = context === 'group'
        ? Craft.t('formie', 'Set defaults applied when a new notification is created on forms in this group. Leave fields empty to inherit global defaults.')
        : Craft.t('formie', 'Set defaults applied when a new notification is created. Leave fields empty to inherit Formie’s built-in behaviour.');

    const integrationDescription = context === 'group'
        ? Craft.t('formie', 'Control whether captcha integrations are enabled by default on new forms in this group. Inherit uses the global default.')
        : Craft.t('formie', 'Control whether captcha integrations are enabled by default on new forms and stencils. Inherit uses each integration’s global enabled state.');

    return (
        <DefaultsVariableCategoriesProvider settings={settings}>
            <PaneTabsContent value="form" className="formie-defaults-panel">
                <DefaultsSectionIntro
                    title={Craft.t('formie', 'New Form Defaults')}
                    description={formDefaultsDescription}
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
                    description={fieldDefaultsDescription}
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

                <FieldLayout
                    name="defaultErrorMessagePosition"
                    label={Craft.t('formie', 'Default Field Error Position')}
                    instructions={Craft.t('formie', 'Fields will by default have their validation error position set to this option.')}
                >
                    <SelectInput
                        value={getAtPath(values, 'defaultErrorMessagePosition')}
                        options={options.errorMessagePositions || []}
                        onChange={(value) => { updateValue('defaultErrorMessagePosition', String(value ?? '')); }}
                    />
                </FieldLayout>

                {fieldTypes.length ? (
                    <>
                        <FieldLayout
                            name="selectedFieldType"
                            label={Craft.t('formie', 'Field Type')}
                            instructions={Craft.t('formie', 'Choose a field type to configure its default settings.')}
                        >
                            <ComboboxInput
                                value={selectedFieldType}
                                options={fieldTypeOptions}
                                placeholder={Craft.t('formie', 'Select an option')}
                                emptyMessage={Craft.t('formie', 'No field types found.')}
                                onValueChange={(value) => { setSelectedFieldType(String(value ?? '')); }}
                            />
                        </FieldLayout>

                        {selectedFieldType ? (
                            <SchemaDefaultsPanel
                                panelKey={selectedFieldType}
                                schema={selectedFieldTypeConfig?.schema || []}
                                schemaIndex={selectedFieldTypeConfig?.schemaIndex || null}
                                values={(values.fieldDefaults || {})[selectedFieldType] || {}}
                                onChange={(fieldValues) => { updateFieldDefaults(selectedFieldType, fieldValues); }}
                            />
                        ) : null}
                    </>
                ) : (
                    <p className="formie-defaults-note">
                        {Craft.t('formie', 'No field types expose default settings yet.')}
                    </p>
                )}
            </PaneTabsContent>

            <PaneTabsContent value="validation" className="formie-defaults-panel">
                <DefaultsSectionIntro
                    title={Craft.t('formie', 'Validation Message Defaults')}
                    description={validationDescription}
                />

                <SchemaDefaultsPanel
                    panelKey="validation-message-defaults"
                    schema={settings.validationMessageDefaultsSchema || []}
                    schemaIndex={settings.validationMessageDefaultsSchemaIndex || null}
                    values={values.validationMessageDefaults || {}}
                    onChange={updateValidationMessageDefaults}
                />
            </PaneTabsContent>

            <PaneTabsContent value="notifications" className="formie-defaults-panel">
                <DefaultsSectionIntro
                    title={Craft.t('formie', 'Notification Defaults')}
                    description={notificationDescription}
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
                    description={integrationDescription}
                />

                {integrationCaptchas.length ? integrationCaptchas.map((captcha) => {
                    const path = `integrationDefaults.captchas.${captcha.handle}`;

                    return (
                        <FieldLayout
                            key={captcha.handle}
                            name={path}
                            label={captcha.label}
                            instructions={Craft.t('formie', 'Whether this captcha should be enabled when a new form or stencil is created.')}
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
        </DefaultsVariableCategoriesProvider>
    );
};
