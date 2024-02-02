import { Node, mergeAttributes } from '@tiptap/core';
import { VueNodeViewRenderer, VueRenderer } from '@tiptap/vue-3';
import { Plugin, PluginKey, NodeSelection } from '@tiptap/pm/state';
import { Suggestion } from '@tiptap/suggestion';

import tippy from 'tippy.js';
import 'tippy.js/themes/light-border.css';

import { clone } from '@utils/object';

import VariableTagView from './VariableTag.vue';
import VariableListSuggestion from './VariableListSuggestion.vue';

export const VariableTagPluginKey = new PluginKey('variableTagPlugin');

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
            Suggestion({
                editor: this.editor,
                pluginKey: VariableTagPluginKey,
                char: '{',

                items: ({ editor, query }) => {
                    const items = clone(this.options.field.variables);

                    return items.filter((item) => { return !item.heading; })
                        .filter((item) => { return item.label.toLowerCase().includes(query.toLowerCase()); })
                        .slice(0, 5);
                },

                render: () => {
                    let component;
                    let popup;

                    return {
                        onStart: (props) => {
                            component = new VueRenderer(VariableListSuggestion, {
                                editor: props.editor,

                                props: {
                                    items: props.items,
                                    command: props.command,
                                },
                            });

                            if (!props.clientRect) {
                                return;
                            }

                            let parentElement = document.body;

                            // When in a modal window, `vue-final-modal` focus trap will override the click event when trying to
                            // select a variable from the list. So instead ensure that tippy is added to the closest modal
                            if (this.options.field.$el && this.options.field.$el.closest('.fui-modal')) {
                                parentElement = this.options.field.$el.closest('.fui-modal');
                            }

                            popup = tippy('body', {
                                getReferenceClientRect: props.clientRect,
                                appendTo: () => { return parentElement; },
                                content: component.element,
                                showOnCreate: true,
                                interactive: true,
                                trigger: 'manual',
                                placement: 'bottom-start',
                                theme: 'light-border toolbar-dropdown',
                            });
                        },

                        onUpdate(props) {
                            component.updateProps(props);

                            if (!props.clientRect) {
                                return;
                            }

                            popup[0].setProps({
                                getReferenceClientRect: props.clientRect,
                            });
                        },

                        onKeyDown(props) {
                            if (props.event.key === 'Escape') {
                                popup[0].hide();

                                return true;
                            }

                            return component.ref?.onKeyDown(props);
                        },

                        onExit() {
                            popup[0].destroy();
                            component.destroy();
                        },
                    };
                },

                command: ({ editor, range, props }) => {
                    // Increase range.to by one when the next node is of type "text" and starts with a space character
                    const { nodeAfter } = editor.view.state.selection.$to;
                    const overrideSpace = nodeAfter?.text?.startsWith(' ');

                    if (overrideSpace) {
                        range.to += 1;
                    }

                    editor.chain().focus().insertContentAt(range, [
                        {
                            type: this.name,
                            attrs: props,
                        },
                        {
                            type: 'text',
                            text: ' ',
                        },
                    ]).run();

                    window.getSelection()?.collapseToEnd();
                },

                allow: ({ state, range }) => {
                    const $from = state.doc.resolve(range.from);
                    const type = state.schema.nodes[this.name];
                    const allow = !!$from.parent.type.contentMatch.matchType(type);

                    return allow;
                },
            }),

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
