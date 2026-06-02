import type {
    FrontendFormDefinition,
    FrontendFormEnvelope,
    FrontendFormSession,
    FrontendSubmitResult,
    FrontendTransport,
} from './types';
import { serializeTransportFieldValues } from './schema';

export type GraphqlFrontendTransportOptions = {
    endpoint: string;
    formHandle: string;
    siteId?: number;
    credentials?: RequestCredentials;
};

type GraphqlResponse<T> = {
    data?: T;
    errors?: Array<{ message?: string }>;
};

const FRONTEND_SESSION_SELECTION = `
    id
    currentPageId
    tokens
    continuation
`;

const FRONTEND_SUBMIT_RESULT_SELECTION = `
    success
    submissionUid
    currentPageId
    nextPageId
    previousPageId
    isFinalPage
    errors
    messages
    session {
        ${FRONTEND_SESSION_SELECTION}
    }
`;

function buildGraphqlUrl(endpoint: string): string {
    if (endpoint.startsWith('http://') || endpoint.startsWith('https://')) {
        return endpoint;
    }

    const normalizedEndpoint = endpoint.trim();

    if (!normalizedEndpoint || normalizedEndpoint === '/') {
        return '/api';
    }

    return normalizedEndpoint;
}

async function requestGraphql<T>(options: GraphqlFrontendTransportOptions, query: string, variables: Record<string, unknown>): Promise<T> {
    const response = await fetch(buildGraphqlUrl(options.endpoint), {
        method: 'POST',
        // Default `same-origin`: credentialed cross-origin + `Allow-Origin: *` is invalid in browsers.
        credentials: options.credentials ?? 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
        },
        body: JSON.stringify({
            query,
            variables,
        }),
    });

    if (!response.ok) {
        throw new Error(`Request failed with status ${response.status}.`);
    }

    const payload = await response.json() as GraphqlResponse<T>;

    if (payload.errors?.length) {
        throw new Error(payload.errors[0]?.message || 'GraphQL returned an error.');
    }

    if (!payload.data) {
        throw new Error('GraphQL returned no data.');
    }

    return payload.data;
}

export async function loadGraphqlFrontendEnvelope(options: GraphqlFrontendTransportOptions): Promise<FrontendFormEnvelope> {
    const data = await requestGraphql<{
        formieClientForm?: FrontendFormEnvelope | null;
    }>(
        options,
        `
            query ClientForm($handle: String!, $siteId: Int) {
                formieClientForm(handle: $handle, siteId: $siteId) {
                    schemaVersion
                    definition
                    session {
                        ${FRONTEND_SESSION_SELECTION}
                    }
                }
            }
        `,
        {
            handle: options.formHandle,
            siteId: options.siteId,
        },
    );

    if (!data.formieClientForm) {
        throw new Error('No client form definition was returned.');
    }

    return data.formieClientForm;
}

export function createGraphqlFrontendTransport(options: GraphqlFrontendTransportOptions): FrontendTransport {
    return {
        async submit({ definition, session, values, action }): Promise<FrontendSubmitResult> {
            const serializedValues = await serializeTransportFieldValues(definition, values);

            const data = await requestGraphql<{
                submitFormieClientForm?: FrontendSubmitResult | null;
            }>(
                options,
                `
                    mutation SubmitFormieClientForm(
                        $input: FormieClientSubmitInput!
                    ) {
                        submitFormieClientForm(input: $input) {
                            ${FRONTEND_SUBMIT_RESULT_SELECTION}
                        }
                    }
                `,
                {
                    input: {
                        handle: options.formHandle,
                        siteId: options.siteId,
                        action,
                        session,
                        values: serializedValues,
                    },
                },
            );

            if (!data.submitFormieClientForm) {
                throw new Error('No client submit result was returned.');
            }

            return data.submitFormieClientForm;
        },
        async refreshSession({ session }): Promise<FrontendFormSession> {
            const data = await requestGraphql<{
                refreshFormieClientSession?: FrontendFormSession | null;
            }>(
                options,
                `
                    mutation RefreshFormieClientSession(
                        $input: FormieClientSessionRefreshInput!
                    ) {
                        refreshFormieClientSession(input: $input) {
                            ${FRONTEND_SESSION_SELECTION}
                        }
                    }
                `,
                {
                    input: {
                        handle: options.formHandle,
                        siteId: options.siteId,
                        session,
                    },
                },
            );

            if (!data.refreshFormieClientSession) {
                throw new Error('No client session was returned.');
            }

            return data.refreshFormieClientSession;
        },
        async setPage({ definition, session, values, currentPageId, targetPageId }): Promise<FrontendFormSession> {
            const serializedValues = await serializeTransportFieldValues(definition, values);

            const data = await requestGraphql<{
                setFormieClientPage?: FrontendFormSession | null;
            }>(
                options,
                `
                    mutation SetFormieClientPage(
                        $input: FormieClientSetPageInput!
                    ) {
                        setFormieClientPage(input: $input) {
                            ${FRONTEND_SESSION_SELECTION}
                        }
                    }
                `,
                {
                    input: {
                        handle: options.formHandle,
                        siteId: options.siteId,
                        currentPageId,
                        targetPageId,
                        session,
                        values: serializedValues,
                    },
                },
            );

            if (!data.setFormieClientPage) {
                throw new Error('No client page session was returned.');
            }

            return data.setFormieClientPage;
        },
    };
}
