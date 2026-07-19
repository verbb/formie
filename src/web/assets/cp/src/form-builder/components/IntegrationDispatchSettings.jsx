import { useCallback, useMemo } from 'react';
import { cloneDeep } from 'lodash-es';

import { Button, Icon, Lightswitch, SelectInput } from '@verbb/plugin-kit-react/components';
import { useFormBuilderForm } from '@form-builder/contexts/FormBuilderFormContext';
import { IntegrationDispatchStepList } from '@form-builder/components/IntegrationDispatchStepList';
import { IntegrationRerunSettings } from '@form-builder/components/IntegrationRerunSettings';

const DISPATCH_PATH = 'settings.integrationDispatch';

const DEFAULT_PLAN = {
    enabled: false,
    notificationTiming: 'beforeIntegrations',
    failurePolicy: 'continue',
    steps: [],
};

const NOTIFICATION_TIMING_OPTIONS = [
    { value: 'beforeIntegrations', label: Craft.t('formie', 'Before integrations') },
    { value: 'afterIntegrations', label: Craft.t('formie', 'After integrations') },
];

const FAILURE_POLICY_OPTIONS = [
    { value: 'continue', label: Craft.t('formie', 'Continue with remaining integrations') },
    { value: 'stop', label: Craft.t('formie', 'Stop remaining integrations') },
];

const isElementIntegration = (integration) => {
    const groupLabel = String(integration?.groupLabel || '').toLowerCase();

    return groupLabel === 'elements';
};

const isUserOrEntryIntegration = (integration) => {
    const classHandle = String(integration?.classHandle || '').toLowerCase();

    if (classHandle === 'user' || classHandle === 'entry') {
        return true;
    }

    // Fallback for older bootstrap payloads without classHandle.
    const handle = String(integration?.handle || '').toLowerCase();

    return handle === 'user'
        || handle === 'entry'
        || handle === 'users'
        || handle === 'entries';
};

const buildDefaultSteps = (integrations, { immediateForElements = false } = {}) => {
    return integrations.map((integration) => {
        const useImmediate = immediateForElements && isElementIntegration(integration);

        return {
            handle: integration.handle,
            mode: useImmediate ? 'immediate' : 'queued',
        };
    });
};

function IntegrationSettingsSectionHeading({ title, description }) {
    return (
        <div>
            <h3 className="fui-integration-settings-section-heading">
                {title}
            </h3>
            {description ? (
                <p className="mt-1 text-sm leading-relaxed text-gray-500">
                    {description}
                </p>
            ) : null}
        </div>
    );
}

