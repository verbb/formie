import {
    outer,
    label,
    inner,
    help,
    messages,
    message,
    $if,
    createSection,
    formInput,
    fragment,
} from '@formkit/inputs';

import { dragAndDrop, animations } from '@formkit/drag-and-drop';

import { isEmpty, get } from 'lodash-es';

import { getId, setId } from '@utils/string';
import { clone } from '@utils/object';

const bulkOptions = createSection('bulkOptions', () => {
    return {
        $cmp: 'TableBulkOptions',
        if: '$enableBulkOptions',
        props: {
            predefinedOptions: '$predefinedOptions',
            setRows: '$fns.setRows',
        },
    };
});

const setRows = (node, items, replace = true) => {
    let values = node.context.node._value;

    // Reset the model
    if (replace) {
        values = [];
    }

    // Add each item properly
    items.forEach((item) => {
        const newRow = clone(item);

        // Always increment the total cols. We don't want to reuse deleted cols
        if (node.context.useColumnIds) {
            newRow.id = `col${++node.context.totalColumns}`;
        }

        values.push(setId(item));
    });

    node.context.node.input(values);
};

const addRow = (node) => {
    return () => {
        const values = node.context.node._value;
        const newRow = clone(node.context.newRowDefaults);

        // Add a way for us to track brand-new rows to generate handles
        Object.defineProperty(newRow, '__isNew', {
            enumerable: false,
            writable: true,
            value: Symbol(true),
        });

        // Always increment the total cols. We don't want to reuse deleted cols
        if (node.context.useColumnIds) {
            newRow.id = `col${++node.context.totalColumns}`;
        }

        values.push(setId(newRow));

        node.context.node.input(values);

        // Focus on the first text field on the new row, when the DOM is ready
        setTimeout(() => {
            const $rows = document.querySelectorAll(`#table-${node.props.id} tr`);

            if ($rows.length) {
                const $lastRow = $rows[$rows.length - 1];

                if ($lastRow) {
                    const $firstText = $lastRow.querySelector('input[type="text"]');

                    if ($firstText) {
                        $firstText.focus();
                    }
                }
            }
        }, 50);
    };
};

const removeRow = (node, index) => {
    return () => {
        const value = node.context.node._value;

        if (node.context.confirmDelete) {
            let message = node.context.confirmMessage;

            if (typeof message === 'function') {
                message = node.context.confirmMessage(value[index]);
            }

            if (confirm(message)) {
                value.splice(index, 1);
            }
        } else {
            value.splice(index, 1);
        }

        node.context.node.input(value);
    };
};

const canAddMore = (node) => {
    const totalItems = node.context._value.length;

    return (node.context.repeatable && (node.context.limit ? totalItems < node.context.limit : true));
};

