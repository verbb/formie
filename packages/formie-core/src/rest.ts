import type {
    FrontendFormDefinition,
    FrontendFormEnvelope,
    FrontendFormSession,
    FrontendSubmitResult,
    FrontendTransport,
} from './types';
import { serializeTransportFieldValues } from './schema';

export type RestFrontendTransportOptions = {
    endpoint: string;
    formHandle: string;
    siteId?: number;
    credentials?: RequestCredentials;
};

function buildActionUrl(baseUrl: string, path: string): string {
    if (path.startsWith('http://') || path.startsWith('https://')) {
        return path;
    }

    if (baseUrl.startsWith('http://') || baseUrl.startsWith('https://')) {
        return new URL(path, baseUrl).toString();
    }

    const normalizedBaseUrl = baseUrl.trim();

    if (!normalizedBaseUrl || normalizedBaseUrl === '/') {
        return path;
    }

    return `${normalizedBaseUrl.replace(/\/+$/, '')}${path}`;
}

async function requestJson<T>(url: string, init: RequestInit): Promise<T> {
    const response = await fetch(url, init);

    if (!response.ok) {
        throw new Error(`Request failed with status ${response.status}.`);
    }

    return response.json() as Promise<T>;
}

export async function loadFrontendEnvelope(options: RestFrontendTransportOptions): Promise<FrontendFormEnvelope> {
    const url = buildActionUrl(options.endpoint, '/actions/formie/client/forms/load');
    const body = JSON.stringify({
        handle: options.formHandle,
        siteId: options.siteId,
    });

    return requestJson<FrontendFormEnvelope>(url, {
        method: 'POST',
        credentials: options.credentials ?? 'same-origin',
        headers: {
            'Content-Type': 'application/json',
        },
        body,
    });
}

export function createRestFrontendTransport(options: RestFrontendTransportOptions): FrontendTransport {
    return {
        async submit({ definition, session, values, action }): Promise<FrontendSubmitResult> {
            const url = buildActionUrl(options.endpoint, '/actions/formie/client/submissions/submit');
            const serializedValues = await serializeTransportFieldValues(definition, values);

            return requestJson<FrontendSubmitResult>(url, {
                method: 'POST',
                credentials: options.credentials ?? 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    handle: options.formHandle,
                    siteId: options.siteId,
                    action,
                    session,
                    values: serializedValues,
                }),
            });
        },
        async refreshSession({ session }): Promise<FrontendFormSession> {
            const url = buildActionUrl(options.endpoint, '/actions/formie/client/sessions/refresh');

            return requestJson<FrontendFormSession>(url, {
                method: 'POST',
                credentials: options.credentials ?? 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    handle: options.formHandle,
                    siteId: options.siteId,
                    session,
                }),
            });
        },
        async setPage({ definition, session, values, currentPageId, targetPageId }): Promise<FrontendFormSession> {
            const url = buildActionUrl(options.endpoint, '/actions/formie/client/forms/page');
            const serializedValues = await serializeTransportFieldValues(definition, values);

            return requestJson<FrontendFormSession>(url, {
                method: 'POST',
                credentials: options.credentials ?? 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    handle: options.formHandle,
                    siteId: options.siteId,
                    currentPageId,
                    targetPageId,
                    session,
                    values: serializedValues,
                }),
            });
        },
    };
}
