type LoadExternalScriptOptions = {
    id: string;
    src: string;
    async?: boolean;
    defer?: boolean;
};
export declare function ensureGlobal<T = unknown>(globalName: string, timeoutMs?: number): Promise<T>;
export declare function loadExternalScript({ id, src, async, defer, }: LoadExternalScriptOptions): Promise<HTMLScriptElement>;
export declare function loadScriptAndEnsureGlobal<T = unknown>(globalName: string, options: LoadExternalScriptOptions & {
    timeoutMs?: number;
}): Promise<T>;
export {};
//# sourceMappingURL=scripts.d.ts.map