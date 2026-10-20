import type { FormieFormInstance } from '#contracts/client';
export declare function bindFormEventInstance(root: Element, form: HTMLFormElement | null, instance: FormieFormInstance): () => void;
export declare function withFormEventInstance<T extends object>(target: Element, detail: T): T & {
    instance?: FormieFormInstance;
};
export declare function dispatchFormieDomEvent(target: Element, eventName: string, detail: unknown): void;
//# sourceMappingURL=dom-events.d.ts.map