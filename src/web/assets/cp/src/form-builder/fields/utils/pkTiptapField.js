import { useEffect, useState } from 'react';

/** Normalize Lit/React TipTap change handlers to a stable string value. */
export function readPkTiptapChangeValue(eventOrValue) {
    if (typeof eventOrValue === 'string') {
        return eventOrValue;
    }

    if (Array.isArray(eventOrValue)) {
        return JSON.stringify(eventOrValue);
    }

    if (eventOrValue?.detail && 'value' in eventOrValue.detail) {
        const detailValue = eventOrValue.detail.value;
        if (typeof detailValue === 'string') {
            return detailValue;
        }
        if (Array.isArray(detailValue)) {
            return JSON.stringify(detailValue);
        }
        if (detailValue == null) {
            return '';
        }
        return String(detailValue);
    }

    const targetValue = eventOrValue?.target?.value;
    if (typeof targetValue === 'string') {
        return targetValue;
    }
    if (Array.isArray(targetValue)) {
        return JSON.stringify(targetValue);
    }

    return '';
}

/** Stable string for comparing TipTap controlled values (arrays or JSON strings). */
export function normalizePkTiptapStoreValue(value) {
    if (typeof value === 'string') {
        return value;
    }
    if (Array.isArray(value)) {
        return JSON.stringify(value);
    }
    if (value == null) {
        return '';
    }
    return String(value);
}

/**
 * Attach to a `<pk-tiptap-*>` host ref after the WC mounts its TipTap Editor.
 * Formie VariableDropdown needs the live editor instance (kit no longer ships picker UI).
 */
export function usePkTiptapEditor(hostRef) {
    const [editor, setEditor] = useState(null);

    useEffect(() => {
        let cancelled = false;
        let frameId = 0;

        const sync = () => {
            if (cancelled) {
                return;
            }

            const host = hostRef.current;
            if (!host) {
                frameId = requestAnimationFrame(sync);
                return;
            }

            // Input exposes editorInstance; editor facade exposes .editor.
            const next = host.editorInstance ?? host.editor ?? null;
            setEditor((current) => (current === next ? current : next));

            if (!next) {
                frameId = requestAnimationFrame(sync);
            }
        };

        sync();

        return () => {
            cancelled = true;
            if (frameId) {
                cancelAnimationFrame(frameId);
            }
        };
    }, [hostRef]);

    return editor;
}
