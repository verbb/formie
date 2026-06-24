import type { FormMountOptions, FormieClient, FormieFormInstance } from '#contracts/client';
import type { FormSubmitResult } from '#contracts/schema';
export type FormieElementTarget = string | Element | Iterable<Element>;
export type FormieEvent = {
    name: string;
    payload: unknown;
};
export type FormieOptions = Omit<Partial<FormMountOptions>, 'mode'> & {
    element: FormieElementTarget;
    observe?: boolean;
    allowEmpty?: boolean;
    client?: FormieClient;
    onReady?: (instance: FormieFormInstance) => void;
    onResult?: (result: FormSubmitResult, instance: FormieFormInstance) => void;
    onSuccess?: (result: FormSubmitResult, instance: FormieFormInstance) => void;
    onError?: (result: FormSubmitResult, instance: FormieFormInstance) => void;
    onEvent?: (event: FormieEvent, instance: FormieFormInstance) => void;
};
export type FormieApp = {
    client: FormieClient;
    readonly instances: FormieFormInstance[];
    get: (target: string | Element) => FormieFormInstance | null;
    rescan: () => Promise<FormieFormInstance[]>;
    destroy: () => Promise<void>;
};
export declare function formie(options: FormieOptions): Promise<FormieApp>;
//# sourceMappingURL=formie.d.ts.map