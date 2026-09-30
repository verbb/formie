import { browserRequest, type BrowserRequestOptions } from '@verbb/formie-core';
import type { FormEndpointPayload, FormSubmitResult } from '#contracts/schema';
import { appendFormCsrfToFormData } from '#utils/csrf';
import { createDebug } from '#utils/debug';
import { request, requestJson } from '#utils/http';

const debug = createDebug('general', 'transport');

type GraphqlResponse<T> = {
    data?: T;
    errors?: Array<{ message?: string }>;
};

type GraphqlFormQueryResult = {
    formieHtmlForm?: FormEndpointPayload | null;
};

function toServerRenderPayloadInput(renderOptions: Record<string, unknown>): Record<string, unknown> {
    const input: Record<string, unknown> = {};

    ['theme', 'themeConfig', 'locale', 'siteId'].forEach((key) => {
        if (renderOptions[key] !== undefined) {
            input[key] = renderOptions[key];
        }
    });

    return input;
}

function normalizePayload(payload: Record<string, unknown>, fallbackFormError?: string): FormSubmitResult {
    const success = payload.success === true;
    const completion = payload.completion && typeof payload.completion === 'object'
        ? payload.completion as NonNullable<FormSubmitResult['completion']>
        : null;
    const payment = payload.payment && typeof payload.payment === 'object' ? payload.payment as FormSubmitResult['payment'] : null;
    const keepSubmitLoading = ['requiresAction', 'pending', 'unknown'].includes(payment?.status || '');
    const errors = payload.errors;
    const fieldErrorsFlat: Record<string, string[]> = Object.fromEntries(Object.entries(errors && typeof errors === 'object' ? errors : {}).map(([path, messages]) => [path, Array.isArray(messages) ? messages.filter((message): message is string => typeof message === 'string') : []]));
    const formErrors = fieldErrorsFlat.form || [];
    const fieldErrors: Record<string, string[]> = {};

    Object.entries(fieldErrorsFlat).forEach(([key, value]) => {
        if (key === 'form') {
            return;
        }

        // The server normalises Yii keys; retain the entire control value path.
        fieldErrors[key] = value;
    });

    const resolvedFormErrors = !success && formErrors.length === 0 && Object.keys(fieldErrors).length > 0
        ? [fallbackFormError || 'Submission failed.']
        : formErrors;

    const isTransientPendingResult = !success
        && keepSubmitLoading
        && resolvedFormErrors.length === 0
        && Object.keys(fieldErrors).length === 0;

    const result: FormSubmitResult = {
        ok: success,
        outcome: typeof payload.outcome === 'string' ? payload.outcome : undefined,
        version: typeof payload.version === 'number' ? payload.version : null,
        submissionUid: typeof payload.submissionUid === 'string' ? payload.submissionUid : null,
        errors: payload.errors,
        session: payload.session,
        completion,
        action: (payload.submitAction === 'back' || payload.submitAction === 'save' || payload.submitAction === 'submit')
            ? payload.submitAction
            : undefined,
        message: (
            completion?.message
            || payload.successMessage
            || payload.submitActionMessage
            || (success ? 'Submission completed.' : (isTransientPendingResult ? '' : (resolvedFormErrors[0] || 'Submission failed.')))
        ) as string,
        code: success ? undefined : String(payload.code || 'SUBMIT_ERROR'),
        keepSubmitLoading,
        payment,
        fieldErrors: Object.keys(fieldErrors).length ? fieldErrors : undefined,
        formErrors: resolvedFormErrors.length ? resolvedFormErrors : undefined,
        nextPage: payload.nextPageId
            ? {
                id: String(payload.nextPageId),
            }
            : null,
        redirect: payment?.status === 'requiresAction' && payment.action?.type === 'redirect' && payment.action.url
            ? {
                url: payment.action.url,
                target: 'same-tab',
            }
            : completion?.url
            ? {
                url: completion.url,
                target: completion.target,
            }
            : payload.redirectUrl
            ? {
                url: String(payload.redirectUrl),
                target: payload.redirectTarget === 'new-tab' ? 'new-tab' : 'same-tab',
            }
            : null,
        submitData: Array.isArray(payload.submitData) ? payload.submitData : undefined,
        clientEvents: Array.isArray(payload.clientEvents) ? payload.clientEvents as FormSubmitResult['clientEvents'] : undefined,
        meta: payload,
    };

    return result;
}

