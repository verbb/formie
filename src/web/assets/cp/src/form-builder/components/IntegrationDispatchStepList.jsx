import { Button, SelectInput } from '@verbb/plugin-kit-react/components';

const STEP_EXECUTION_OPTIONS = [
    { value: 'synchronous', label: Craft.t('formie', 'Synchronous') },
    { value: 'queued', label: Craft.t('formie', 'Queued') },
];

function IntegrationDispatchStepList({ steps, integrationLookup, isIntegrationEnabled, onStepsChange, onStepExecutionChange }) {
    const move = (index, otherIndex) => {
        const next = [...steps];
        [next[index], next[otherIndex]] = [next[otherIndex], next[index]];
        onStepsChange(next);
    };

    return (
        <div className="space-y-6">
            <p>{Craft.t('formie', 'Synchronous integrations run in order first. Queued integrations are then dispatched in order. Queueing does not mean delivery has completed.')}</p>
            {STEP_EXECUTION_OPTIONS.map((lane) => {
                const entries = steps.map((step, index) => ({ step, index })).filter(({ step }) => (step.execution || 'queued') === lane.value);
                return (
                    <section key={lane.value} aria-label={lane.label} className="space-y-2">
                        <h4>{lane.label}</h4>
                        {entries.length === 0 && <p>{Craft.t('formie', 'No integrations in this lane.')}</p>}
                        {entries.map(({ step, index }, position) => {
                            const integration = integrationLookup[step.handle];
                            if (!integration) return null;
                            return (
                                <div key={step.handle} className="flex items-center gap-3 rounded-lg border p-3">
                                    <span className="grow">{integration.name || step.handle}{!isIntegrationEnabled(step.handle) && ` (${Craft.t('formie', 'Disabled')})`}</span>
                                    <SelectInput aria-label={Craft.t('formie', 'Execution for {name}', { name: integration.name || step.handle })} value={step.execution || 'queued'} options={STEP_EXECUTION_OPTIONS} onChange={(value) => onStepExecutionChange(index, value)} />
                                    <Button type="button" disabled={position === 0} onClick={() => move(index, entries[position - 1].index)}>{Craft.t('formie', 'Move up')}</Button>
                                    <Button type="button" disabled={position === entries.length - 1} onClick={() => move(index, entries[position + 1].index)}>{Craft.t('formie', 'Move down')}</Button>
                                </div>
                            );
                        })}
                    </section>
                );
            })}
        </div>
    );
}

export { IntegrationDispatchStepList, STEP_EXECUTION_OPTIONS };
