import { Plugin, PluginKey } from 'prosemirror-state';
import Link from '@tiptap/extension-link';

export default Link.extend({
    addProseMirrorPlugins() {
        return [
            new Plugin({
                key: new PluginKey('handleClick'),
                props: {
                    handleClick: (view, pos, event) => {
                        const attrs = this.editor.getAttributes('link');

                        // Raise a custom event so we can action this elsewhere. Notably, opening
                        // up a menu bubble in a Vue component, for max convenience
                        if (attrs.href && event.target instanceof HTMLAnchorElement) {
                            // Give it a second to resolve the cursor before raising the event.
                            // Otherwise tippy can freak out with positioning.
                            setTimeout(() => {
                                this.editor.emit('fui:link-clicked');
                            }, 50);

                            return true;
                        }
                    },
                },
            }),
        ];
    },
});
