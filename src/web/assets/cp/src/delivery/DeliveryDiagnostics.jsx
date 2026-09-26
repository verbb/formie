import { useEffect, useState } from 'react';
import { Button, Dialog, Textarea } from '@verbb/plugin-kit-react/components';

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

    const exportSensitive = async () => {
        try {
            const { data } = await Craft.sendActionRequest('POST', 'formie/delivery/sensitive-evidence', { data: { uid: selected, acknowledged } });
            download(data, '-sensitive');
        } catch {
            setError(Craft.t('formie', 'Sensitive evidence is unavailable or you do not have permission.'));
        }
    };

    return (
        <>
            {!selected && error && <p role="alert">{error}</p>}
            {submissionId && <section><h3>{Craft.t('formie', 'Submission Delivery History')}</h3>
                {attempts.length === 0 && <p>{Craft.t('formie', 'No delivery attempts recorded.')}</p>}
                {attempts.map((attempt) => <p key={attempt.uid}><Button type="button" onClick={() => setSelected(attempt.uid)}>{attempt.binding}: {attempt.step} ({attempt.status})</Button></p>)}
            </section>}
            <Dialog open={Boolean(selected)} label={Craft.t('formie', 'Formie Delivery Diagnostics')} size="wide" onPkOpenChange={(event) => {
                if (!(event.detail?.open ?? event.target?.open)) { setSelected(null); onClose?.(); }
            }}>
                {error && <p role="alert">{error}</p>}
                {!error && !bundle && <p>{Craft.t('formie', 'Loading diagnostics…')}</p>}
                {bundle && <>
                    <p>{Craft.t('formie', 'This support bundle is redacted. Completed delivery evidence is retained for 30 days. Unresolved delivery data remains available for reconciliation.')}</p>
                    <p><strong>{Craft.t('formie', 'Status')}: {bundle.status}</strong> · {bundle.binding}</p>
                    <p><a href={bundle.submissionUrl}>{Craft.t('formie', 'Open Submission Delivery History')}</a></p>
                    <pre style={{ whiteSpace: 'pre-wrap', overflowWrap: 'anywhere', maxHeight: '50vh', overflow: 'auto' }}>{JSON.stringify(bundle, null, 2)}</pre>
                    <div style={{ display: 'flex', flexWrap: 'wrap', gap: '8px', margin: '12px 0' }}>
                        <Button type="button" onClick={async () => { try { await navigator.clipboard.writeText(JSON.stringify(bundle, null, 2)); setNotice(Craft.t('formie', 'Support bundle copied.')); } catch { setError(Craft.t('formie', 'Copy failed. Download the bundle instead.')); } }}>{Craft.t('formie', 'Copy support bundle')}</Button>
                        <Button type="button" onClick={() => download(bundle)}>{Craft.t('formie', 'Download support bundle')}</Button>
                    </div>
                    {bundle.canExportSensitive && <div>
                        <label><input type="checkbox" checked={acknowledged} onChange={(event) => setAcknowledged(event.target.checked)} /> {Craft.t('formie', 'I understand this export may contain personal data.')}</label>
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
                    <p role="status">{notice}</p>
                </>}
            </Dialog>
        </>
    );
}
