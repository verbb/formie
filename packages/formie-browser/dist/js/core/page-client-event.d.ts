export declare function resolveSubmittedPageId(form: HTMLFormElement): string | null;
/**
 * When the builder enables JavaScript events for a page, the theme emits
 * `data-formie-client-event` on that page's section. On each successful
 * **submit** (not back/save), push the configured key/value object to
 * `window.dataLayer` (when present) and dispatch `formie:client-event`.
 */
export declare function dispatchPageClientEventForSubmit(form: HTMLFormElement, action: string): void;
//# sourceMappingURL=page-client-event.d.ts.map