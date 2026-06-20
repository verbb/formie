import type { FormAction, SubmitStage } from '#contracts/common';
import type { ThemeClassMap } from '#contracts/theme';
export type FormDefinitionField = {
    id: string;
    uid?: string;
    handle: string;
    type: string;
    label?: string;
    required?: boolean;
    settings?: Record<string, unknown>;
    children?: FormDefinitionField[];
};
export type FormDefinitionPage = {
    id: string;
    name?: string;
    fields: FormDefinitionField[];
    settings?: Record<string, unknown>;
};
export type FormDefinitionPayload = {
    formId?: string;
    handle?: string;
    pages?: FormDefinitionPage[];
    settings?: Record<string, unknown>;
    theme?: ThemeClassMap;
};
export type FormRefreshTokensPayload = {
    csrf?: {
        param: string;
        token: string;
    };
    requestToken?: string;
    renderId?: string;
    captchas?: Record<string, {
        sessionKey: string;
        value?: string;
    }>;
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
    action?: FormAction;
    stage?: SubmitStage;
    code?: string;
    message?: string;
    keepSubmitLoading?: boolean;
    fieldErrors?: Record<string, string[]>;
    pageFieldErrors?: Record<string, Record<string, string[]>>;
    formErrors?: string[];
    nextPage?: {
        id: string;
    } | null;
    redirect?: FormRedirect | null;
    submitData?: unknown[];
    clientEvents?: FormClientEvent[];
    meta?: Record<string, unknown>;
};
export type FormEndpointPayload = {
    html?: string;
    theme?: ThemeClassMap;
    modules?: FormModuleManifest[];
    refreshTokens?: FormRefreshTokensPayload;
};
export type FormModuleTargetType = 'form' | 'field' | 'page' | 'button' | 'selector';
export type FormModuleTarget = {
    targetType: FormModuleTargetType;
    targetId: string;
};
export type FormModuleManifest = {
    id: string;
    src?: string;
    type: string;
    targets?: FormModuleTarget[];
    renderTargets?: string[];
    config?: Record<string, unknown>;
};
//# sourceMappingURL=schema.d.ts.map