export async function requestRender(endpoint: string, handle: string, renderOptions: Record<string, unknown> = {}, requestOptions: BrowserRequestOptions = {}): Promise<FormEndpointPayload> {
    const body = JSON.stringify({
        handle,
        renderOptions,
    });

    debug.log('requestRender start.', { endpoint, handle });
    const result = await requestJson<FormEndpointPayload>(endpoint, {
        ...requestOptions,
        method: 'POST',
        body,
        headers: {
            'Content-Type': 'application/json',
        },
    });
    debug.log('requestRender complete.', {
        hasHtml: !!result.html,
    });
    return result;
}

export async function requestGraphqlRender(endpoint: string, handle: string, renderOptions: Record<string, unknown> = {}, requestOptions: BrowserRequestOptions = {}): Promise<FormEndpointPayload> {
    const query = `
query FormieHtmlForm($handle: String!, $input: ServerRenderPayloadInput) {
  formieHtmlForm(handle: $handle, input: $input) {
    html
  }
}`;
    const body = JSON.stringify({
        query,
        variables: {
            handle,
            input: toServerRenderPayloadInput(renderOptions),
        },
    });

    debug.log('requestGraphqlRender start.', { endpoint, handle });
    const result = await requestJson<GraphqlResponse<GraphqlFormQueryResult>>(endpoint, {
        ...requestOptions,
        method: 'POST',
        body,
        headers: {
            'Content-Type': 'application/json',
        },
    });

    if (Array.isArray(result.errors) && result.errors.length > 0) {
        throw new Error(result.errors.map((error) => error.message || 'Unknown GraphQL error').join('; '));
    }

    if (!result.data?.formieHtmlForm) {
        throw new Error(`Form not found for handle "${handle}".`);
    }

    const payload = result.data.formieHtmlForm;
    debug.log('requestGraphqlRender complete.', {
        hasHtml: !!payload.html,
    });

    return payload;
}

export async function requestRefreshTokens(endpoint: string, handle: string, renderId?: string, requestOptions: BrowserRequestOptions = {}, requestToken?: string): Promise<FormEndpointPayload['refreshTokens']> {
    const url = new URL(endpoint, window.location.origin);
    url.searchParams.set('handle', handle);

    if (renderId) {
        url.searchParams.set('renderId', renderId);
    }

    if (requestToken) url.searchParams.set('requestToken', requestToken);

    debug.log('requestRefreshTokens start.', {
        endpoint: url.toString(),
        handle,
        hasRenderId: !!renderId,
    });
    const response = await requestJson<FormEndpointPayload>(url.toString(), requestOptions);
    debug.log('requestRefreshTokens complete.', {
        hasRefreshTokens: !!response.refreshTokens,
    });

    return response.refreshTokens || response as FormEndpointPayload['refreshTokens'];
}

