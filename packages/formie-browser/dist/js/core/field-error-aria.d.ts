/**
 * Field-error ARIA helpers.
 *
 * Pair `aria-errormessage` with `aria-describedby` pointing at the same error
 * message id. `aria-errormessage` alone is inconsistently announced (notably
 * iOS Safari + VoiceOver); Adrian Roselli's cross-AT testing found the pairing
 * more reliable without clobbering instruction ids already on describedby.
 */
export declare function appendDescribedBy(input: HTMLElement, describedById: string): void;
export declare function removeDescribedBy(input: HTMLElement, describedById: string): void;
/**
 * Drop describedby ids whose targets no longer exist (Formie removes error
 * message nodes from the DOM when clearing). Keeps instruction/other ids.
 */
export declare function pruneMissingDescribedBy(input: HTMLElement, doc?: Document): void;
export declare function setErrorMessageReference(input: HTMLElement, errorMessageId: string): void;
export declare function clearErrorMessageReference(input: HTMLElement, errorMessageId: string): void;
/**
 * Clear errormessage refs and prune any describedby ids that no longer resolve
 * (covers message nodes removed from the DOM and legacy container ids).
 */
export declare function clearFieldErrorAria(input: HTMLElement, errorMessageIds?: string[]): void;
//# sourceMappingURL=field-error-aria.d.ts.map