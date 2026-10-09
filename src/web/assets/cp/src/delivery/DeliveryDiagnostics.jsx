import { useEffect, useState } from 'react';
import {
    Alert,
    Button,
    CopyButton,
    Dialog,
    Icon,
    Status,
    Tab,
    TabPanel,
    Tabs,
    Textarea,
} from '@verbb/plugin-kit-react/components';

import { deliveryCheckpoints, diagnoseDelivery } from './deliveryDiagnosis';

const evidenceGroups = [
    {
        id: 'errors',
        label: () => Craft.t('formie', 'Errors'),
        matches: (name) => /error|exception/.test(name),
    },
    {
        id: 'responses',
        label: () => Craft.t('formie', 'Provider responses'),
        matches: (name) => /response|provider-resource/.test(name),
    },
    {
        id: 'mappings',
        label: () => Craft.t('formie', 'Submission data'),
        matches: (name) => /mapping|submission-projection/.test(name),
    },
];

const groupCheckpoints = (checkpoints = []) => {
    const groups = evidenceGroups.map((group) => ({
        ...group,
        checkpoints: [],
    }));

    checkpoints.forEach((checkpoint) => {
        const group = groups.find((candidate) =>
            candidate.matches(checkpoint.checkpoint),
        );

        if (group) {
            group.checkpoints.push(checkpoint);
        }
    });

    return groups.filter((group) => group.checkpoints.length > 0);
};

const checkpointLabels = {
    prepared: () => Craft.t('formie', 'Delivery prepared'),
    queued: () => Craft.t('formie', 'Added to queue'),
    started: () => Craft.t('formie', 'Delivery started'),
    'submission-projection': () =>
        Craft.t('formie', 'Submission data captured'),
    'mapping-inputs': () => Craft.t('formie', 'Field mappings prepared'),
    request: () => Craft.t('formie', 'Request prepared'),
    response: () => Craft.t('formie', 'Provider response received'),
    'response-error': () => Craft.t('formie', 'Provider returned an error'),
    'provider-error': () => Craft.t('formie', 'Provider error recorded'),
    'email-prepared': () => Craft.t('formie', 'Email prepared'),
    'email-render-exception': () => Craft.t('formie', 'Email rendering failed'),
    'email-exception': () => Craft.t('formie', 'Email sending failed'),
    'email-response': () => Craft.t('formie', 'Email result recorded'),
    result: () => Craft.t('formie', 'Delivery result recorded'),
    'queue-error': () => Craft.t('formie', 'Queue job failed'),
    retry: () => Craft.t('formie', 'Delivery retried'),
    reconciled: () => Craft.t('formie', 'Outcome confirmed'),
};

const checkpointLabel = (name) =>
    checkpointLabels[name]?.() ??
    name
        .split('-')
        .map((word) => `${word.charAt(0).toUpperCase()}${word.slice(1)}`)
        .join(' ');

function JsonBlock({ value }) {
    const json = JSON.stringify(value, null, 2);

    return (
        <div className="formie-delivery-json-block">
            <div className="formie-delivery-json-block-scroll">
                <div className="formie-delivery-json-block-copy-overlay">
                    <CopyButton
                        className="formie-delivery-json-block-copy"
                        variant="transparent"
                        value={json}
                        aria-label={Craft.t('formie', 'Copy code')}
                        copiedLabel={Craft.t('formie', 'Copied')}
                    />
                </div>
                <pre className="formie-delivery-json-block-content">{json}</pre>
            </div>
        </div>
    );
}

function DeliveryTimeline({ checkpoints }) {
    return (
        <ol style={{ margin: 0, padding: '16px 16px 4px 40px' }}>
            {(checkpoints ?? []).map((checkpoint, index) => (
                <li
                    key={`${checkpoint.checkpoint}-${checkpoint.dateCreated}-${index}`}
                >
                    <strong>{checkpointLabel(checkpoint.checkpoint)}</strong>
                    {checkpoint.operation?.binding
                        ? ` · ${checkpoint.operation.binding}`
                        : ''}
                    {checkpoint.dateCreated
                        ? ` — ${checkpoint.dateCreated}`
                        : ''}
                </li>
            ))}
        </ol>
    );
}

