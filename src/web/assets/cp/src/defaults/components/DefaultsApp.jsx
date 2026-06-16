import { useState } from 'react';

import {
    PaneTabs,
    PaneTabsList,
    PaneTabsTrigger,
} from '@verbb/plugin-kit-react/components';
import { useCpFormPayloadSync } from '@utils';

import { DefaultsTabPanels } from '@defaults/components/DefaultsTabPanels';
import { setAtPath } from '@defaults/utils/defaultsEditorState';

const DEFAULT_TABS = [
    { id: 'form', label: 'Form Defaults' },
    { id: 'fields', label: 'Field Defaults' },
    { id: 'validation', label: 'Validation Messages' },
    { id: 'notifications', label: 'Notification Defaults' },
    { id: 'integrations', label: 'Integration Defaults' },
];

export const DefaultsApp = ({ settings }) => {
    const [values, setValues] = useState(settings.values || {});

    useCpFormPayloadSync({
        inputId: settings.payloadInputId,
        payload: values,
    });

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

    return (
        <div className="formie-defaults-app">
            <PaneTabs defaultValue="form" className="w-full">
                <PaneTabsList aria-label={Craft.t('formie', 'Defaults sections')}>
                    {DEFAULT_TABS.map((tab) => {
                        return (
                            <PaneTabsTrigger key={tab.id} value={tab.id}>
                                {Craft.t('formie', tab.label)}
                            </PaneTabsTrigger>
                        );
                    })}
                </PaneTabsList>

                <DefaultsTabPanels
                    settings={settings}
                    values={values}
                    updateValue={updateValue}
                    updateFieldDefaults={updateFieldDefaults}
                    updateFormDefaults={updateFormDefaults}
                    updateNotificationDefaults={updateNotificationDefaults}
                    updateValidationMessageDefaults={updateValidationMessageDefaults}
                    context="global"
                />
            </PaneTabs>
        </div>
    );
};
