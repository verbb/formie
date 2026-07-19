import {
    useCallback, useEffect, useState, useMemo, useRef,
} from 'react';
import { isEqual } from 'lodash-es';
import { collectSchemaDefaultValues } from '@form-builder/utils/collectSchemaDefaultValues';

import { renderMarkdown } from '@verbb/plugin-kit-core';
import { cn } from '@verbb/plugin-kit-react/utils';
import { SchemaFormEngine, useSchemaFormEngine } from '@verbb/plugin-kit-react/forms';
import {
    Button,
    Option,
    OptionGroup,
    Select,
    Status,
    Spinner,
} from '@verbb/plugin-kit-react/components';
import useUrlRouter from '@form-builder/hooks/useUrlRouter';
import {
    useIntegrations,
    fetchIntegrationFormSettingsConfig,
} from '@form-builder/hooks/useFormTools';
import { useFormBuilderApp } from '@form-builder/contexts/FormBuilderAppContext';
import { useFormBuilderForm } from '@form-builder/contexts/FormBuilderFormContext';
import useAppStore from '@form-builder/hooks/useAppStore';
import { LargeErrorState, StatePanel } from '@utils';
import { IntegrationDispatchSettings } from '@form-builder/components/IntegrationDispatchSettings';

const integrationConfigCache = {};
const DISPATCH_SETTINGS_HANDLE = 'settings';

const parseEnabledValue = (value) => {
    if (typeof value === 'boolean') {
        return value;
    }

    if (typeof value === 'number') {
        return value === 1;
    }

    if (typeof value === 'string') {
        const normalized = value.trim().toLowerCase();
        if (normalized === '1' || normalized === 'true') {
            return true;
        }

        if (normalized === '0' || normalized === 'false' || normalized === '') {
            return false;
        }
    }

    return null;
};

const getIntegrationEnabledState = (integration, integrationSettings) => {
    const settingsEnabled = parseEnabledValue(integrationSettings?.enabled);
    if (settingsEnabled !== null) {
        return settingsEnabled;
    }

    return false;
};

const normalizeIntegrationSettings = (settings) => {
    if (!settings || typeof settings !== 'object') {
        return {};
    }

    const normalized = { ...settings };
    const parsedEnabled = parseEnabledValue(settings.enabled);

    if (parsedEnabled !== null) {
        normalized.enabled = parsedEnabled;
    }

    return normalized;
};

const getNestedErrors = (errors, prefix) => {
    if (!errors || typeof errors !== 'object' || !prefix) {
        return {};
    }

    const nestedErrors = {};

    Object.entries(errors).forEach(([key, value]) => {
        if (!key.startsWith(`${prefix}.`)) {
            return;
        }

        const nestedKey = key.slice(prefix.length + 1);
        if (!nestedKey) {
            return;
        }

        nestedErrors[nestedKey] = value;
    });

    return nestedErrors;
};