export async function requestSetPage(url: string, form?: HTMLFormElement, pageId?: string): Promise<{
    success?: boolean;
    pageId?: string | number;
    errors?: Record<string, string[]>;
}> {
    const requestUrl = new URL(url, window.location.origin);
    const body = new FormData();

    if (pageId) {
        body.append('pageId', pageId);
    }

    if (form) {
        // Page changes must carry the same continuity identifiers the submit flow
        // uses so the backend can persist draft/session state for multipage forms.
        const inputNames = ['handle', 'renderId', 'draftContextToken', 'draftContext', 'progressId', 'requestToken', 'expectedVersion'];

        inputNames.forEach((name) => {
            const input = form.querySelector(`input[name="${name}"]`) as HTMLInputElement | null;
            const value = input?.value?.trim();

            if (value) {
                body.append(name, value);
            }
        });

        appendFormCsrfToFormData(body, form);
        for (const [name, value] of new FormData(form)) {
            if (name.startsWith('fields[')) body.append(name, value);
        }
    }

    debug.log('requestSetPage start.', {
        requestUrl: requestUrl.toString(),
        pageId: pageId || null,
    });
    const response = await request(requestUrl.toString(), {
        method: 'POST', body, profile: form?.dataset.formieRequestProfile as BrowserRequestOptions['profile'],
    });
    const result = await response.json() as {
        success?: boolean;
        pageId?: string | number;
        errors?: Record<string, string[]>;
        session?: { version: number; tokens?: { request?: string } };
    };
    if (form && result.session) {
        const session = result.session;
        const version = form.querySelector<HTMLInputElement>('input[name="expectedVersion"]');
        const token = form.querySelector<HTMLInputElement>('input[name="requestToken"]');
        if (version) version.value = String(session.version);
        if (token && session.tokens?.request) token.value = session.tokens.request;
    }
    debug.log('requestSetPage complete.', result);
    return result;
}

export function clearSubmissionOnUnload(endpoint: string, form: HTMLFormElement): void {
    const requestUrl = new URL(endpoint, window.location.origin);
    const body = new FormData();
    const inputNames = ['handle', 'renderId', 'draftContextToken', 'draftContext'];

    inputNames.forEach((name) => {
        const input = form.querySelector(`input[name="${name}"]`) as HTMLInputElement | null;
        const value = input?.value?.trim();

        if (value) {
            body.append(name, value);
        }
    });

    appendFormCsrfToFormData(body, form);

    debug.log('clearSubmissionOnUnload start.', {
        requestUrl: requestUrl.toString(),
    });

    try {
        if (form.dataset.formieRequestProfile !== 'cross-origin-public' && typeof navigator.sendBeacon === 'function' && navigator.sendBeacon(requestUrl.toString(), body)) {
            return;
        }
    } catch (_error) {
        // Fall back to keepalive fetch below when Beacon is unavailable or rejects the payload.
    }

    void browserRequest(requestUrl.toString(), {
        method: 'POST',
        body,
        keepalive: true,
        headers: {
            Accept: 'application/json',
        },
    }, { profile: form.dataset.formieRequestProfile as BrowserRequestOptions['profile'] });
}

export async function submitForm(form: HTMLFormElement, formData: FormData): Promise<FormSubmitResult> {
    const method = (form.getAttribute('method') || 'POST').toUpperCase();
    const action = form.getAttribute('action') || window.location.href;
    const fallbackFormError = form.dataset.formieErrorMessage?.trim() || 'Submission failed.';

    debug.log('submitForm start.', {
        method,
        action,
        submitAction: formData.get('submitAction'),
    });
    const response = await browserRequest(action, {
        method,
        body: formData,
        headers: {
            Accept: 'application/json',
        },
    }, { profile: form.dataset.formieRequestProfile as BrowserRequestOptions['profile'] });

    const contentType = response.headers.get('content-type') || '';

    // Non-JSON responses still count as successful submits in traditional redirect
    // flows; only non-OK responses are treated as transport errors here.
    if (!contentType.includes('application/json')) {
        if (!response.ok) {
            debug.warn('submitForm non-JSON HTTP error.', {
                status: response.status,
                contentType,
            });
            return {
                ok: false,
                code: 'HTTP_ERROR',
                message: `Request failed (${response.status}).`,
                formErrors: [`Request failed (${response.status}).`],
            };
        }

        debug.log('submitForm non-JSON success response.', {
            status: response.status,
            contentType,
        });
        return {
            ok: true,
            message: 'Submission completed.',
        };
    }

    const payload = await response.json() as Record<string, unknown>;
    const normalized = normalizePayload(payload, fallbackFormError);
    debug.log('submitForm JSON response normalized.', {
        ok: normalized.ok,
        code: normalized.code,
        hasRedirect: !!normalized.redirect?.url,
        hasSubmitData: Array.isArray(normalized.submitData) && normalized.submitData.length > 0,
    });
    return normalized;
}
