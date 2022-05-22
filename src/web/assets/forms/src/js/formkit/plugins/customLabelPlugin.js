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
        const inputDefinition = clone(node.props.definition);

        if (!node.props.label) {
            return;
        }

        if (['radio', 'checkbox'].includes(node.props.type)) { return; }

        const originalSchema = inputDefinition.schema;

        const markdown = new MarkdownIt();

        const higherOrderSchema = (extensions) => {
            const { required } = node.props.attrs;
            let warningText = node.props.attrs.warning;
            const helpText = node.context.help;
            const infoText = node.props.attrs.info;

            let helpElement = {};
            let tabElement = {};
            let infoElement = {};

            if (node.props.attrs.tab) {
                tabElement = {
                    $el: 'span',
                    children: node.props.attrs.tab,
                };
            }

            if (infoText) {
                infoElement = {
                    $el: 'span',
                    attrs: {
                        'data-tippy-content': markdown.renderInline(infoText),
                        'data-icon': 'info',
                    },
                };

                setTimeout(() => {
                    const tipppy = tippy('[data-tippy-content]', {
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
                    class: `$: ${'$classes.label'} + ' ' + ${(required ? 'required' : null)}`,
                },
                children: [
                    '$label',
                    infoElement,
                ],
            };

            if (helpText) {
                helpElement = {
                    $el: 'div',
                    attrs: {
                        id: `$: "help-" + ${'$id'}`,
                        class: '$classes.help',
                        innerHTML: markdown.render(helpText),
                    },
                };
            }

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
                        warningText = Craft.t('formie', 'The required attribute will not be synced across field instances.');
                    }
                }
            }

            if (warningText) {
                extensions.help = {
                    $el: 'div',
                    attrs: {
                        class: 'warning with-icon',
                        innerHTML: markdown.renderInline(warningText),
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
