import { useEffect, useRef } from 'react';

export const CP_MAIN_FORM_SELECTOR = '#main-form';

const SAVE_TRIGGER_SELECTOR = 'input[type="submit"], button[type="submit"], a.formsubmit, button.formsubmit';

const syncPayloadInput = (inputId, payload) => {
    const input = document.getElementById(inputId);

    if (!input) {
        return null;
    }

    const nextValue = JSON.stringify(payload ?? {});

    if (input.value !== nextValue) {
        input.value = nextValue;
    }

    return nextValue;
};

const resetCpFormUnloadState = (form) => {
    if (typeof jQuery === 'undefined') {
        return;
    }

    const $form = jQuery(form);
    const serializer = $form.data('serializer') || (() => $form.serialize());

    $form.data('initialSerializedValue', serializer());
};

export const useCpFormPayloadSync = ({
    inputId,
    payload,
    enabled = true,
    onBeforeSubmit,
}) => {
    const payloadRef = useRef(payload);
    const onBeforeSubmitRef = useRef(onBeforeSubmit);
    const hasResetInitialUnloadStateRef = useRef(false);

    payloadRef.current = payload;
    onBeforeSubmitRef.current = onBeforeSubmit;

    useEffect(() => {
        if (!enabled || !inputId) {
            return;
        }

        const nextValue = syncPayloadInput(inputId, payload);

        if (nextValue === null) {
            return;
        }

        if (hasResetInitialUnloadStateRef.current) {
            return;
        }

        const form = document.querySelector(CP_MAIN_FORM_SELECTOR);

        if (!form) {
            return;
        }

        resetCpFormUnloadState(form);
        hasResetInitialUnloadStateRef.current = true;
    }, [enabled, inputId, payload]);

    useEffect(() => {
        if (!enabled || !inputId) {
            return undefined;
        }

        const sync = () => {
            if (!document.getElementById(inputId)) {
                return;
            }

            onBeforeSubmitRef.current?.();
            syncPayloadInput(inputId, payloadRef.current);
        };

        const handleClick = (event) => {
            const trigger = event.target.closest(SAVE_TRIGGER_SELECTOR);

            if (!trigger) {
                return;
            }

            sync();
        };

        document.addEventListener('submit', sync, { capture: true });
        document.addEventListener('click', handleClick, { capture: true });

        return () => {
            document.removeEventListener('submit', sync, { capture: true });
            document.removeEventListener('click', handleClick, { capture: true });
        };
    }, [enabled, inputId]);
};
