import { useEffect, useState } from 'react';
import { Alert, Button, Dialog, Textarea } from '@verbb/plugin-kit-react/components';

import { diagnosticSummary } from './deliveryDiagnostics';

const evidenceGroups = [
    { id: 'mappings', label: () => Craft.t('formie', 'Mappings and submission values'), matches: (name) => /mapping|submission-projection/.test(name) },
    { id: 'requests', label: () => Craft.t('formie', 'Provider requests'), matches: (name) => /request/.test(name) },
    { id: 'responses', label: () => Craft.t('formie', 'Provider responses'), matches: (name) => /response|provider-resource/.test(name) },
    { id: 'errors', label: () => Craft.t('formie', 'Errors'), matches: (name) => /error|exception/.test(name) },
];

const groupCheckpoints = (checkpoints = []) => {
    const groups = evidenceGroups.map((group) => ({ ...group, checkpoints: [] }));
    const lifecycle = { id: 'lifecycle', label: () => Craft.t('formie', 'Lifecycle and decisions'), checkpoints: [] };

    checkpoints.forEach((checkpoint) => {
        const group = groups.find((candidate) => candidate.matches(checkpoint.checkpoint));
        (group ?? lifecycle).checkpoints.push(checkpoint);
    });

    return [...groups, lifecycle].filter((group) => group.checkpoints.length > 0);
};

function JsonBlock({ value }) {
    return <pre style={{ whiteSpace: 'pre-wrap', overflowWrap: 'anywhere', maxHeight: '28vh', overflow: 'auto', padding: '12px', background: 'var(--gray-050)', borderRadius: '6px' }}>{JSON.stringify(value, null, 2)}</pre>;
}

function EvidenceGroups({ checkpoints }) {
    return <div>
        {groupCheckpoints(checkpoints).map((group) => <details key={group.id} open={group.id === 'errors'} style={{ marginBlock: '8px' }}>
            <summary style={{ cursor: 'pointer', fontWeight: 600 }}>{group.label()} ({group.checkpoints.length})</summary>
            <div style={{ marginInlineStart: '16px' }}>
                {group.checkpoints.map((checkpoint, index) => <section key={`${checkpoint.checkpoint}-${checkpoint.dateCreated}-${index}`} style={{ marginBlock: '12px' }}>
                    <h4 style={{ marginBlockEnd: '4px' }}>{checkpoint.checkpoint}</h4>
                    {checkpoint.dateCreated && <p className="light" style={{ marginBlock: '0 0 6px' }}>{checkpoint.dateCreated}</p>}
                    <JsonBlock value={checkpoint.data} />
                </section>)}
            </div>
        </details>)}
    </div>;
}

function DeliveryOverview({ bundle }) {
    const items = [
        [Craft.t('formie', 'Status'), bundle.status],
        [Craft.t('formie', 'Integration'), bundle.binding],
        [Craft.t('formie', 'Operation'), bundle.step],
        [Craft.t('formie', 'Execution'), bundle.execution],
        [Craft.t('formie', 'Result code'), bundle.result?.code || Craft.t('formie', 'None')],
        [Craft.t('formie', 'Started'), bundle.startedAt || Craft.t('formie', 'Not started')],
        [Craft.t('formie', 'Completed'), bundle.completedAt || Craft.t('formie', 'Not completed')],
    ];

    return <dl style={{ display: 'grid', gridTemplateColumns: 'max-content minmax(0, 1fr)', gap: '6px 16px', marginBlock: '12px 20px' }}>
        {items.map(([label, value]) => <div key={label} style={{ display: 'contents' }}>
            <dt style={{ fontWeight: 600 }}>{label}</dt>
            <dd style={{ margin: 0, overflowWrap: 'anywhere' }}>{value}</dd>
        </div>)}
    </dl>;
}

function DeliveryTimeline({ checkpoints }) {
    return <ol style={{ marginBlock: '8px 20px', paddingInlineStart: '24px' }}>
        {(checkpoints ?? []).map((checkpoint, index) => <li key={`${checkpoint.checkpoint}-${checkpoint.dateCreated}-${index}`}>
            <strong>{checkpoint.checkpoint}</strong>{checkpoint.dateCreated ? ` — ${checkpoint.dateCreated}` : ''}
        </li>)}
    </ol>;
}

