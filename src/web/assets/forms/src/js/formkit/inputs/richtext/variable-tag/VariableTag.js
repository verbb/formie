import { Node, mergeAttributes } from '@tiptap/core';
import { VueNodeViewRenderer } from '@tiptap/vue-3';
import { Plugin, PluginKey, NodeSelection } from 'prosemirror-state';

import VariableTagView from './VariableTag.vue';

export default Node.create({
    name: 'variableTag',
    group: 'inline',
    inline: true,
    selectable: true,
    draggable: true,
    atom: true,

    addAttributes() {
        return {
            label: { default: null },
            value: { default: null },
        };
    },

    parseHTML() {
        return [
            {
                tag: 'variable-tag',
                getAttrs: (dom) => { return JSON.parse(dom.innerHTML); },
            },
        ];
    },

    renderHTML({ HTMLAttributes }) {
        return ['variable-tag', JSON.stringify(HTMLAttributes)];
    },

    addCommands() {
        return {
            setVariableTag: (options) => {
                return ({
                    tr, dispatch, view, state,
                }) => {
                    const { selection } = state;
                    const position = selection.$cursor ? selection.$cursor.pos : selection.$to.pos;
                    const node = this.type.create(options);
                    const transaction = state.tr.insert(position, node);

                    dispatch(transaction);
                };
            },
        };
    },

    addNodeView() {
        return VueNodeViewRenderer(VariableTagView);
    },

    addProseMirrorPlugins() {
        return [
            new Plugin({
                props: {
                    handleKeyDown: (view, event) => {
                        // Prevent _any_ key from clearing block. As soon as you start typing,
                        // and a block is focused, it'll blast the block away.
                        view.state.typing = true;
                    },

                    handlePaste: (view, event, slice) => {
                        // Prevent pasting overwriting block
                        view.state.pasting = true;
                    },
                },

                filterTransaction: (transaction, state) => {
                    let result = true;

                    // Check if our flags are set, and if the selected node is a `variableTag`
                    if (state.typing || state.pasting) {
                        transaction.mapping.maps.forEach((map) => {
                            map.forEach((oldStart, oldEnd, newStart, newEnd) => {
                                state.doc.nodesBetween(oldStart, oldEnd, (node, number, pos, parent, index) => {
                                    if (node.type.name === 'variableTag') {
                                        result = false;
                                    }
                                });
                            });
                        });
                    }

                    return result;
                },
            }),
        ];
    },
});