function IntegrationDispatchSettings({
    payloadIntegrations,
    enabledPayloadIntegrations,
    isIntegrationEnabled,
}) {
    const { getValueAtPath, form: parentForm, values } = useFormBuilderForm();

    const plan = useMemo(() => {
        const stored = getValueAtPath(DISPATCH_PATH, null);

        return {
            ...DEFAULT_PLAN,
            ...(stored && typeof stored === 'object' ? stored : {}),
        };
    }, [getValueAtPath, values]);

    const integrationLookup = useMemo(() => {
        return payloadIntegrations.reduce((acc, integration) => {
            acc[integration.handle] = integration;
            return acc;
        }, {});
    }, [payloadIntegrations]);

    const hasEnabledUserOrEntryIntegrations = useMemo(() => {
        return payloadIntegrations.some((integration) => {
            return isUserOrEntryIntegration(integration) && isIntegrationEnabled(integration.handle);
        });
    }, [payloadIntegrations, isIntegrationEnabled]);

    const resolvedSteps = useMemo(() => {
        if (Array.isArray(plan.steps) && plan.steps.length) {
            return plan.steps.filter((step) => {
                return step?.handle && integrationLookup[step.handle];
            });
        }

        return buildDefaultSteps(payloadIntegrations);
    }, [plan.steps, payloadIntegrations, integrationLookup]);

    const updatePlan = useCallback((patch) => {
        if (!parentForm?.setFieldValue) {
            return;
        }

        const nextPlan = {
            ...DEFAULT_PLAN,
            ...(getValueAtPath(DISPATCH_PATH, null) || {}),
            ...patch,
        };

        parentForm.setFieldValue(DISPATCH_PATH, nextPlan);
    }, [getValueAtPath, parentForm]);

    const ensureCustomSteps = useCallback(() => {
        const current = getValueAtPath(DISPATCH_PATH, null) || {};

        if (Array.isArray(current.steps) && current.steps.length) {
            return current.steps;
        }

        return buildDefaultSteps(payloadIntegrations);
    }, [payloadIntegrations, getValueAtPath]);

    const moveStep = (index, direction) => {
        const steps = cloneDeep(ensureCustomSteps());
        const targetIndex = index + direction;

        if (targetIndex < 0 || targetIndex >= steps.length) {
            return;
        }

        const [step] = steps.splice(index, 1);
        steps.splice(targetIndex, 0, step);
        updatePlan({ steps });
    };

    const updateStepMode = (index, mode) => {
        const steps = cloneDeep(ensureCustomSteps());
        steps[index] = {
            ...steps[index],
            mode,
        };
        updatePlan({ steps });
    };

    const applyRecommendedSetup = () => {
        updatePlan({
            enabled: true,
            notificationTiming: 'afterIntegrations',
            failurePolicy: 'continue',
            steps: buildDefaultSteps(payloadIntegrations, { immediateForElements: true }),
        });

        if (!parentForm?.setFieldValue) {
            return;
        }

        const currentRerun = getValueAtPath('settings.integrationPolicies.rerun', null) || {};
        const nextRerun = { ...currentRerun };

        payloadIntegrations.forEach((integration) => {
            if (!isUserOrEntryIntegration(integration) || !isIntegrationEnabled(integration.handle)) {
                return;
            }

            nextRerun[integration.handle] = {
                policy: 'onEdit',
            };
        });

        parentForm.setFieldValue('settings.integrationPolicies.rerun', nextRerun);
    };

    const showDispatchSection = payloadIntegrations.length >= 2;

    return (
        <div className="space-y-8">
            <div>
                <h2 className="text-lg font-semibold text-gray-900">
                    {Craft.t('formie', 'Settings')}
                </h2>
                <p className="mt-1 text-sm leading-relaxed text-gray-500">
                    {Craft.t('formie', 'Advanced integration behaviour for this form, including execution order and when integrations re-run.')}
                </p>
            </div>

            {showDispatchSection && (
                <section className="space-y-4">
                    <IntegrationSettingsSectionHeading
                        title={Craft.t('formie', 'Dispatch')}
                        description={Craft.t('formie', 'Control the order integrations run in, whether they execute immediately or via the queue, and when email notifications are sent.')}
                    />

                    {enabledPayloadIntegrations.length < 2 && plan.enabled && (
                        <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                            {Craft.t('formie', 'Dispatch is enabled, but at least two integrations must be active for orchestration to run on submission.')}
                        </div>
                    )}

                    {hasEnabledUserOrEntryIntegrations && (
                        <div className="rounded-lg border border-[rgba(51,64,77,.1)] bg-white p-4">
                            <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <div className="text-sm font-semibold text-gray-900">
                                        {Craft.t('formie', 'Recommended for User & Entry flows')}
                                    </div>
                                    <p className="mt-1 text-sm text-gray-500">
                                        {Craft.t('formie', 'Run element integrations immediately during the submission request, then send notifications afterwards. This helps with activation emails, auto-login, and success page links that depend on created users or entries.')}
                                    </p>
                                </div>
                                <Button
                                    type="button"
                                    variant="secondary"
                                    className="shrink-0"
                                    onClick={applyRecommendedSetup}
                                >
                                    <Icon slot="start" icon="wand-magic-sparkles" className="size-3.5" />
                                    {Craft.t('formie', 'Apply recommended setup')}
                                </Button>
                            </div>
                        </div>
                    )}

                    <div className="rounded-lg border border-[rgba(51,64,77,.1)] bg-white p-4">
                        <div className="flex items-start justify-between gap-4">
                            <div>
                                <div className="text-sm font-semibold text-gray-900">
                                    {Craft.t('formie', 'Enable Integration Dispatch')}
                                </div>
                                <p className="mt-1 text-sm text-gray-500">
                                    {Craft.t('formie', 'Run enabled integrations sequentially in the order below, with control over immediate or queued execution. When disabled, enabled integrations run independently using Formie\'s default queue settings.')}
                                </p>
                            </div>
                            <Lightswitch
                                checked={Boolean(plan.enabled)}
                                onCheckedChange={(checked) => {
                                    const patch = { enabled: Boolean(checked) };

                                    if (checked && (!Array.isArray(plan.steps) || !plan.steps.length)) {
                                        patch.steps = buildDefaultSteps(payloadIntegrations);
                                    }

                                    updatePlan(patch);
                                }}
                                aria-label={Craft.t('formie', 'Enable Integration Dispatch')}
                            />
                        </div>
                    </div>

                    {plan.enabled && (
                        <>
                            <div className="flex flex-col gap-4">
                                <div>
                                    <label className="mb-1 block text-sm font-semibold text-gray-900">
                                        {Craft.t('formie', 'Notification Timing')}
                                    </label>
                                    <p className="mb-2 text-sm text-gray-500">
                                        {Craft.t('formie', 'When notifications with the default dispatch timing should be sent. Individual notifications can override this in Advanced settings.')}
                                    </p>
                                    <SelectInput
                                        value={plan.notificationTiming || 'beforeIntegrations'}
                                        options={NOTIFICATION_TIMING_OPTIONS}
                                        onChange={(value) => {
                                            updatePlan({ notificationTiming: String(value || 'beforeIntegrations') });
                                        }}
                                    />
                                </div>

                                <div>
                                    <label className="mb-1 block text-sm font-semibold text-gray-900">
                                        {Craft.t('formie', 'Failure Policy')}
                                    </label>
                                    <p className="mb-2 text-sm text-gray-500">
                                        {Craft.t('formie', 'Whether to continue running later integrations when one fails.')}
                                    </p>
                                    <SelectInput
                                        value={plan.failurePolicy || 'continue'}
                                        options={FAILURE_POLICY_OPTIONS}
                                        onChange={(value) => {
                                            updatePlan({ failurePolicy: String(value || 'continue') });
                                        }}
                                    />
                                </div>
                            </div>

                            <div>
                                <div className="mb-3">
                                    <h4 className="text-sm font-semibold text-gray-900">
                                        {Craft.t('formie', 'Integration Steps')}
                                    </h4>
                                    <p className="mt-1 text-sm text-gray-500">
                                        {Craft.t('formie', 'Integrations run top to bottom. Use Immediate for element integrations that must finish during the submission request, such as user registration or entry creation. Disabled integrations are skipped at runtime.')}
                                    </p>
                                </div>

                                <IntegrationDispatchStepList
                                    steps={resolvedSteps}
                                    integrationLookup={integrationLookup}
                                    isIntegrationEnabled={isIntegrationEnabled}
                                    isElementIntegration={isElementIntegration}
                                    onStepsChange={(steps) => {
                                        updatePlan({ steps });
                                    }}
                                    onStepModeChange={updateStepMode}
                                    onMoveStep={moveStep}
                                />
                            </div>
                        </>
                    )}
                </section>
            )}

            {enabledPayloadIntegrations.length > 0 && (
                <section className="space-y-4">
                    <IntegrationSettingsSectionHeading
                        title={Craft.t('formie', 'Re-Run')}
                        description={Craft.t('formie', 'Choose when integrations should run again after the initial submission. Integration conditions still apply.')}
                    />

                    <IntegrationRerunSettings
                        payloadIntegrations={payloadIntegrations}
                        enabledPayloadIntegrations={enabledPayloadIntegrations}
                        showHeading={false}
                    />
                </section>
            )}
        </div>
    );
}

export { DISPATCH_PATH, IntegrationDispatchSettings };
