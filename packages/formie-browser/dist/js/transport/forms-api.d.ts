import { type BrowserRequestOptions } from '@verbb/formie-core';
import type { FormEndpointPayload, FormSubmitResult } from '#contracts/schema';
export declare function requestRender(endpoint: string, handle: string, renderOptions?: Record<string, unknown>, requestOptions?: BrowserRequestOptions): Promise<FormEndpointPayload>;
export declare function requestGraphqlRender(endpoint: string, handle: string, renderOptions?: Record<string, unknown>, requestOptions?: BrowserRequestOptions): Promise<FormEndpointPayload>;
export declare function requestRefreshTokens(endpoint: string, handle: string, renderId?: string, requestOptions?: BrowserRequestOptions, requestToken?: string): Promise<FormEndpointPayload['refreshTokens']>;
export declare function requestSetPage(url: string, form?: HTMLFormElement, pageId?: string): Promise<{
    success?: boolean;
    pageId?: string | number;
}>;
export declare function clearSubmissionOnUnload(endpoint: string, form: HTMLFormElement): void;
export declare function submitForm(form: HTMLFormElement, formData: FormData): Promise<FormSubmitResult>;
//# sourceMappingURL=forms-api.d.ts.map