import { clone } from '@formkit/utils';

import MarkdownIt from 'markdown-it';

import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';
import 'tippy.js/themes/light.css';

//
// A plugin to for an opinionated label.
// 1. Adds info button to label (tippy).
// 2. Adds Markdown support to help, warning and info text.
// 3. Replaces help slot with warning text.
// 4. Moves help slot below label.
// 5. Add a `required` attribute to the label, if required.
//

export default function moveLabelPlugin(node) {
    node.on('created', () => {
        if (!node.props || !node.props.definition) {
            return;
        }

        const inputDefinition = clone(node.props.definition);

        if (['radio', 'checkbox'].includes(node.props.type)) { return; }

        const originalSchema = inputDefinition.schema;

        const markdown = new MarkdownIt();

        const higherOrderSchema = (extensions) => {
            // We need to add all content to the node's context to be reactive, otherwise we get an issue
            // where the same node props are used across fields. This also gives us the nice benefit of using
            // `$markdown` or `$warning` which is nicer to use
            node.context.required = node.props.attrs.required || '';
            node.context.requiredClass = node.context.required ? 'required' : '';
            node.context.info = node.props.attrs.info || '';
            node.context.warning = node.props.attrs.warning || '';
            node.context.markdown = (string) => { return string ? markdown.render(string) : string; };
            node.context.markdownInline = (string) => { return string ? markdown.renderInline(string) : string; };

            let helpElement = {};
            let tabElement = {};
            let infoElement = {};

            tabElement = {
                $el: 'span',
                $if: node.props.attrs.tab,
                children: node.props.attrs.tab,
            };

            if (node.props.info) {
                const tippyId = `tippy-${Craft.randomString(10)}`;

                infoElement = {
                    $el: 'span',
                    attrs: {
                        id: tippyId,
                        'data-tippy-content': '$markdownInline($info)',
                        'data-icon': 'info',
                    },
                };

                setTimeout(() => {
                    const tipppy = tippy(`#${tippyId}`, {
                        theme: 'light fui-field-instructions-tooltip',
                        trigger: 'click',
                        interactive: true,
                        allowHTML: true,
                        appendTo: document.body,
                    });
                }, 500);
            }

            const labelElement = {
                $el: 'label',
                if: '$label',
                attrs: {
                    id: `$: "label-" + ${'$id'}`,
                    for: '$id',
                    class: `$: ${'$classes.label'} + ' ' + ${'$requiredClass'}`,
                },
                children: [
                    '$label',
                    infoElement,
                ],
            };

            helpElement = {
                $el: 'div',
                $if: node.props.help,
                attrs: {
                    id: `$: "help-" + ${'$id'}`,
                    class: '$classes.help',
                    innerHTML: '$markdown($help)',
                },
            };

            extensions.label = {
                $el: 'div',
                attrs: {
                    class: 'heading',
                    for: null,
                },
                children: [
                    labelElement,
                    tabElement,
                    helpElement,
                ],
            };

            // Very special case for some fields...
            if (node.name === 'required') {
                // A bit gross, but get the Vuex store used by our app attached to the form element
                const $store = node.parent.props.attrs['formie-store'];

                if ($store && $store.state && $store.state.formie) {
                    const { editingField } = $store.state.formie;

                    if (editingField && editingField.field && editingField.field.isSynced) {
                        node.context.warning = Craft.t('formie', 'The required attribute will not be synced across field instances.');
                    }
                }
            }

            if (node.context.warning) {
                extensions.help = {
                    $el: 'div',
                    attrs: {
                        class: 'warning with-icon',
                        innerHTML: '$markdownInline($warning)',
                    },
                    children: null,
                };
            } else {
                extensions.help = { $el: null, children: null };
            }

            return originalSchema(extensions);
        };

        inputDefinition.schema = higherOrderSchema;
        node.props.definition = inputDefinition;
    });
}
