import { FormieValidator } from '#validation/validator';
export type CraftFormsApp = {
    get: (form: HTMLFormElement) => FormieValidator | null;
    rescan: () => void;
    destroy: () => void;
};
/** Enhance Craft action forms without taking ownership of their requests. */
export declare function craftForms({ root, observe }?: {
    root?: ParentNode;
    observe?: boolean;
}): CraftFormsApp;
//# sourceMappingURL=craft-forms.d.ts.map