<template>
    <div :class="context.classes.element" :data-is-repeatable="context.repeatable">
        <table ref="table" class="editable fullwidth">
            <thead v-if="showHeader">
                <tr>
                    <th v-for="(col, index) in columns" :key="index" scope="col" :class="col.class">
                        {{ col.label ? col.label : '' }}
                        {{ col.heading ? col.heading : '' }}
                    </th>

                    <th colspan="2"></th>
                </tr>
            </thead>

            <TableBody :context="context" @remove="removeItem">
                <slot></slot>
            </TableBody>
        </table>

        <div v-if="canAddMore" tabindex="0" class="btn add icon" @click.prevent="addItem" @keydown.space.prevent="addItem">
            {{ newRowLabel | t('formie') }}
        </div>
    </div>
</template>

<script>
import get from 'lodash/get';

import { setId } from '@braid/vue-formulate/src/libs/utils';
import { newId } from '../../../utils/string';

export default {
    name: 'TableField',

    props: {
        context: {
            type: Object,
            required: true,
        },
    },

    data() {
        return {
            totalColumns: 0,
        };
    },

    computed: {
        totalItems() {
            return Array.isArray(this.context.model) ? this.context.model.length : this.context.minimum || 1;
        },

        canAddMore() {
            return (this.context.repeatable && this.totalItems < this.context.limit);
        },

        items() {
            return Array.isArray(this.context.model) ? this.context.model : [{}];
        },

        columns() {
            const columns = this._getSlotProp('columns');

            if (typeof columns === 'string') {
                return get(this.$editingField.field, columns);
            }

            if (columns !== undefined) {
                return columns;
            }

            return [];
        },

        showHeader() {
            return this._getSlotProp('showHeader', true);
        },

        confirmDelete() {
            return this._getSlotProp('confirmDelete', false);
        },

        confirmMessage() {
            return this._getSlotProp('confirmMessage', '');
        },

        newRowLabel() {
            return this._getSlotProp('newRowLabel', 'Add an option');
        },

        newRowDefaults() {
            return this._getSlotProp('newRowDefaults', {});
        },

        useColumnIds() {
            return this._getSlotProp('useColumnIds', false);
        },
    },

    created() {
        if (!Array.isArray(this.context.model)) {
            this.context.model = [];
        }

        // Set the total columns now, so we can keep track of all added/deleted cols
        // But make sure to find the largest column, becuase they can be deleted, we can't
        // rely on the length of the array
        this.totalColumns = Math.max(Math.max.apply(Math, this.context.model.map((o) => {
            if (o.id) { return o.id.replace('col', ''); }
        })), this.context.model.length) || 0;
    },

    methods: {
        _getSlotProp(prop, fallback = null) {
            if (this.context.slotProps.repeatable[prop] !== undefined) {
                return this.context.slotProps.repeatable[prop];
            }

            return fallback;
        },

        getRowValue(index, field) {
            if (this.context.model[index] && has(this.context.model[index], field)) {
                return this.context.model[index][field];
            }

            return null;
        },

        addItem() {
            let { newRowDefaults } = this;

            if (typeof newRowDefaults === 'function') {
                newRowDefaults = newRowDefaults();
            }

            // Ensure we make the new row data non-reactive
            newRowDefaults = clone(newRowDefaults);

            // Add a symbol to track the status of this new item
            // Note the verbose syntax in order to have the variable hidden
            Object.defineProperty(newRowDefaults, '__isNew', {
                enumerable: false,
                writable: true,
                value: Symbol(true),
            });

            // Always increment the total cols. We don't want to reuse deleted cols
            if (this.useColumnIds) {
                newRowDefaults.id = 'col' + ++this.totalColumns;
            }

            if (Array.isArray(this.context.model)) {
                this.context.model.push(setId(newRowDefaults));
            } else {
                this.context.model = (new Array(this.totalItems + 1)).fill('').map(() => setId(newRowDefaults));
            }

            // Focus on the new row
            this.$nextTick().then(() => {
                const $rows = this.$refs.table.getElementsByTagName('tr');

                if ($rows.length) {
                    const $lastRow = $rows[$rows.length - 1];

                    if ($lastRow) {
                        const $firstText = $lastRow.querySelector('input[type="text"]');

                        if ($firstText) {
                            $firstText.focus();
                        }
                    }
                }
            });
        },

        removeItem(index) {
            if (this.confirmDelete) {
                let message = this.confirmMessage;
                const row = this.context.model[index];

                if (typeof message === 'function') {
                    message = this.confirmMessage(row);
                }

                if (confirm(message)) {
                    this.deleteRow(index);
                }
            } else {
                this.deleteRow(index);
            }
        },

        deleteRow(index) {
            if (Array.isArray(this.context.model) && this.context.model.length > this.context.minimum) {
                // In this context we actually have data
                this.context.model.splice(index, 1);
            } else if (this.items.length > this.context.minimum) {
                // In this context the fields have never been touched (not "dirty")
                this.context.model = (new Array(this.items.length - 1)).fill('').map(() => setId({}));
            }
        },
    },
};

</script>
