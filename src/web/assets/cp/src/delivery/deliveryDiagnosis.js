const messageKeys = ['error', 'message', 'detail', 'reason', 'body'];

const scalarMessage = (value) => {
    if (typeof value === 'string' && value.trim()) {
        return value.trim();
    }

    if (typeof value === 'number') {
        return String(value);
    }

    return '';
};

export const evidenceMessage = (value, depth = 0) => {
    const scalar = scalarMessage(value);

    if (scalar || !value || typeof value !== 'object' || depth > 4) {
        return scalar;
    }

    for (const key of messageKeys) {
        const message = evidenceMessage(value[key], depth + 1);

        if (message) {
            return message;
        }
    }

    if (Array.isArray(value.exceptions)) {
        for (const exception of value.exceptions) {
            const message = evidenceMessage(exception, depth + 1);

            if (message) {
                return message;
            }
        }
    }

    for (const nested of Object.values(value)) {
        const message = evidenceMessage(nested, depth + 1);

        if (message) {
            return message;
        }
    }

    return '';
};

const latestCheckpoint = (checkpoints, matcher) =>
    [...checkpoints]
        .reverse()
        .find(({ checkpoint }) => matcher.test(checkpoint));

export const deliveryCheckpoints = (bundle = {}) => {
    const root = (bundle.checkpoints ?? []).map((checkpoint) => ({
        ...checkpoint,
        operation: null,
    }));
    const operations = (bundle.operations ?? []).flatMap((operation) =>
        (operation.checkpoints ?? []).map((checkpoint) => ({
            ...checkpoint,
            operation: {
                binding: operation.binding,
                status: operation.status,
                step: operation.step,
                uid: operation.uid,
            },
        })),
    );

    return [...root, ...operations];
};

export const diagnoseDelivery = (bundle = {}) => {
    const checkpoints = deliveryCheckpoints(bundle);
    const find = (matcher) => latestCheckpoint(checkpoints, matcher);
    let checkpoint = find(/^email-render-exception$/);

    if (checkpoint) {
        const response = find(/^email-response$/);

        return {
            kind: 'email-render',
            detail:
                evidenceMessage(response?.data) ||
                evidenceMessage(checkpoint.data),
            tab: response ? 'responses' : 'errors',
        };
    }

    checkpoint = find(/^operation-stale$/);

    if (checkpoint || bundle.result?.code === 'operation_stale') {
        return {
            kind: 'operation-stale',
            detail: evidenceMessage(checkpoint?.data),
            tab: 'timeline',
        };
    }

    checkpoint = find(/^email-exception$/);

    if (checkpoint) {
        return {
            kind: 'email-send',
            detail: evidenceMessage(checkpoint.data),
            tab: 'errors',
        };
    }

    checkpoint = find(/^(provider-error|response-error)$/);

    if (checkpoint) {
        return {
            kind: 'provider',
            detail: evidenceMessage(checkpoint.data),
            tab: 'errors',
        };
    }

    checkpoint = find(/^email-response$/);

    if (checkpoint && evidenceMessage(checkpoint.data)) {
        return {
            kind: 'email-response',
            detail: evidenceMessage(checkpoint.data),
            tab: 'responses',
        };
    }

    checkpoint = find(/^queue-error$/);

    if (checkpoint) {
        return {
            kind: 'queue',
            detail: evidenceMessage(checkpoint.data),
            tab: 'errors',
        };
    }

    if (bundle.status === 'unknown') {
        return { kind: 'unknown', detail: '', tab: 'timeline' };
    }

    if (['failed', 'rejected'].includes(bundle.status)) {
        return {
            kind: 'failed',
            detail: bundle.result?.code ?? '',
            tab: 'timeline',
        };
    }

    return { kind: 'status', detail: '', tab: 'timeline' };
};
