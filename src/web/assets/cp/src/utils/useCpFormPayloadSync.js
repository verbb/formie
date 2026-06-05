import { useEffect, useRef } from 'react';

export const CP_MAIN_FORM_SELECTOR = '#main-form';

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
}) => {
    const payloadRef = useRef(payload);
    const hasResetInitialUnloadStateRef = useRef(false);

    payloadRef.current = payload;

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

        const form = document.querySelector(CP_MAIN_FORM_SELECTOR);

        if (!form) {
            return undefined;
        }

        const sync = () => {
            syncPayloadInput(inputId, payloadRef.current);
        };

        const handleClick = (event) => {
            const trigger = event.target.closest('input[type="submit"], button[type="submit"]');

            if (!trigger || !form.contains(trigger)) {
                return;
            }

            sync();
        };

        form.addEventListener('submit', sync, { capture: true });
        form.addEventListener('click', handleClick, { capture: true });

        return () => {
            form.removeEventListener('submit', sync, { capture: true });
            form.removeEventListener('click', handleClick, { capture: true });
        };
    }, [enabled, inputId]);
};
