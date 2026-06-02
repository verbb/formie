import { useEffect, useRef, useState } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faArrowsRotate, faCheck,
} from '@fortawesome/pro-solid-svg-icons';

import { Button, SelectInput } from '@verbb/plugin-kit-react/components';
import { FieldControl, FieldLayout } from '@verbb/plugin-kit-react/forms/Field';
import { useEngineField } from '@verbb/plugin-kit-react/forms/useEngineField';
import { cn, getErrorMessage } from '@verbb/plugin-kit-react/utils';
import { IntegrationErrorMessage } from './IntegrationErrorMessage';

import { useFormBuilderApp } from '@form-builder/contexts/FormBuilderAppContext';
import { useFormBuilderForm } from '@form-builder/contexts/FormBuilderFormContext';
import { refreshIntegrationFormSettings } from '@form-builder/hooks/useFormTools';

const SUCCESS_FEEDBACK_DURATION = 2200;

function IntegrationRefreshSelectField({ field, form }) {
    const {
        value, setValue, setTouched, errors, isInvalid,
    } = useEngineField(form, field.name);
    const { activeIntegrationHandle } = useFormBuilderApp();
    const { form: parentForm, getValueAtPath } = useFormBuilderForm();
    const [refreshing, setRefreshing] = useState(false);
    const [refreshError, setRefreshError] = useState(null);
    const [showRefreshSuccess, setShowRefreshSuccess] = useState(false);
    const successTimeoutRef = useRef(null);

    const options = Array.isArray(field.options) ? field.options : [];

    const getIntegrationSettingsPath = () => {
        const integrationHandle = activeIntegrationHandle || field.integrationHandle;
        return integrationHandle ? `settings.integrations.${integrationHandle}` : '';
    };

    const getLiveIntegrationSettings = (settingsPath) => {
        const liveSettings = parentForm?.getFieldValue?.(settingsPath);
        if (liveSettings && typeof liveSettings === 'object' && !Array.isArray(liveSettings)) {
            return liveSettings;
        }

        const contextSettings = getValueAtPath(settingsPath, {});
        return (contextSettings && typeof contextSettings === 'object' && !Array.isArray(contextSettings)) ? contextSettings : {};
    };

    const syncIntegrationSetting = (name, nextValue) => {
        const settingsPath = getIntegrationSettingsPath();
        if (!settingsPath || !parentForm?.setFieldValue || !name) {
            return;
        }

        const currentSettings = getLiveIntegrationSettings(settingsPath);
        parentForm.setFieldValue(settingsPath, {
            ...currentSettings,
            [name]: nextValue,
        });
    };

    useEffect(() => {
        return () => {
            if (successTimeoutRef.current) {
                clearTimeout(successTimeoutRef.current);
            }
        };
    }, []);

    const handleRefresh = async() => {
        const integrationHandle = activeIntegrationHandle || field.integrationHandle;
        if (refreshing) {
            return;
        }

        setShowRefreshSuccess(false);

        if (!integrationHandle) {
            const errorMessage = Craft.t('formie', 'Unable to refresh integration data: missing integration handle.');
            setRefreshError({
                heading: Craft.t('formie', 'Configuration error'),
                text: errorMessage,
                traceAsString: '',
            });
            return;
        }

        setRefreshError(null);

        const settingsPath = getIntegrationSettingsPath();
        const currentSettings = getLiveIntegrationSettings(settingsPath);

        setRefreshing(true);
        const result = await refreshIntegrationFormSettings(integrationHandle, currentSettings);
        setRefreshing(false);

        if (result?.ok === true && parentForm?.setFieldValue && result?.data && typeof result.data === 'object' && !Array.isArray(result.data)) {
            // Provider metadata (lists, fields, etc.) must be part of the form payload
            // so the selected list can rebuild its mapping schema after the save reload.
            const latestSettings = getLiveIntegrationSettings(settingsPath);
            parentForm.setFieldValue(settingsPath, {
                ...latestSettings,
                ...result.data,
            });
        }

        if (typeof window !== 'undefined') {
            window.dispatchEvent(new CustomEvent('formie:integration-settings-refreshed', {
                detail: {
                    handle: integrationHandle,
                    ok: result?.ok === true,
                    error: result?.error || null,
                },
            }));
        }

        if (result?.ok !== true) {
            const errorMessage = result?.error || Craft.t('formie', 'Failed to refresh integration data.');
            const parsedError = result?.errorObject ? getErrorMessage(result.errorObject) : null;
            setRefreshError(parsedError?.text ? parsedError : {
                heading: Craft.t('formie', 'Internal Server Error'),
                text: errorMessage,
                traceAsString: '',
            });
            return;
        }

        setShowRefreshSuccess(true);

        if (successTimeoutRef.current) {
            clearTimeout(successTimeoutRef.current);
        }

        successTimeoutRef.current = setTimeout(() => {
            setShowRefreshSuccess(false);
            successTimeoutRef.current = null;
        }, SUCCESS_FEEDBACK_DURATION);
    };

    return (
        <FieldLayout
            name={field.name}
            label={field.label}
            instructions={field.instructions}
            required={field.required}
            errors={errors}
            withControl={false}
        >
            <div>
                <div className="flex items-center gap-2">
                    <FieldControl>
                        <SelectInput
                            options={options}
                            placeholder={field.placeholder}
                            onChange={(nextValue) => {
                                setValue(nextValue);
                                setTouched();
                                syncIntegrationSetting(field.name, nextValue);
                            }}
                            value={value ?? ''}
                            isInvalid={isInvalid}
                            triggerClassName={cn(
                                isInvalid && 'border-error',
                            )}
                        />
                    </FieldControl>

                    <Button
                        type="button"
                        variant="none"
                        onClick={handleRefresh}
                        loading={refreshing}
                        className={showRefreshSuccess ? 'text-green-600' : ''}
                        aria-label={Craft.t('formie', 'Refresh Data')}
                    >
                        <FontAwesomeIcon icon={showRefreshSuccess ? faCheck : faArrowsRotate} className="size-3.5" />
                    </Button>
                </div>

                <IntegrationErrorMessage error={refreshError} className="mt-2" />
            </div>
        </FieldLayout>
    );
}

export { IntegrationRefreshSelectField };
