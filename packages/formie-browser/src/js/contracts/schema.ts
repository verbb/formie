import type { BrowserModuleManifest } from '@verbb/formie-core';
import type { FormAction, SubmitStage } from '#contracts/common';
import type { ThemeClassMap } from '#contracts/theme';

export type FormRefreshTokensPayload = {
    csrf?: {
        param: string;
        token: string;
    };
    requestToken?: string;
    renderId?: string;
    uploadCreateToken?: string;
    captchas?: Record<string, { sessionKey: string; value?: string }>;
    meta?: Record<string, unknown>;
};

export type FormRedirect = {
    url: string;
    target?: 'same-tab' | 'new-tab';
};

export type FormClientEvent = {
    event: string;
    payload: Record<string, string>;
};

export type FormSubmitResult = {
    ok: boolean;
    outcome?: string;
    version?: number | null;
    submissionUid?: string | null;
    errors?: unknown;
    session?: unknown;
    completion?: Record<string, unknown> | null;
    action?: FormAction;
    stage?: SubmitStage;
    code?: string;
    message?: string;
    keepSubmitLoading?: boolean;
    fieldErrors?: Record<string, string[]>;
    formErrors?: string[];
    nextPage?: { id: string } | null;
    redirect?: FormRedirect | null;
    submitData?: unknown[];
    clientEvents?: FormClientEvent[];
    meta?: Record<string, unknown>;
};

export type FormEndpointPayload = {
    html?: string;
    theme?: ThemeClassMap;
    modules?: BrowserModuleManifest;
    refreshTokens?: FormRefreshTokensPayload;
};

export type { BrowserModuleEntry, BrowserModuleManifest } from '@verbb/formie-core';
export type FormModuleTarget = import('@verbb/formie-core').BrowserModuleTarget;
export type FormModuleTargetType = FormModuleTarget['targetType'];
