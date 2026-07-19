import { useEffect } from 'react';

/**
 * Formie CP keyboard shortcut handler. Kept in the app (not plugin-kit) because the
 * builder-specific save/cut/copy/paste/undo/redo bindings are Formie CP behavior.
 */
export const useKeyboardShortcuts = ({
    onSave,
    onCut,
    onCopy,
    onPaste,
    onUndo,
    onRedo,
    onSelectAll,
    onEscape,
    onEnter,
    onDelete,
} = {}) => {
    useEffect(() => {
        const isInputElement = (element) => {
            if (!element) {
                return false;
            }

            const tagName = element.tagName?.toLowerCase();
            const isInput = tagName === 'input' || tagName === 'textarea';
            const isContentEditable = element.contentEditable === 'true';

            return isInput || isContentEditable;
        };

        const handleKeyDown = (event) => {
            if ((event.metaKey || event.ctrlKey) && event.key === 's') {
                event.preventDefault();
                onSave?.();
            }

            if ((event.metaKey || event.ctrlKey) && event.key === 'x') {
                onCut?.();
            }

            if ((event.metaKey || event.ctrlKey) && event.key === 'c') {
                onCopy?.();
            }

            if ((event.metaKey || event.ctrlKey) && event.key === 'v') {
                onPaste?.();
            }

            if ((event.metaKey || event.ctrlKey) && event.key === 'z' && !event.shiftKey) {
                event.preventDefault();
                onUndo?.();
            }

            if (((event.metaKey && event.shiftKey) || event.ctrlKey) && (event.key === 'z' || event.key === 'y')) {
                event.preventDefault();
                onRedo?.();
            }

            if ((event.metaKey || event.ctrlKey) && event.key === 'a') {
                onSelectAll?.();
            }

            if (event.key === 'Escape') {
                onEscape?.();
            }

            if (event.key === 'Enter' && !isInputElement(event.target)) {
                onEnter?.();
            }

            if (event.key === 'Delete' && !isInputElement(event.target)) {
                onDelete?.();
            }
        };

        document.addEventListener('keydown', handleKeyDown);

        return () => {
            document.removeEventListener('keydown', handleKeyDown);
        };
    }, [onSave, onCut, onCopy, onPaste, onUndo, onRedo, onSelectAll, onEscape, onEnter, onDelete]);
};
