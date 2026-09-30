const resultSummary = (result) => result ? { status: result.status, code: result.code, retryable: result.retryable } : null;

export const diagnosticSummary = (bundle) => ({
    uid: bundle.uid,
    submissionId: bundle.submissionId,
    binding: bundle.binding,
    step: bundle.step,
    execution: bundle.execution,
    status: bundle.status,
    result: resultSummary(bundle.result),
    startedAt: bundle.startedAt,
    completedAt: bundle.completedAt,
    dateCreated: bundle.dateCreated,
    truncated: Boolean(bundle.truncated),
    checkpoints: (bundle.checkpoints ?? []).map(({ checkpoint, dateCreated }) => ({ checkpoint, dateCreated })),
    operations: (bundle.operations ?? []).map((operation) => ({
        uid: operation.uid,
        binding: operation.binding,
        step: operation.step,
        status: operation.status,
        result: resultSummary(operation.result),
        dateUpdated: operation.dateUpdated,
        checkpoints: (operation.checkpoints ?? []).map(({ checkpoint, dateCreated }) => ({ checkpoint, dateCreated })),
    })),
});
