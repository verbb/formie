import {
    useCallback, useMemo, useState,
} from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faChevronDown, faChevronRight } from '@fortawesome/pro-solid-svg-icons';

import {
    Checkbox,
    SelectInput,
} from '@verbb/plugin-kit-react/components';
import { useFormBuilderForm } from '@form-builder/contexts/FormBuilderFormContext';

export const RERUN_POLICIES_PATH = 'settings.integrationPolicies.rerun';

export const RERUN_POLICY_SUBMIT_ONLY = 'submitOnly';
export const RERUN_POLICY_ON_EDIT = 'onEdit';
export const RERUN_POLICY_CUSTOM = 'custom';

export const RERUN_EVENT_SUBMIT = 'submit';
export const RERUN_EVENT_FRONTEND_EDIT = 'frontendEdit';
export const RERUN_EVENT_CP_SAVE = 'cpSave';
export const RERUN_EVENT_UNMARK_SPAM = 'unmarkSpam';

const POLICY_OPTIONS = [
    { value: RERUN_POLICY_SUBMIT_ONLY, label: Craft.t('formie', 'Once on submit') },
    { value: RERUN_POLICY_ON_EDIT, label: Craft.t('formie', 'Also when submission is edited') },
    { value: RERUN_POLICY_CUSTOM, label: Craft.t('formie', 'Custom…') },
];

const CUSTOM_EVENT_OPTIONS = [
    { value: RERUN_EVENT_SUBMIT, label: Craft.t('formie', 'Initial submission') },
    { value: RERUN_EVENT_FRONTEND_EDIT, label: Craft.t('formie', 'Front-end edit') },
    { value: RERUN_EVENT_CP_SAVE, label: Craft.t('formie', 'Control panel save') },
    { value: RERUN_EVENT_UNMARK_SPAM, label: Craft.t('formie', 'Unmarked as not spam') },
];

const DEFAULT_RERUN_CONFIG = {
    policy: RERUN_POLICY_SUBMIT_ONLY,
    events: [RERUN_EVENT_SUBMIT],
};

const resolvePolicyConfig = (stored) => {
    if (!stored || typeof stored !== 'object') {
        return { ...DEFAULT_RERUN_CONFIG };
    }

    const policy = stored.policy || RERUN_POLICY_SUBMIT_ONLY;
    const events = Array.isArray(stored.events) && stored.events.length
        ? stored.events
        : [RERUN_EVENT_SUBMIT];

    return {
        policy,
        events,
    };
};

const getSummaryLabel = (policies, integrations) => {
    const configured = integrations.filter((integration) => {
        const config = resolvePolicyConfig(policies?.[integration.handle]);

        return config.policy !== RERUN_POLICY_SUBMIT_ONLY;
    });

    if (!configured.length) {
        return Craft.t('formie', 'All integrations run once on submit');
    }

    if (configured.length === 1) {
        const config = resolvePolicyConfig(policies[configured[0].handle]);
        const option = POLICY_OPTIONS.find((entry) => entry.value === config.policy);

        return Craft.t('formie', '{name}: {policy}', {
            name: configured[0].name,
            policy: option?.label || config.policy,
        });
    }

    return Craft.t('formie', '{count} integrations with custom re-run behaviour', {
        count: configured.length,
    });
};