function DeliveryOperations({ operations }) {
    if (!operations?.length) {
        return <p>{Craft.t('formie', 'No child operations were recorded for this delivery.')}</p>;
    }

    return <div>
        {operations.map((operation) => <details key={operation.uid} style={{ marginBlock: '8px' }}>
            <summary style={{ cursor: 'pointer', fontWeight: 600 }}>{operation.binding}: {operation.step} ({operation.status})</summary>
            <div style={{ marginInlineStart: '16px' }}>
                {operation.result?.code && <p><strong>{Craft.t('formie', 'Result code')}:</strong> {operation.result.code}</p>}
                <EvidenceGroups checkpoints={operation.checkpoints} />
            </div>
        </details>)}
    </div>;
}

export function DeliveryDiagnostics({ uid, submissionId = null, onClose }) {
    const [bundle, setBundle] = useState(null);
    const [attempts, setAttempts] = useState([]);
    const [selected, setSelected] = useState(uid);
    const [error, setError] = useState('');
    const [acknowledged, setAcknowledged] = useState(false);
    const [notice, setNotice] = useState('');
    const [reason, setReason] = useState('');
    const [busy, setBusy] = useState(false);

    useEffect(() => {
        if (!submissionId) return;
        let current = true;
        Craft.sendActionRequest('GET', 'formie/delivery/history', { params: { submissionId } })
            .then(({ data }) => { if (current) setAttempts(data.attempts); })
            .catch(() => { if (current) setError(Craft.t('formie', 'Unable to load delivery history.')); });
        return () => { current = false; };
    }, [submissionId]);

    useEffect(() => {
        setBundle(null);
        setError('');
        setAcknowledged(false);
        if (!selected) return;
        let current = true;
        Craft.sendActionRequest('GET', 'formie/delivery/bundle', { params: { uid: selected } })
            .then(({ data }) => { if (current) setBundle(data); })
            .catch(() => { if (current) setError(Craft.t('formie', 'Unable to load delivery diagnostics. Check your permissions.')); });
        return () => { current = false; };
    }, [selected]);

    const act = async (action, data) => {
        setBusy(true);
        setError('');
        try {
            await Craft.sendActionRequest('POST', `formie/delivery/${action}`, { data: { uid: selected, ...data } });
            const response = await Craft.sendActionRequest('GET', 'formie/delivery/bundle', { params: { uid: selected } });
            setBundle(response.data);
            setNotice(action === 'retry' ? Craft.t('formie', 'Retry queued with the original delivery identity.') : action === 'force' ? Craft.t('formie', 'Force run recorded.') : Craft.t('formie', 'Reconciliation recorded.'));
        } catch (error) {
            setError(error.response?.data?.message || Craft.t('formie', 'Unable to update this delivery.'));
        } finally { setBusy(false); }
    };

    const download = (data, suffix = '') => {
        const url = URL.createObjectURL(new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' }));
        const link = document.createElement('a');
        link.href = url;
        link.download = `formie-delivery-${selected}${suffix}.json`;
        link.click();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    };

    const copySummary = async () => {
        try {
            await navigator.clipboard.writeText(JSON.stringify(diagnosticSummary(bundle), null, 2));
            setNotice(Craft.t('formie', 'Diagnostic summary copied without submission values.'));
        } catch {
            setError(Craft.t('formie', 'Copy failed. Download the full redacted bundle instead.'));
        }
    };

    const exportSensitive = async () => {
        try {
            const { data } = await Craft.sendActionRequest('POST', 'formie/delivery/sensitive-evidence', { data: { uid: selected, acknowledged } });
            download(data, '-sensitive');
        } catch {
            setError(Craft.t('formie', 'Sensitive evidence is unavailable or you do not have permission.'));
        }
    };

    const exportBundle = async () => {
        try {
            const { data } = await Craft.sendActionRequest('POST', 'formie/delivery/export-bundle', { data: { uid: selected, acknowledged } });
            download(data);
        } catch {
            setError(Craft.t('formie', 'Unable to export delivery diagnostics. Check your permissions and acknowledgement.'));
        }
    };

    return (
        <>
            {!selected && error && <Alert variant="error" size="sm" announce="assertive">{error}</Alert>}
            {submissionId && <section><h3>{Craft.t('formie', 'Submission Delivery History')}</h3>
                {attempts.length === 0 && <p>{Craft.t('formie', 'No delivery attempts recorded.')}</p>}
                {attempts.map((attempt) => <p key={attempt.uid}><Button type="button" onClick={() => setSelected(attempt.uid)}>{attempt.binding}: {attempt.step} ({attempt.status})</Button></p>)}
            </section>}
            <Dialog open={Boolean(selected)} label={Craft.t('formie', 'Formie Delivery Diagnostics')} size="wide" onPkOpenChange={(event) => {
                if (!(event.detail?.open ?? event.target?.open)) { setSelected(null); onClose?.(); }
            }}>
                {error && <Alert variant="error" size="sm" announce="assertive">{error}</Alert>}
                {!error && !bundle && <p>{Craft.t('formie', 'Loading diagnostics…')}</p>}
                {bundle && <>
                    <p>{Craft.t('formie', 'Credentials are redacted from this view. Retained evidence can still contain personal submission data. Completed delivery evidence is retained for {days} days; unresolved evidence remains available for reconciliation.', { days: bundle.retentionDays })}</p>
                    <p><a href={bundle.submissionUrl}>{Craft.t('formie', 'Open Submission Delivery History')}</a></p>

                    <h3>{Craft.t('formie', 'Overview')}</h3>
                    <DeliveryOverview bundle={bundle} />

                    <h3>{Craft.t('formie', 'Delivery timeline')}</h3>
                    <DeliveryTimeline checkpoints={bundle.checkpoints} />

                    <h3>{Craft.t('formie', 'Diagnostic evidence')}</h3>
                    <EvidenceGroups checkpoints={bundle.checkpoints} />

                    <h3>{Craft.t('formie', 'Child operations')}</h3>
                    <DeliveryOperations operations={bundle.operations} />

                    <h3>{Craft.t('formie', 'Support export')}</h3>
                    <p>{Craft.t('formie', 'Copy a value-free summary for an initial support request, or download the complete retained bundle when mapped values and provider evidence are required.')}</p>
                    <label><input type="checkbox" checked={acknowledged} onChange={(event) => setAcknowledged(event.target.checked)} /> {Craft.t('formie', 'I understand this export may contain personal data.')}</label>
                    <div style={{ display: 'flex', flexWrap: 'wrap', gap: '8px', margin: '12px 0' }}>
                        <Button type="button" onClick={copySummary}>{Craft.t('formie', 'Copy diagnostic summary')}</Button>
                        <Button type="button" disabled={!acknowledged} onClick={exportBundle}>{Craft.t('formie', 'Download full redacted bundle')}</Button>
                    </div>
                    {bundle.canExportSensitive && <div>
                        <Button type="button" disabled={!acknowledged} onClick={exportSensitive}>{Craft.t('formie', 'Export sensitive evidence')}</Button>
                    </div>}
                    {bundle.canReconcile && ['sending', 'unknown'].includes(bundle.status) && <div>
                        <p>{Craft.t('formie', 'Confirm the outcome with the provider before recording a decision. Reconcile uncertain child operations before their parent.')}</p>
                        <Textarea label={Craft.t('formie', 'Reconciliation reason')} value={reason} onInput={(event) => setReason(event.target.value)} />
                        <div style={{ display: 'flex', flexWrap: 'wrap', gap: '8px', margin: '12px 0' }}>
                            <Button type="button" disabled={busy || !reason.trim()} onClick={() => act('reconcile', { outcome: 'succeeded', reason })}>{Craft.t('formie', 'Confirm delivered')}</Button>
                            <Button type="button" disabled={busy || !reason.trim()} onClick={() => act('reconcile', { outcome: 'notDelivered', reason })}>{Craft.t('formie', 'Confirm not delivered')}</Button>
                        </div>
                    </div>}
                    {bundle.canRetry && bundle.status === 'failed' && <Button type="button" disabled={busy} onClick={() => act('retry', {})}>{Craft.t('formie', 'Retry safe delivery')}</Button>}
                    {bundle.canForce && !['sending', 'unknown'].includes(bundle.status) && <div>
                        <p>{Craft.t('formie', 'A force run creates a new execution and overrides conditions and opt-in. Provide a reason for the audit history.')}</p>
                        <Textarea label={Craft.t('formie', 'Force run reason')} value={reason} onInput={(event) => setReason(event.target.value)} />
                        <Button type="button" disabled={busy || !reason.trim()} onClick={() => act('force', { submissionId: bundle.submissionId, handle: bundle.binding, reason })}>{Craft.t('formie', 'Force new run')}</Button>
                    </div>}
                    {notice && <Alert variant="success" size="sm" announce="polite">{notice}</Alert>}
                </>}
            </Dialog>
        </>
    );
}
