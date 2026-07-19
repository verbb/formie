import { useEffect } from 'react';

/**
 * Legacy no-op for callers that still import this hook.
 *
 * Tabbed field/sub-field/page-button modals now match v1 ModalTabs: each
 * `pk-tab-panel` owns `overflow-y`, so switching tabs does not share a body
 * `scrollTop`. Prefer removing call sites over extending this hook.
 *
 * @param {React.RefObject<HTMLElement | null>} _rootRef
 * @param {boolean} [_enabled=true]
 */
function useResetDialogBodyScrollOnTabChange(_rootRef, _enabled = true) {
    useEffect(() => {
        // Intentionally empty — panel-owned scroll replaced sticky body scroll.
    }, []);
}

export { useResetDialogBodyScrollOnTabChange };