function DiagnosticDetails({ bundle, overview }) {
    const checkpoints = deliveryCheckpoints(bundle);
    const groups = groupCheckpoints(checkpoints);
    const [activeTab, setActiveTab] = useState('overview');

    return (
        <Tabs
            className="formie-delivery-diagnostics-tabs"
            variant="pane"
            value={activeTab}
            aria-label={Craft.t('formie', 'Delivery diagnostic details')}
            style={{ width: '100%' }}
            onPkChange={(event) =>
                setActiveTab(event.detail?.value || 'overview')
            }
        >
            <Tab slot="nav" value="overview">
                {Craft.t('formie', 'Overview')}
            </Tab>
            {groups.map((group) => (
                <Tab key={group.id} slot="nav" value={group.id}>
                    {group.label()} ({group.checkpoints.length})
                </Tab>
            ))}
            <Tab slot="nav" value="timeline">
                {Craft.t('formie', 'Timeline')} ({checkpoints.length})
            </Tab>
            <TabPanel value="overview">
                <div style={{ padding: '16px 16px 4px' }}>{overview}</div>
            </TabPanel>
            {groups.map((group) => (
                <TabPanel key={group.id} value={group.id}>
                    <div
                        style={{
                            display: 'grid',
                            gap: '16px',
                            padding: '16px 16px 4px',
                        }}
                    >
                        {group.checkpoints.map((checkpoint, index) => (
                            <section
                                key={`${checkpoint.checkpoint}-${checkpoint.dateCreated}-${index}`}
                            >
                                <div
                                    style={{
                                        display: 'flex',
                                        flexWrap: 'wrap',
                                        alignItems: 'baseline',
                                        justifyContent: 'space-between',
                                        gap: '4px 12px',
                                        marginBlockEnd: '6px',
                                    }}
                                >
                                    <div>
                                        <h4 style={{ margin: 0 }}>
                                            {checkpointLabel(
                                                checkpoint.checkpoint,
                                            )}
                                        </h4>
                                        <code
                                            className="light"
                                            style={{ fontSize: '11px' }}
                                        >
                                            {checkpoint.checkpoint}
                                        </code>
                                    </div>
                                    {checkpoint.dateCreated && (
                                        <span className="light">
                                            {checkpoint.operation?.binding
                                                ? `${checkpoint.operation.binding} · `
                                                : ''}
                                            {checkpoint.dateCreated}
                                        </span>
                                    )}
                                </div>
                                <JsonBlock value={checkpoint.data} />
                            </section>
                        ))}
                    </div>
                </TabPanel>
            ))}
            <TabPanel value="timeline">
                <DeliveryTimeline checkpoints={checkpoints} />
            </TabPanel>
        </Tabs>
    );
}

const statusPresentation = (status) =>
    ({
        failed: {
            tone: 'red',
            title: Craft.t('formie', 'Delivery failed'),
            message: Craft.t(
                'formie',
                'Formie could not complete this delivery.',
            ),
        },
        unknown: {
            tone: 'warning',
            title: Craft.t('formie', 'Delivery outcome unknown'),
            message: Craft.t(
                'formie',
                'Formie could not confirm whether the provider completed this delivery. Check the provider before recording an outcome.',
            ),
        },
        succeeded: {
            tone: 'green',
            title: Craft.t('formie', 'Delivered successfully'),
            message: Craft.t(
                'formie',
                'The provider confirmed that this delivery completed.',
            ),
        },
        sending: {
            tone: 'pending',
            title: Craft.t('formie', 'Delivery in progress'),
            message: Craft.t(
                'formie',
                'Formie is still waiting for this delivery to finish.',
            ),
        },
        pending: {
            tone: 'pending',
            title: Craft.t('formie', 'Waiting to send'),
            message: Craft.t('formie', 'This delivery has not started yet.'),
        },
        rejected: {
            tone: 'red',
            title: Craft.t('formie', 'Delivery rejected'),
            message: Craft.t(
                'formie',
                'The delivery was rejected before it could complete.',
            ),
        },
        skipped: {
            tone: 'gray',
            title: Craft.t('formie', 'Delivery skipped'),
            message: Craft.t(
                'formie',
                'Formie determined that this delivery did not need to run.',
            ),
        },
    })[status] ?? {
        tone: 'gray',
        title: Craft.t('formie', 'Delivery status: {status}', { status }),
        message: Craft.t(
            'formie',
            'Review the timeline and evidence below for more information.',
        ),
    };

