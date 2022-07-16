<template>
    <div :class="context.classes.element" :data-is-repeatable="context.repeatable">
        <div v-if="enableBulkOptions" tabindex="0" class="btn add icon fui-table-bulk-add-btn" @click.prevent="openModal">
            {{ t('formie', 'Bulk add options') }}
        </div>

        <table-bulk-options
            v-if="enableBulkOptions && showModal"
            ref="bulkOptionsModal"
            v-model:showModal="showModal"
            :predefined-options="predefinedOptions"
            :table-field="this"
            @closed="onModalClosed"
        />

        <table v-show="columns.length" ref="table" class="editable fullwidth">
            <thead v-if="showHeader">
                <tr>
                    <th v-for="(col, index) in columns" :key="index" scope="col" :class="col.class">
                        {{ col.label ? col.label : '' }}
                        {{ col.heading ? col.heading : '' }}
                    </th>

                    <th colspan="2"></th>
                </tr>
            </thead>

            <draggable
                :list="context._value"
                tag="tbody"
                :class="{ 'is-dragging': dragging }"
                handle=".move.icon"
                animation="150"
                ghost-class="vue-admin-table-drag"
                item-key="__id"
                @start="dragging = true"
                @end="dragging = false"
            >
                <template #item="{ element, index }">
                    <TableRow :index="index" :model="element" :context="context" @remove="removeItem" />
                </template>
            </draggable>
        </table>

        <div v-if="canAddMore" class="btn add icon" :class="{ 'fui-table-btn-disabled': !columns.length }" tabindex="0" @click.prevent="addItem" @keydown.space.prevent="addItem">
            {{ t('formie', newRowLabel) }}
        </div>
    </div>
</template>

<script>
import { mapState } from 'vuex';
import { get, isEmpty, defaultsDeep } from 'lodash-es';
import Draggable from 'vuedraggable';

import { newId, setId } from '@utils/string';

import TableBulkOptions from './table/TableBulkOptions.vue';
import TableRow from './table/TableRow.vue';

export default {
    name: 'TableInput',

    components: {
        Draggable,
        TableRow,
        TableBulkOptions,
    },

    props: {
        context: {
            type: Object,
            required: true,
        },
    },

    emits: ['remove'],

    data() {
        return {
            dragging: false,
            totalColumns: 0,
            showModal: false,
        };
    },

    computed: {
        ...mapState({
            editingField: (state) => { return state.formie.editingField; },
        }),

        totalItems() {
            return Array.isArray(this.context._value) ? this.context._value.length : this.context.minimum || 1;
        },

        canAddMore() {
            return (this.repeatable && (this.context.limit ? this.totalItems < this.context.limit : true));
        },

        columns() {
            let columns = get(this.context.attrs, 'columns', []);

            if (typeof columns === 'string') {
                if (this.editingField) {
                    columns = get(this.editingField.field, columns);
                }
            }

            if (columns !== undefined) {
                return columns;
            }

            return [];
        },

        repeatable() {
            return get(this.context.attrs, 'repeatable', true);
        },

        showHeader() {
            return get(this.context.attrs, 'showHeader', true);
        },

        confirmDelete() {
            return get(this.context.attrs, 'confirmDelete', false);
        },

        confirmMessage() {
            return get(this.context.attrs, 'confirmMessage', '');
        },

        newRowLabel() {
            return get(this.context.attrs, 'newRowLabel', 'Add an option');
        },

        newRowDefaults() {
            return get(this.context.attrs, 'newRowDefaults', {});
        },

        useColumnIds() {
            return get(this.context.attrs, 'useColumnIds', false);
        },

        enableBulkOptions() {
            return get(this.context.attrs, 'enableBulkOptions', false);
        },

        predefinedOptions() {
            return get(this.context.attrs, 'predefinedOptions', false);
        },

        initialValue() {
            return get(this.context.attrs, 'initialValue', []);
        },
    },

    created() {
        // Testing
        // this.openModal();

        if (!Array.isArray(this.context._value)) {
            this.context.node.input([]);
        }

        // Populate an initial value. For some reason, using `value` doesn't work!
        if (isEmpty(this.context._value) && !isEmpty(this.initialValue)) {
            // Ensure FormKit has settled
            setTimeout(() => {
                this.context.node.input(this.initialValue);
            }, 20);
        }

        // Set the total columns now, so we can keep track of all added/deleted cols
        // But make sure to find the largest column, becuase they can be deleted, we can't
        // rely on the length of the array
        // eslint-disable-next-line
        this.totalColumns = Math.max(Math.max.apply(Math, this.clone(this.context._value).map((o) => {
            if (o.id) { return o.id.toString().replace('col', ''); }
        })), this.context._value.length) || 0;
    },

    methods: {
        setItems(items, replace = true) {
            // Reset the model
            if (replace) {
                this.context.node.input([]);
            }

            // Add each item properly
            items.forEach((item) => {
                this.addItem(null, item);
            });
        },

        addItem(e, item = {}) {
            // Ensure we make the new row data non-reactive
            let newRow = this.clone(this.newRowDefaults);

            // If we're passing in specific item data
            if (!isEmpty(item)) {
                newRow = item;
            }

            if (typeof newRow === 'function') {
                newRow = newRow();
            }

            // Add a symbol to track the status of this new item
            // Note the verbose syntax in order to have the variable hidden
            Object.defineProperty(newRow, '__isNew', {
                enumerable: false,
                writable: true,
                value: Symbol(true),
            });

            // Always increment the total cols. We don't want to reuse deleted cols
            if (this.useColumnIds) {
                newRow.id = `col${++this.totalColumns}`;
            }

            // Always ensure that the value for the model is an array
            if (!Array.isArray(this.context._value)) {
                this.context.node.input([]);
            }

            this.context._value.push(setId(newRow));

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
                const row = this.context._value[index];

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
            this.context._value.splice(index, 1);
        },

        openModal() {
            this.showModal = true;
        },

        onModalClosed() {
            this.showModal = false;
        },
    },
};

</script>

<style lang="scss" scoped>

.fui-table-bulk-add-btn {
    position: absolute;
    top: -36px;
    right: 0;
    font-size: 12px;
    border-radius: 3px;
    padding: 5px 12px;
    height: auto;
}

.fui-table-btn-disabled {
    opacity: 0.2;
    pointer-events: none;
}

</style>