function IntegrationRerunSettings({
    payloadIntegrations,
    enabledPayloadIntegrations,
    showHeading = true,
}) {
    const { getValueAtPath, form: parentForm, values } = useFormBuilderForm();
    const [expanded, setExpanded] = useState(false);

    const policies = useMemo(() => {
        const stored = getValueAtPath(RERUN_POLICIES_PATH, null);

        return stored && typeof stored === 'object' ? stored : {};
    }, [getValueAtPath, values]);

    const summary = useMemo(() => {
        return getSummaryLabel(policies, enabledPayloadIntegrations);
    }, [policies, enabledPayloadIntegrations]);

    const updatePolicy = useCallback((handle, patch) => {
        if (!parentForm?.setFieldValue) {
            return;
        }

        const current = getValueAtPath(RERUN_POLICIES_PATH, null) || {};
        const existing = resolvePolicyConfig(current[handle]);
        const nextEntry = {
            ...existing,
            ...patch,
        };

        if (nextEntry.policy !== RERUN_POLICY_CUSTOM) {
            delete nextEntry.events;
        } else if (!Array.isArray(nextEntry.events) || !nextEntry.events.length) {
            nextEntry.events = [RERUN_EVENT_SUBMIT];
        }

        parentForm.setFieldValue(RERUN_POLICIES_PATH, {
            ...current,
            [handle]: nextEntry,
        });
    }, [getValueAtPath, parentForm]);

    const toggleCustomEvent = useCallback((handle, event, checked) => {
        const current = getValueAtPath(RERUN_POLICIES_PATH, null) || {};
        const existing = resolvePolicyConfig(current[handle]);
        const events = new Set(existing.events || [RERUN_EVENT_SUBMIT]);

        if (checked) {
            events.add(event);
        } else {
            events.delete(event);
        }

        if (!events.size) {
            events.add(RERUN_EVENT_SUBMIT);
        }

        updatePolicy(handle, {
            policy: RERUN_POLICY_CUSTOM,
            events: Array.from(events),
        });
    }, [getValueAtPath, updatePolicy]);

    if (!enabledPayloadIntegrations.length) {
        return null;
    }

    return (
        <div className="fui-integration-rerun-settings rounded-lg border border-[rgba(51,64,77,.1)] bg-white">
            <button
                type="button"
                className="flex w-full items-center gap-3 p-4 text-left"
                onClick={() => {
                    setExpanded((current) => !current);
                }}
                aria-expanded={expanded}
            >
                <FontAwesomeIcon
                    icon={expanded ? faChevronDown : faChevronRight}
                    className="size-3 shrink-0 text-gray-500"
                />
                <div className="min-w-0 flex-1">
                    {showHeading ? (
                        <>
                            <div className="text-sm font-semibold text-gray-900">
                                {Craft.t('formie', 'Per-integration behaviour')}
                            </div>
                            <p className="mt-1 text-sm text-gray-500">
                                {Craft.t('formie', 'Control whether integrations run again after the initial submission. Integration conditions still apply.')}
                            </p>
                        </>
                    ) : (
                        <div className="text-sm font-semibold text-gray-900">
                            {Craft.t('formie', 'Per-integration behaviour')}
                        </div>
                    )}
                    {!expanded && (
                        <p className="mt-2 text-sm text-gray-700">
                            {summary}
                        </p>
                    )}
                </div>
            </button>

            {expanded && (
                <div className="border-t border-[rgba(51,64,77,.1)] px-4 pb-4">
                    <div className="fui-integration-rerun-table mt-4 overflow-x-auto">
                        <table className="w-full min-w-[640px] border-collapse text-sm">
                            <thead>
                                <tr className="border-b border-[rgba(51,64,77,.1)] text-left text-gray-500">
                                    <th className="py-2 pr-4 font-semibold">
                                        {Craft.t('formie', 'Integration')}
                                    </th>
                                    <th className="py-2 font-semibold">
                                        {Craft.t('formie', 'Re-run')}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {payloadIntegrations.map((integration) => {
                                    const config = resolvePolicyConfig(policies[integration.handle]);
                                    const isEnabled = enabledPayloadIntegrations.some((entry) => {
                                        return entry.handle === integration.handle;
                                    });

                                    const hasCustomEvents = isEnabled && config.policy === RERUN_POLICY_CUSTOM;

                                    return (
                                        <tr
                                            key={integration.handle}
                                            className={`fui-integration-rerun-row border-b border-[rgba(51,64,77,.06)] last:border-b-0${hasCustomEvents ? ' fui-integration-rerun-row--expanded' : ''}`}
                                        >
                                            <td className="fui-integration-rerun-label py-3 pr-4">
                                                <div className="font-medium text-gray-900">
                                                    {integration.name}
                                                </div>
                                                {!isEnabled && (
                                                    <div className="mt-0.5 text-xs text-gray-500">
                                                        {Craft.t('formie', 'Disabled on this form')}
                                                    </div>
                                                )}
                                            </td>
                                            <td className="fui-integration-rerun-control py-3">
                                                <SelectInput
                                                    value={config.policy}
                                                    options={POLICY_OPTIONS}
                                                    onChange={(value) => {
                                                        const policy = String(value || RERUN_POLICY_SUBMIT_ONLY);

                                                        updatePolicy(integration.handle, {
                                                            policy,
                                                            ...(policy === RERUN_POLICY_CUSTOM
                                                                ? { events: config.events?.length ? config.events : [RERUN_EVENT_SUBMIT] }
                                                                : {}),
                                                        });
                                                    }}
                                                    triggerClassName="fui-integration-rerun-control-select w-full max-w-md"
                                                    disabled={!isEnabled}
                                                />

                                                {hasCustomEvents && (
                                                    <div className="mt-3 grid gap-2 sm:grid-cols-2">
                                                        {CUSTOM_EVENT_OPTIONS.map((option) => {
                                                            const checked = (config.events || []).includes(option.value);

                                                            return (
                                                                <label
                                                                    key={option.value}
                                                                    className="flex items-center gap-2 text-sm text-gray-700"
                                                                >
                                                                    <Checkbox
                                                                        checked={checked}
                                                                        onCheckedChange={(nextChecked) => {
                                                                            toggleCustomEvent(
                                                                                integration.handle,
                                                                                option.value,
                                                                                Boolean(nextChecked),
                                                                            );
                                                                        }}
                                                                    />
                                                                    <span>{option.label}</span>
                                                                </label>
                                                            );
                                                        })}
                                                    </div>
                                                )}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                </div>
            )}
        </div>
    );
}

export { IntegrationRerunSettings };
