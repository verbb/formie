import { useRef, useSyncExternalStore } from 'react';

const identity = (value) => { return value; };

export const useSyncExternalStoreWithSelector = (
    subscribe,
    getSnapshot,
    getServerSnapshot,
    selector = identity,
    isEqual = Object.is,
) => {
    const instRef = useRef({
        hasValue: false,
        selection: undefined,
    });

    const getSelection = () => {
        const nextSnapshot = getSnapshot();
        const nextSelection = selector(nextSnapshot);
        const inst = instRef.current;

        if (inst.hasValue && isEqual(inst.selection, nextSelection)) {
            return inst.selection;
        }

        inst.hasValue = true;
        inst.selection = nextSelection;
        return nextSelection;
    };

    const getServerSelection = getServerSnapshot
        ? () => { return selector(getServerSnapshot()); }
        : getSelection;

    return useSyncExternalStore(subscribe, getSelection, getServerSelection);
};
