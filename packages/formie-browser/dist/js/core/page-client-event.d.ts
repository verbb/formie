export type ResolvedClientEvent = {
    event: string;
    payload: Record<string, string>;
};
export declare function resolveSubmittedPageId(form: HTMLFormElement): string | null;
export declare function dispatchResolvedClientEvents(form: HTMLFormElement, events: ResolvedClientEvent[]): void;
export declare function dispatchPendingClientEventsFromForm(form: HTMLFormElement): void;
/**
 * Legacy static page attribute dispatch. Prefer server-resolved `clientEvents`
 * from the submit response when available.
 */
export declare function dispatchPageClientEventForSubmit(form: HTMLFormElement, action: string): void;
//# sourceMappingURL=page-client-event.d.ts.map