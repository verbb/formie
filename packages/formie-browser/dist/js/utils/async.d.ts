export declare function sleep(ms: number): Promise<void>;
export declare function waitFor<T>(callback: () => T | null | undefined | false, { timeoutMs, intervalMs, }?: {
    timeoutMs?: number;
    intervalMs?: number;
}): Promise<T>;
export declare function debounce<TArgs extends unknown[]>(callback: (...args: TArgs) => void, delayMs: number): (...args: TArgs) => void;
export declare function waitForElement(selector: string, root?: ParentNode): Promise<Element>;
//# sourceMappingURL=async.d.ts.map