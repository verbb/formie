<template>
    <div class="fui-subfield-workspace">
        <div v-for="(row, rowIndex) in rows" :key="rowIndex" class="fui-subfield-layout">
            <SubFieldRow :row="row" :row-index="rowIndex" />
        </div>
    </div>
</template>

<script>
import { get } from 'lodash-es';

import { newId } from '@utils/string';
import { parse } from '@utils/conditionals';

import SubFieldRow from '@formkit-components/SubFieldRow.vue';

export default {
    name: 'SubFields',

    components: {
        SubFieldRow,
    },

    props: {
        context: {
            type: Object,
            default: () => {},
        },

        type: {
            type: String,
            default: '',
        },

        sourceKey: {
            type: String,
            default: '',
        },

        sourceValue: {
            type: String,
            default: '',
        },

        layoutKey: {
            type: String,
            default: '',
        },
    },

    computed: {
        field() {
            return this.context.node.parent.context._value;
        },

        fieldType() {
            return this.$store.getters['fieldtypes/fieldtype'](this.type);
        },

        rows() {
            // In case we're passing in a specific key on the model to fetch rows for.
            // See Date fields which have conditional layouts/rows.
            if (this.layoutKey) {
                return get(this.field, this.layoutKey);
            }

            return this.field.rows;
        },

        proxySourceValue() {
            return get(this.field, this.sourceValue);
        },
    },

    watch: {
        rows: {
            deep: true,
            handler(newValue) {
                this.context.node.input(newValue);
            },
        },
        proxySourceValue(newValue) {
            if (this.sourceKey) {
                if (parse(this.sourceKey, this.field)) {
                    this.context.node.input(this.rows);
                }
            }
        },
    },

    created() {
        // Update the model immediately, if empty. Mostly for when conditionally shown
        if (!this.context._value.length) {
            this.context.node.input(this.rows);
        }
    },

    methods: {
        moveRow(sourceRowIndex, sourceFieldIndex, rowIndex) {
            const data = {
                __id: newId(),
            };

            // // Just guard against not actually moving rows - but only if its a full-width field
            if (sourceRowIndex === rowIndex || sourceRowIndex === (rowIndex - 1)) {
                // We need to factor in moving a column from columns to a single row
                // even if that's directly above it. You want to break it out into its own row
                if (this.rows[sourceRowIndex].fields.length === 1) {
                    return;
                }
            }

            // Remove the old column - but also insert it to our new field/row data
            // Remember using splice with `1` will remove 1 element from the array
            data.fields = this.rows[sourceRowIndex].fields.splice(sourceFieldIndex, 1);

            // Check to see if there are no more fields - delete the row too
            if (this.rows[sourceRowIndex].fields.length === 0) {
                this.rows.splice(sourceRowIndex, 1);

                // If we've completely removed the row, and we're moving down the list
                // be sure to account for the now incorrect array size
                if (sourceRowIndex < rowIndex) {
                    rowIndex = rowIndex - 1;
                }
            }

            // Add the new row
            this.rows.splice(rowIndex, 0, data);
        },

        moveField(sourceRowIndex, sourceFieldIndex, rowIndex, fieldIndex) {
            // Just guard against not actually moving columns
            if (sourceRowIndex === rowIndex && sourceFieldIndex === fieldIndex) {
                return;
            }

            // Just guard against not actually moving columns
            if (sourceRowIndex === rowIndex && sourceFieldIndex === (fieldIndex - 1)) {
                return;
            }

            // noinspection EqualityComparisonWithCoercionJS
            if (sourceRowIndex == rowIndex && this.rows[sourceRowIndex].fields.length === 1) {
                // Not moving the field anywhere.
                return;
            }

            // Remove the old column
            const [fieldData] = this.rows[sourceRowIndex].fields.splice(sourceFieldIndex, 1);

            // Check to see if there are no more fields - delete the row too
            if (this.rows[sourceRowIndex].fields.length === 0) {
                this.rows.splice(sourceRowIndex, 1);

                // If we've completely removed the row, and we're moving down the list
                // be sure to account for the now incorrect array size
                if (sourceRowIndex < rowIndex) {
                    rowIndex = rowIndex - 1;
                }
            }

            // Add the new row
            this.rows[rowIndex].fields.splice(fieldIndex, 0, fieldData);
        },
    },
};

</script>

<style lang="scss">

.fui-subfield-workspace {
    background-color: var(--gray-050);
    background-image: linear-gradient(to right, var(--gray-100) 1px, transparent 0), linear-gradient(to bottom, var(--gray-100) 1px, transparent 1px);
    background-position: -1px -1px;
    background-size: 24px 24px;
    box-shadow: inset 0 1px 3px -1px #acbed2;
    border-radius: 3px;
    padding: 10px;
}

.fui-subfield-layout {
    // margin: 0 -7px;
}

.fui-subfield-row {
    display: flex;
    flex-wrap: wrap;
}

.fui-subfield-col {
    position: relative;
    width: 100%;
    min-height: 1px;
    flex-basis: 0;
    flex-grow: 1;
    max-width: 100%;
    display: flex;
    flex: 1;
}

.fui-subfield-layout .form-field-drop-target {
    width: 0;
    z-index: 1;
    transform: translateY(11px) translateX(-3px);
    height: calc(100% - 21px);

    &.is-active {
        z-index: 10;
    }

    .form-field-dropzone-vertical {
        top: -9px;
        left: -7px;
        width: 20px;
        bottom: -7px;
    }
}

.fui-subfield-layout .form-field-drop-target-container {
    position: relative;
    padding-left: 10px;
    padding-right: 10px;
    width: 100%;
    height: 8px;

    &.is-active {
        z-index: 10;
    }

    .form-field-dropzone-horizontal {
        height: 30px;
        top: -11px;
    }
}

</style>
