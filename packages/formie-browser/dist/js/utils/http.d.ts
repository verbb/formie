import { type BrowserRequestOptions } from '@verbb/formie-core';
export type RequestJsonOptions = BrowserRequestOptions & {
    method?: string;
    body?: BodyInit | null;
    headers?: Record<string, string>;
    signal?: AbortSignal;
};
export declare function request(url: string | URL, options?: RequestJsonOptions): Promise<Response>;
export declare function requestJson<T>(url: string | URL, options?: RequestJsonOptions): Promise<T>;
export declare function requestText(url: string | URL, options?: RequestJsonOptions): Promise<string>;
//# sourceMappingURL=http.d.ts.map