function DeliveryHeader({ bundle, onExport }) {
    const presentation = statusPresentation(bundle.status);

    return (
        <section
            className="formie-delivery-diagnostics-header"
            style={{
                marginBlockEnd: '12px',
                padding: '12px',
                border: '1px solid var(--hairline-color)',
                borderRadius: '6px',
                background: 'var(--gray-050)',
            }}
        >
            <div
                style={{
                    display: 'flex',
                    flexWrap: 'wrap',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    gap: '16px',
                }}
            >
                <div style={{ minWidth: 0, flex: '1 1 260px' }}>
                    <div
                        style={{
                            display: 'flex',
                            alignItems: 'center',
                            gap: '8px',
                        }}
                    >
                        <Status
                            status={presentation.tone}
                            aria-label={presentation.title}
                        />
                        <h3 style={{ margin: 0, fontSize: '16px' }}>
                            {presentation.title}
                        </h3>
                    </div>
                </div>
                <div style={{ display: 'flex', flexWrap: 'wrap', gap: '8px' }}>
                    <Button
                        href={bundle.submissionUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        {Craft.t('formie', 'Open submission')}
                        <Icon
                            slot="end"
                            icon="arrow-up-right-from-square"
                            className="size-3"
                        />
                    </Button>
                    <Button type="button" onClick={onExport}>
                        <Icon slot="start" icon="download" className="size-3" />
                        {Craft.t('formie', 'Export')}
                    </Button>
                </div>
            </div>
        </section>
    );
}

const diagnosisPresentation = (diagnosis) =>
    ({
        'email-render': {
            title: Craft.t(
                'formie',
                'The notification template could not be rendered',
            ),
            description: Craft.t(
                'formie',
                'Formie stopped before sending because part of the email content could not be processed.',
            ),
            nextStep: Craft.t(
                'formie',
                'Open the submission and review the notification content, Twig, and field references mentioned in the error.',
            ),
        },
        'operation-stale': {
            title: Craft.t('formie', 'The delivery changed before it was sent'),
            description: Craft.t(
                'formie',
                'The form, notification, or integration settings no longer match the operation that was queued.',
            ),
            nextStep: Craft.t(
                'formie',
                'Review the current settings, then retry the queue job only if the new configuration should be used.',
            ),
        },
        'email-send': {
            title: Craft.t('formie', 'The email failed while being sent'),
            description: Craft.t(
                'formie',
                'The notification rendered, but the mailer encountered an error during delivery.',
            ),
            nextStep: Craft.t(
                'formie',
                'Check the reported error, Craft mail settings, and whether the queue worker uses the same mail configuration as web requests.',
            ),
        },
        provider: {
            title: Craft.t('formie', 'The provider returned an error'),
            description: Craft.t(
                'formie',
                'Formie reached the integration provider, but the provider did not accept or complete the request.',
            ),
            nextStep: Craft.t(
                'formie',
                'Start with the reported error, then check the integration credentials, endpoint settings, and mapped fields.',
            ),
        },
        'email-response': {
            title: Craft.t('formie', 'The email was not sent'),
            description: Craft.t(
                'formie',
                'The notification process returned an error before delivery could be confirmed.',
            ),
            nextStep: Craft.t(
                'formie',
                'Review the reported error first, then check the notification content and Craft mail settings.',
            ),
        },
        queue: {
            title: Craft.t('formie', 'The queue job stopped with an error'),
            description: Craft.t(
                'formie',
                'The background worker could not complete the delivery job.',
            ),
            nextStep: Craft.t(
                'formie',
                'Review the reported exception and the Craft queue logs. Retry the job after fixing the underlying error.',
            ),
        },
        unknown: {
            title: Craft.t(
                'formie',
                'Confirm whether the provider received this delivery',
            ),
            description: Craft.t(
                'formie',
                'Formie lost confirmation after delivery started, so retrying immediately could create a duplicate.',
            ),
            nextStep: Craft.t(
                'formie',
                'Check the provider first, then record the confirmed outcome below.',
            ),
        },
        failed: {
            title: Craft.t('formie', 'The delivery did not complete'),
            description: Craft.t(
                'formie',
                'No more specific cause was identified from the retained evidence.',
            ),
            nextStep: Craft.t(
                'formie',
                'Review the timeline and error details. Export the diagnostics if you need help from support.',
            ),
        },
        status: {
            title: Craft.t('formie', 'Review the delivery details'),
            description: Craft.t(
                'formie',
                'The retained evidence below shows what Formie recorded during this delivery.',
            ),
            nextStep: Craft.t(
                'formie',
                'Use the timeline to follow the delivery in order, then inspect a technical category if needed.',
            ),
        },
    })[diagnosis.kind];

function Diagnosis({ diagnosis }) {
    const presentation = diagnosisPresentation(diagnosis);

    return (
        <section style={{ marginBlockEnd: '20px' }}>
            <p className="light" style={{ margin: '0 0 4px', fontWeight: 600 }}>
                {Craft.t('formie', 'Where to start')}
            </p>
            <h3 style={{ margin: 0 }}>{presentation.title}</h3>
            <p style={{ margin: '6px 0 0' }}>{presentation.description}</p>
            {diagnosis.detail && (
                <div
                    style={{
                        marginBlockStart: '12px',
                        padding: '12px',
                        borderInlineStart:
                            '3px solid var(--pk-color-error, #d81f23)',
                        background: 'var(--gray-050)',
                        overflowWrap: 'anywhere',
                    }}
                >
                    <strong>{Craft.t('formie', 'Reported error')}</strong>
                    <p style={{ margin: '4px 0 0' }}>{diagnosis.detail}</p>
                </div>
            )}
            <p style={{ margin: '12px 0 0' }}>
                <strong>{Craft.t('formie', 'Next step:')}</strong>{' '}
                {presentation.nextStep}
            </p>
        </section>
    );
}

function DeliveryOverview({
    diagnosis,
    bundle,
    canReconcile,
    canForce,
    busy,
    notice,
    reason,
    onReasonChange,
    onAction,
}) {
    return (
        <>
            <Diagnosis diagnosis={diagnosis} />
            {canReconcile && (
                <div>
                    <p>
                        {Craft.t(
                            'formie',
                            'Confirm the outcome with the provider before recording a decision.',
                        )}
                    </p>
                    <Textarea
                        label={Craft.t('formie', 'Reconciliation reason')}
                        value={reason}
                        onInput={(event) => onReasonChange(event.target.value)}
                    />
                    <div
                        style={{
                            display: 'flex',
                            flexWrap: 'wrap',
                            gap: '8px',
                            margin: '12px 0',
                        }}
                    >
                        <Button
                            type="button"
                            disabled={busy || !reason.trim()}
                            onClick={() =>
                                onAction('reconcile', {
                                    outcome: 'succeeded',
                                    reason,
                                })
                            }
                        >
                            {Craft.t('formie', 'Confirm delivered')}
                        </Button>
                        <Button
                            type="button"
                            disabled={busy || !reason.trim()}
                            onClick={() =>
                                onAction('reconcile', {
                                    outcome: 'notDelivered',
                                    reason,
                                })
                            }
                        >
                            {Craft.t('formie', 'Confirm not delivered')}
                        </Button>
                    </div>
                </div>
            )}
            {canForce && (
                <div>
                    <p>
                        {Craft.t(
                            'formie',
                            'A force run creates a new execution and overrides conditions and opt-in. Provide a reason for the audit history.',
                        )}
                    </p>
                    <Textarea
                        label={Craft.t('formie', 'Force run reason')}
                        value={reason}
                        onInput={(event) => onReasonChange(event.target.value)}
                    />
                    <Button
                        type="button"
                        disabled={busy || !reason.trim()}
                        onClick={() =>
                            onAction('force', {
                                submissionId: bundle.submissionId,
                                handle: bundle.binding,
                                reason,
                            })
                        }
                    >
                        {Craft.t('formie', 'Force new run')}
                    </Button>
                </div>
            )}
            {notice && (
                <Alert variant="success" size="sm" announce="polite">
                    {notice}
                </Alert>
            )}
        </>
    );
}

export function DeliveryDiagnostics({ uid, onClose }) {
    const [bundle, setBundle] = useState(null);
    const [selected, setSelected] = useState(uid);
    const [error, setError] = useState('');
    const [notice, setNotice] = useState('');
    const [reason, setReason] = useState('');
    const [busy, setBusy] = useState(false);

    useEffect(() => {
        setBundle(null);
        setError('');
        if (!selected) return;
        let current = true;
        Craft.sendActionRequest('GET', 'formie/delivery/bundle', {
            params: { uid: selected },
        })
            .then(({ data }) => {
                if (current) setBundle(data);
            })
            .catch(() => {
                if (current)
                    setError(
                        Craft.t(
                            'formie',
                            'Unable to load delivery diagnostics. Check your permissions.',
                        ),
                    );
            });
        return () => {
            current = false;
        };
    }, [selected]);

    const act = async (action, data) => {
        setBusy(true);
        setError('');
        try {
            await Craft.sendActionRequest('POST', `formie/delivery/${action}`, {
                data: { uid: selected, ...data },
            });
            const response = await Craft.sendActionRequest(
                'GET',
                'formie/delivery/bundle',
                { params: { uid: selected } },
            );
            setBundle(response.data);
            setNotice(
                action === 'force'
                    ? Craft.t('formie', 'Force run recorded.')
                    : Craft.t('formie', 'Reconciliation recorded.'),
            );
        } catch (error) {
            setError(
                error.response?.data?.message ||
                    Craft.t('formie', 'Unable to update this delivery.'),
            );
        } finally {
            setBusy(false);
        }
    };

    const download = (data) => {
        const url = URL.createObjectURL(
            new Blob([JSON.stringify(data, null, 2)], {
                type: 'application/json',
            }),
        );
        const link = document.createElement('a');
        link.href = url;
        link.download = `formie-delivery-${selected}.json`;
        link.click();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    };

    const exportBundle = async () => {
        try {
            const { data } = await Craft.sendActionRequest(
                'POST',
                'formie/delivery/export-bundle',
                { data: { uid: selected } },
            );
            download(data);
        } catch {
            setError(
                Craft.t(
                    'formie',
                    'Unable to export delivery diagnostics. Check your permissions.',
                ),
            );
        }
    };

    const canReconcile =
        bundle?.canReconcile && ['sending', 'unknown'].includes(bundle.status);
    const canForce =
        bundle?.canForce && !['sending', 'unknown'].includes(bundle.status);
    const diagnosis = bundle ? diagnoseDelivery(bundle) : null;

    return (
        <>
            {!selected && error && (
                <Alert variant="error" size="sm" announce="assertive">
                    {error}
                </Alert>
            )}
            <Dialog
                className="formie-delivery-diagnostics-dialog"
                open={Boolean(selected)}
                label={Craft.t('formie', 'Formie Delivery Diagnostics')}
                size="wide"
                style={{
                    '--pk-dialog-width': 'min(calc(100vw - 24px), 78rem)',
                    '--pk-dialog-max-width': 'min(calc(100vw - 24px), 78rem)',
                    '--pk-dialog-height': 'min(calc(100dvh - 32px), 50rem)',
                    '--pk-dialog-min-height': 'min(calc(100dvh - 32px), 50rem)',
                    '--pk-dialog-max-height': 'min(calc(100dvh - 32px), 50rem)',
                }}
                onPkOpenChange={(event) => {
                    if (!(event.detail?.open ?? event.target?.open)) {
                        setSelected(null);
                        onClose?.();
                    }
                }}
            >
                <div className="formie-delivery-diagnostics-layout">
                    {error && (
                        <Alert variant="error" size="sm" announce="assertive">
                            {error}
                        </Alert>
                    )}
                    {!error && !bundle && (
                        <p style={{ marginBlockStart: 0 }}>
                            {Craft.t('formie', 'Loading diagnostics…')}
                        </p>
                    )}
                    {bundle && diagnosis && (
                        <>
                            <DeliveryHeader
                                bundle={bundle}
                                onExport={exportBundle}
                            />
                            <DiagnosticDetails
                                key={bundle.uid}
                                bundle={bundle}
                                overview={
                                    <DeliveryOverview
                                        diagnosis={diagnosis}
                                        bundle={bundle}
                                        canReconcile={canReconcile}
                                        canForce={canForce}
                                        busy={busy}
                                        notice={notice}
                                        reason={reason}
                                        onReasonChange={setReason}
                                        onAction={act}
                                    />
                                }
                            />
                        </>
                    )}
                </div>
            </Dialog>
        </>
    );
}
