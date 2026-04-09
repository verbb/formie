import { Extension } from '@tiptap/core';
import { Plugin, PluginKey } from 'prosemirror-state';

/**
 * Strips rich formatting on paste by inserting clipboard text/plain only.
 * Based on https://github.com/unicscode/tiptap-clean-paste (MIT).
 */
export default Extension.create({
    name: 'cleanPaste',

    priority: 2000,

    addOptions() {
        return {
            // ASCII printable, Latin/Greek blocks, Unicode letters, whitespace
            regexPattern: /[^\x20-\x7E\u00A0-\u02AF\u0370-\u03FF\p{Letter}\s]/gu,
        };
    },

    addProseMirrorPlugins() {
        const { regexPattern } = this.options;

        return [
            new Plugin({
                key: new PluginKey('cleanPaste'),
                props: {
                    handlePaste(view, event) {
                        const { clipboardData } = event;
                        if (!clipboardData) {
                            return false;
                        }

                        const text = clipboardData.getData('text/plain');
                        if (!text) {
                            return false;
                        }

                        const cleanText = text.replace(regexPattern, '');

                        event.preventDefault();

                        const { state } = view;
                        const { from, to } = state.selection;
                        const newText = state.schema.text(cleanText);
                        const tr = state.tr.replaceWith(from, to, newText);
                        view.dispatch(tr);

                        return true;
                    },
                },
            }),
        ];
    },
});
