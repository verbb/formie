export type FormUnloadWarningGuard = {
    captureBaseline: () => void;
    scheduleBaselineCapture: () => void;
    refreshDirtyState: () => boolean;
    destroy: () => void;
};
export declare function createFormUnloadWarningGuard(form: HTMLFormElement, options?: {
    shouldWarn?: () => boolean;
}): FormUnloadWarningGuard;
//# sourceMappingURL=unload-warning.d.ts.map