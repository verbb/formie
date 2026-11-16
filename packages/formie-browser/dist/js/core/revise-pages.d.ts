import type { FormAction } from '#contracts/common';
import type { FormieValidator } from '#validation/validator';
export declare function isReviseForm(form: HTMLFormElement): boolean;
/**
 * Changes page for a page button or Back on a revise form. Returns false when the request should be sent
 * as usual, which is the submit button on the last page.
 */
export declare function handleRevisePageAction(form: HTMLFormElement, action: FormAction, validator: FormieValidator | null, validate: boolean): boolean;
/**
 * Changes page for a page tab on a revise form. Moving forward checks the current page first.
 */
export declare function handleRevisePageTab(form: HTMLFormElement, pageId: string, validator: FormieValidator | null, validate: boolean): void;
/**
 * Shows the first page with an error when the visible page has none, such as after the server rejects an
 * answer on an earlier page.
 */
export declare function showRevisePageWithErrors(form: HTMLFormElement): void;
//# sourceMappingURL=revise-pages.d.ts.map