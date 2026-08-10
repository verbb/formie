export type FormCsrfToken = {
    name: string;
    value: string;
};
/**
 * Resolve the Craft CSRF hidden input for a Formie form.
 *
 * Prefer Formie markers / declared param names so custom `csrfTokenName`
 * values in `config/general.php` keep working. Fall back to Craft's CP
 * global and the default token name for older or custom templates.
 */
export declare function getFormCsrfInput(form: ParentNode | null | undefined): HTMLInputElement | null;
export declare function getFormCsrfToken(form: ParentNode | null | undefined): FormCsrfToken | null;
export declare function appendFormCsrfToFormData(body: FormData, form: ParentNode | null | undefined): void;
export declare function applyFormCsrfToRecord(target: Record<string, string>, form: ParentNode | null | undefined): void;
export declare function isFormCsrfFieldName(name: string, form?: ParentNode | null): boolean;
//# sourceMappingURL=csrf.d.ts.map