function Integrations({ schema }) {
    const { integrationGroups, integrations } = useIntegrations();
    const { activeIntegrationHandle } = useFormBuilderApp();
    const {
        form: parentForm,
        getValueAtPath,
        errors: parentErrors,
        values,
    } = useFormBuilderForm();
    const formId = useAppStore((state) => { return state.formId; });
    const router = useUrlRouter();

    const [integrationConfig, setIntegrationConfig] = useState(null);
    const [integrationConfigHandle, setIntegrationConfigHandle] = useState(null);
    const [configLoading, setConfigLoading] = useState(false);
    const [configError, setConfigError] = useState(null);
    const [failedIcons, setFailedIcons] = useState({});

    const payloadIntegrations = useMemo(() => {
        if (!integrations?.length) {
            return [];
        }

        return integrations.filter((integration) => {
            return integration.supportsPayloadSending !== false;
        });
    }, [integrations]);

    const enabledPayloadIntegrations = useMemo(() => {
        return payloadIntegrations.filter((integration) => {
            const integrationSettings = getValueAtPath(`settings.integrations.${integration.handle}`, null);

            return getIntegrationEnabledState(integration, integrationSettings);
        });
    }, [payloadIntegrations, getValueAtPath]);

    const isIntegrationEnabled = useCallback((handle) => {
        return enabledPayloadIntegrations.some((integration) => {
            return integration.handle === handle;
        });
    }, [enabledPayloadIntegrations]);

    const showDispatchSettings = useMemo(() => {
        if (!payloadIntegrations.length || !enabledPayloadIntegrations.length) {
            return false;
        }

        if (enabledPayloadIntegrations.length >= 2) {
            return true;
        }

        const plan = getValueAtPath('settings.integrationDispatch', null);
        const rerunPolicies = getValueAtPath('settings.integrationPolicies.rerun', null);

        if (plan?.enabled) {
            return true;
        }

        if (rerunPolicies && typeof rerunPolicies === 'object') {
            return Object.values(rerunPolicies).some((entry) => {
                return entry?.policy && entry.policy !== 'submitOnly';
            });
        }

        return true;
    }, [payloadIntegrations.length, enabledPayloadIntegrations.length, getValueAtPath, values]);
    const isDispatchSettingsActive = activeIntegrationHandle === DISPATCH_SETTINGS_HANDLE;

    const activeIntegration = isDispatchSettingsActive
        ? null
        : integrations?.find((integration) => {
            return integration.handle === activeIntegrationHandle;
        });

    const loadIntegrationConfig = useCallback(async(handle, options = {}) => {
        const { force = false, background = false } = options;

        if (!handle || !formId || handle === DISPATCH_SETTINGS_HANDLE) {
            setIntegrationConfig(null);
            setIntegrationConfigHandle(null);
            setConfigError(null);
            return;
        }

        const cached = integrationConfigCache[handle];
        if (!force && cached) {
            setIntegrationConfig(cached);
            setIntegrationConfigHandle(handle);
            setConfigError(null);
            return;
        }

        if (!background) {
            setConfigLoading(true);
        }
        setConfigError(null);

        const result = await fetchIntegrationFormSettingsConfig(handle, formId);
        if (!background) {
            setConfigLoading(false);
        }

        if (result.ok && result.data) {
            integrationConfigCache[handle] = result.data;
            setIntegrationConfig(result.data);
            setIntegrationConfigHandle(handle);
            setConfigError(null);
        } else {
            setIntegrationConfig(null);
            setIntegrationConfigHandle(null);
            setConfigError(result.errorObject || result.error || Craft.t('formie', 'Failed to load integration settings.'));
        }
    }, [formId]);

    // Fetch integration form settings config when an integration is selected
    useEffect(() => {
        loadIntegrationConfig(activeIntegrationHandle);
    }, [activeIntegrationHandle, loadIntegrationConfig]);

    const handleIntegrationSelect = (integration) => {
        router.navigateToIntegration(integration.handle);
    };

    const handleIconError = (iconKey) => {
        setFailedIcons((prev) => {
            if (prev[iconKey]) {
                return prev;
            }

            return {
                ...prev,
                [iconKey]: true,
            };
        });
    };

    useEffect(() => {
        const handleInlineRefresh = async(event) => {
            const detail = event?.detail || {};
            const handle = String(detail.handle || '');
            const ok = detail.ok === true;

            if (!handle || handle !== activeIntegrationHandle) {
                return;
            }

            if (!ok) {
                // Refresh failures are shown inline by the triggering field;
                // do not replace the entire integration settings panel.
                return;
            }

            delete integrationConfigCache[handle];
            await loadIntegrationConfig(handle, { force: true, background: true });
        };

        window.addEventListener('formie:integration-settings-refreshed', handleInlineRefresh);
        return () => {
            window.removeEventListener('formie:integration-settings-refreshed', handleInlineRefresh);
        };
    }, [activeIntegrationHandle, loadIntegrationConfig]);

    useEffect(() => {
        if (!showDispatchSettings && isDispatchSettingsActive && integrations?.length) {
            router.navigateToIntegration(integrations[0].handle, { replace: true });
        }
    }, [showDispatchSettings, isDispatchSettingsActive, integrations, router]);

    useEffect(() => {
        if (!activeIntegrationHandle && integrations && integrations.length) {
            const firstIntegration = integrations[0];
            router.navigateToIntegration(firstIntegration.handle, { replace: true });
        }
    }, [integrations, activeIntegrationHandle, router]);

    if (!integrations || integrations.length === 0) {
        return (
            <StatePanel
                variant="empty"
                title={Craft.t('formie', 'No integrations found')}
                message={Craft.t('formie', 'No integrations are available for this form.')}
                containerClassName="p-8 py-26 text-center"
                contentClassName="flex w-[90%] max-w-[560px] flex-col items-center text-center mx-auto"
            />
        );
    }

    return (
        <div className="flex flex-1 h-full">
            <div className={cn(
                'relative',
                'bg-[#f3f7fc]',
                'border-r',
                'border-[rgba(51,64,77,.1)]',
                'rounded-l-lg',
                'overflow-auto',
                'hidden md:block',
                'md:w-[200px] lg:w-[240px]',
                'px-1.5 pt-3 md:px-2',
            )}>
                {integrationGroups.map((group) => {
                    return (
                        <div key={group.handle} className="mb-4 :last:mb-0">
                            <div className="mb-[3px] ml-[10px]">
                                <h3 className="text-[11px] font-bold text-gray-500 uppercase">
                                    {group.label}
                                </h3>
                            </div>
                            <div className="space-y-1">
                                {group.integrations.map((integration) => {
                                    const isSelected = activeIntegration && activeIntegration.handle === integration.handle;
                                    const iconKey = `${integration.handle}:${integration.icon || ''}`;
                                    const integrationSettings = getValueAtPath(`settings.integrations.${integration.handle}`, null);
                                    const integrationEnabled = getIntegrationEnabledState(integration, integrationSettings);
                                    return (
                                        <Button
                                            key={integration.handle}
                                            variant="transparent"
                                            size="none"
                                            onClick={() => { return handleIntegrationSelect(integration); }}
                                            className={cn(
                                                'form-builder-integrations-nav-item',
                                                'w-full min-w-0',
                                                'text-[13px]',
                                                isSelected && 'is-selected',
                                            )}
                                        >
                                            {/*
                                             * Host Tailwind (px/gap/bg) does not pierce pk-button shadow.
                                             * Density via --pk-btn-* + ::part(base); icons/status via start/end slots.
                                             */}
                                            {integration.icon && !failedIcons[iconKey] && (
                                                <img
                                                    slot="start"
                                                    src={integration.icon}
                                                    alt=""
                                                    onError={() => {
                                                        handleIconError(iconKey);
                                                    }}
                                                />
                                            )}
                                            <span className="truncate">{integration.name}</span>
                                            <Status
                                                slot="end"
                                                status={integrationEnabled ? 'enabled' : 'disabled'}
                                            />
                                        </Button>
                                    );
                                })}
                            </div>
                        </div>
                    );
                })}

                {showDispatchSettings && (
                    <div className="mt-2 border-t border-[rgba(51,64,77,.15)] pt-3">
                        <Button
                            variant="transparent"
                            size="none"
                            onClick={() => {
                                router.navigateToIntegration(DISPATCH_SETTINGS_HANDLE);
                            }}
                            className={cn(
                                'form-builder-integrations-nav-item',
                                'w-full min-w-0',
                                'text-[13px]',
                                isDispatchSettingsActive && 'is-selected',
                            )}
                        >
                            <span className="truncate">{Craft.t('formie', 'Settings')}</span>
                        </Button>
                    </div>
                )}
            </div>

            <div className="flex-1 overflow-auto">
                <div className="border-b border-[rgba(51,64,77,.1)] bg-[#f3f7fc] p-3 md:hidden">
                    <label className="mb-1 block text-[11px] font-bold uppercase text-gray-500">
                        {Craft.t('formie', 'Integration')}
                    </label>

                    <Select
                        size="sm"
                        width="full"
                        placeholder={Craft.t('formie', 'Select an integration')}
                        value={activeIntegrationHandle || ''}
                        onPkChange={(event) => {
                            const value = event.detail?.value ?? event.target?.value;

                            if (value === DISPATCH_SETTINGS_HANDLE) {
                                router.navigateToIntegration(DISPATCH_SETTINGS_HANDLE);
                                return;
                            }

                            const nextIntegration = integrations.find((integration) => {
                                return integration.handle === value;
                            });

                            if (nextIntegration) {
                                handleIntegrationSelect(nextIntegration);
                            }
                        }}
                    >
                        {integrationGroups.map((group) => {
                            return (
                                <OptionGroup key={group.handle} label={group.label}>
                                    {group.integrations.map((integration) => {
                                        const integrationSettings = getValueAtPath(`settings.integrations.${integration.handle}`, null);
                                        const integrationEnabled = getIntegrationEnabledState(integration, integrationSettings);
                                        const iconKey = `${integration.handle}:${integration.icon || ''}`;

                                        return (
                                            <Option key={integration.handle} value={integration.handle}>
                                                <span className="flex min-w-0 items-center gap-2">
                                                    {integration.icon && !failedIcons[iconKey] && (
                                                        <img
                                                            src={integration.icon}
                                                            alt=""
                                                            className="size-4 shrink-0"
                                                            onError={() => {
                                                                handleIconError(iconKey);
                                                            }}
                                                        />
                                                    )}
                                                    <span className="min-w-0 truncate">{integration.name}</span>
                                                    <Status
                                                        className="ml-auto shrink-0"
                                                        status={integrationEnabled ? 'enabled' : 'disabled'}
                                                    />
                                                </span>
                                            </Option>
                                        );
                                    })}
                                </OptionGroup>
                            );
                        })}

                        {showDispatchSettings && (
                            <OptionGroup label={Craft.t('formie', 'Form settings')}>
                                <Option value={DISPATCH_SETTINGS_HANDLE}>
                                    {Craft.t('formie', 'Settings')}
                                </Option>
                            </OptionGroup>
                        )}
                    </Select>
                </div>

                {isDispatchSettingsActive && (
                    <div className="p-3 md:p-6">
                        <IntegrationDispatchSettings
                            payloadIntegrations={payloadIntegrations}
                            enabledPayloadIntegrations={enabledPayloadIntegrations}
                            isIntegrationEnabled={isIntegrationEnabled}
                        />
                    </div>
                )}

                {!isDispatchSettingsActive && activeIntegration && (
                    <div className="p-3 md:p-6">
                        <div className="mb-6">
                            <div className="flex items-center justify-between gap-3">
                                <h2 className="text-lg font-semibold text-gray-900">
                                    {activeIntegration.name}
                                </h2>
                            </div>
                            {activeIntegration.description && (
                                <div
                                    className="mt-1 text-sm leading-relaxed text-gray-500 [&_a]:text-blue-600 [&_a]:underline [&_a:hover]:text-blue-700 [&_ol]:mt-2 [&_ol]:list-decimal [&_ol]:pl-5 [&_p]:m-0 [&_ul]:mt-2 [&_ul]:list-disc [&_ul]:pl-5 [&_li]:mt-1 [&_p+ul]:mt-2 [&_p+ol]:mt-2"
                                    dangerouslySetInnerHTML={{ __html: renderMarkdown(activeIntegration.description) }}
                                />
                            )}
                        </div>
                        {configLoading && (
                            <div className="flex items-center gap-2 text-gray-500">
                                <div className="size-4"><Spinner size="xs" /></div>
                                <span className="text-sm">{Craft.t('formie', 'Loading settings…')}</span>
                            </div>
                        )}
                        {configError && (
                            <LargeErrorState
                                error={configError}
                                message={Craft.t('formie', 'Failed to load integration settings.')}
                                detailsLabel={Craft.t('formie', 'Show error details')}
                                actionLabel={Craft.t('formie', 'Try Again')}
                                onAction={() => { return loadIntegrationConfig(activeIntegrationHandle, { force: true }); }}
                                containerClassName="flex min-h-[320px] items-center justify-center py-6"
                            />
                        )}
                        {!configLoading && !configError && integrationConfig && integrationConfigHandle === activeIntegrationHandle && (
                            <IntegrationSettingsForm
                                key={activeIntegrationHandle}
                                handle={activeIntegrationHandle}
                                config={integrationConfig}
                                parentForm={parentForm}
                                getValueAtPath={getValueAtPath}
                                parentErrors={parentErrors}
                            />
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}

function IntegrationSettingsForm({
    handle, config, parentForm, getValueAtPath, parentErrors,
}) {
    const path = `settings.integrations.${handle}`;
    const currentSettings = getValueAtPath(path, null);
    const schemaDefaultValues = useMemo(() => {
        return collectSchemaDefaultValues(config.schema || []);
    }, [config.schema]);
    const initialValues = useMemo(() => {
        const mergedSettings = {
            ...(schemaDefaultValues || {}),
            ...(config.defaultValues || {}),
            ...(currentSettings || {}),
        };
        return normalizeIntegrationSettings(mergedSettings);
    }, [currentSettings, config.defaultValues, schemaDefaultValues]);
    const nestedErrors = useMemo(() => {
        return getNestedErrors(parentErrors, path);
    }, [parentErrors, path]);
    const hasHandledInitialChangeRef = useRef(false);

    const form = useSchemaFormEngine({
        schema: config.schema || [],
        schemaIndex: config.schemaIndex || null,
        defaultValues: initialValues,
        errors: nestedErrors,
        onChange: (data) => {
            if (!hasHandledInitialChangeRef.current) {
                hasHandledInitialChangeRef.current = true;

                if (isEqual(data || {}, initialValues || {})) {
                    return;
                }
            }

            if (parentForm?.setFieldValue) {
                const existingValue = getValueAtPath(path, null) || {};
                if (isEqual(existingValue, data || {})) {
                    return;
                }

                parentForm.setFieldValue(path, data);
            }
        },
    });

    return (
        <SchemaFormEngine form={form} withoutForm className="space-y-4" />
    );
}

export { Integrations };