function repeaterField(node) {
    // Enable this to be a dynamic list
    node.sync = true;

    // Assign a unique ID to this field to allow mounting the drag/drop uniquely
    node.id = getId('table');

    node.hook.input((value, next) => {
        // Ensure that values are typecast properly
        return next(Array.isArray(value) ? value : []);
    });

    node.on('created', () => {
        // Populate props with defaults
        node.context.initialValue = get(node.context.attrs, 'initialValue', []);
        node.context.generateValue = get(node.context.attrs, 'generateValue', true);
        node.context.repeatable = get(node.context.attrs, 'repeatable', true);
        node.context.showHeader = get(node.context.attrs, 'showHeader', true);
        node.context.confirmDelete = get(node.context.attrs, 'confirmDelete', false);
        node.context.confirmMessage = get(node.context.attrs, 'confirmMessage', '');
        node.context.newRowLabel = get(node.context.attrs, 'newRowLabel', 'Add an option');
        node.context.newRowDefaults = get(node.context.attrs, 'newRowDefaults', {});
        node.context.useColumnIds = get(node.context.attrs, 'useColumnIds', false);
        node.context.enableBulkOptions = get(node.context.attrs, 'enableBulkOptions', false);
        node.context.predefinedOptions = get(node.context.attrs, 'predefinedOptions', []);
        node.context.allowMultipleDefault = get(node.context.attrs, 'allowMultipleDefault', true);

        // Custom validation handling for blur
        node.context.labelsWithError = [];
        node.context.valuesWithError = [];

        // Columns can be dynamic wth the case of the Table field showing defaults
        if (!Array.isArray(node.context.columns)) {
            setTimeout(() => {
                const $store = node.config.rootConfig.formieConfig;

                if ($store) {
                    const { editingField } = $store.state.formie;

                    if (editingField) {
                        node.context.columns = editingField.field.settings.columns;
                    }
                }
            }, 50);
        }

        // Populate the model with correct `__id` for each item
        node.context._value.forEach((item) => {
            item = setId(item);
        });

        // Populate the values is empty
        if (isEmpty(node._value) && !isEmpty(node.context.initialValue)) {
            const values = clone(node.context.initialValue);

            // Populate the model with correct `__id` for each item
            values.forEach((item) => {
                item = setId(item);
            });

            node.context.node.input(values);
        }

        // Set the total columns now, so we can keep track of all added/deleted cols
        // But make sure to find the largest column, becuase they can be deleted, we can't
        // rely on the length of the array
        // eslint-disable-next-line
        node.context.totalColumns = Math.max(Math.max.apply(Math, clone(node.context._value).map((o) => {
            if (o.id) { return o.id.toString().replace('col', ''); }
        })), node.context._value.length) || 0;

        if (node.context?.fns) {
            node.context.fns.setRows = setRows.bind(null, node);
            node.context.fns.addRow = addRow.bind(null, node);
            node.context.fns.removeRow = removeRow.bind(null, node);
            node.context.fns.canAddMore = canAddMore.bind(null, node);
        }
    });

    node.on('mounted', () => {
        const $tbody = document.querySelector(`#table-${node.id} tbody`);

        if (!$tbody) {
            console.log(`Unable to find #table-${node.id} tbody`);

            return;
        }

        dragAndDrop({
            parent: $tbody,
            getValues: () => {
                return node.context.node._value;
            },
            setValues: (newValues) => {
                node.context.node.input(newValues);
            },
            config: {
                dragHandle: '.move.icon',
                plugins: [animations()],
            },
        });
    });
}

export default {
    schema: outer(
        label('$label'),
        help('$help'),
        inner(
            bulkOptions(),
            {
                $el: 'table',
                attrs: {
                    id: '$: "table-" + $node.id',
                    class: 'editable fullwidth',
                    'data-is-repeatable': '$repeatable',
                },
                children: [
                    {
                        $el: 'thead',
                        if: '$showHeader',
                        children: [
                            {
                                $el: 'tr',
                                children: [
                                    {
                                        $el: 'th',
                                        for: ['column', 'index', '$columns'],
                                        attrs: {
                                            class: '$column.class', scope: 'col', key: '$column', width: '$column.width',
                                        },
                                        children: '$column.label || $column.heading',
                                    },
                                    {
                                        $el: 'th',
                                    },
                                    {
                                        $el: 'th',
                                    },
                                ],
                            },
                        ],
                    },
                    {
                        $el: 'tbody',
                        children: [
                            {
                                $el: 'tr',
                                for: ['item', 'index', '$items'],
                                attrs: {
                                    key: '$item',
                                },
                                children: [
                                    {
                                        $formkit: 'group',
                                        index: '$index',
                                        children: [
                                            {
                                                $cmp: 'TableCell',
                                                for: ['column', 'colIndex', '$columns'],
                                                props: {
                                                    column: '$column',
                                                    index: '$index',
                                                    context: '$node.context',
                                                },
                                            },
                                        ],
                                    },
                                    {
                                        $el: 'td',
                                        attrs: { class: 'thin action' },
                                        children: [
                                            {
                                                $el: 'a',
                                                attrs: { class: 'move icon', title: 'Reorder', role: 'button' },
                                            },
                                        ],
                                    },
                                    {
                                        $el: 'td',
                                        attrs: { class: 'thin action' },
                                        children: [
                                            {
                                                $el: 'a',
                                                attrs: {
                                                    class: 'delete icon', title: 'Delete', role: 'button', onClick: '$fns.removeRow($index)',
                                                },
                                            },
                                        ],
                                    },
                                ],
                            },
                        ],
                    },
                ],
            },
            {
                $el: 'button',
                if: '$fns.canAddMore()',
                attrs: {
                    class: 'btn dashed add icon',
                    type: 'button',
                    onClick: '$fns.addRow()',
                    tabindex: '0',
                },
                children: '$newRowLabel',
            },
        ),
        messages(message('$message.value')),
    ),

    type: 'list',

    props: [
        'min',
        'max',
        'columns',
    ],

    features: [
        repeaterField,
    ],
};
