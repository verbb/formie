export type RequestJsonOptions = {
    method?: string;
    body?: BodyInit | null;
    headers?: Record<string, string>;
    signal?: AbortSignal;
};
export declare function requestJson<T>(url: string | URL, options?: RequestJsonOptions): Promise<T>;
export declare function requestText(url: string | URL, options?: RequestJsonOptions): Promise<string>;
//# sourceMappingURL=http.d.ts.map