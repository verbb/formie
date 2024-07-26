<template>
    <table ref="table" class="editable fullwidth">
        <thead>
            <tr>
                <th v-for="(col, key, index) in columns" :key="index" scope="col" :class="col.class">
                    {{ col.label ? col.label : '' }}
                    {{ col.heading ? col.heading : '' }}
                </th>
            </tr>
        </thead>

        <tbody>
            <tr v-for="(row, rowKey, rowIndex) in rows" :key="rowIndex">
                <template v-for="(col, colKey, colIndex) in columns">
                    <th v-if="col.type === 'heading'" :key="colIndex" :class="col.class">
                        {{ row[colKey] }}
                    </th>

                    <td v-if="col.type === 'fieldSelect'" :key="colIndex" :class="col.class">
                        <div class="flex flex-nowrap">
                            <div class="small select">
                                <select v-model="proxyValue[rowKey]" :name="colKey">
                                    <option v-for="(option, j) in fieldSelectOptions()" :key="j" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </td>
                </template>
            </tr>
        </tbody>
    </table>
</template>

<script>
import { mapState } from 'vuex';
import {
    get, truncate, isEmpty, isPlainObject,
} from 'lodash-es';

import { toBoolean } from '@utils/bool';

export default {
    props: {
        context: {
            type: Object,
            required: true,
        },
    },

    data() {
        return {
            proxyValue: {},
        };
    },

    computed: {
        ...mapState({
            editingField: (state) => { return state.formie.editingField; },
        }),

        field() {
            if (this.editingField) {
                return this.editingField.field;
            }

            return [];
        },

        columns() {
            return get(this.context.attrs, 'columns', {});
        },

        rows() {
            return get(this.context.attrs, 'rows', {});
        },
    },

    watch: {
        proxyValue: {
            deep: true,
            handler(newValue) {
                this.context.node.input(newValue);
            },
        },
    },

    mounted() {
        // Set the proxy value only if an object. Just in case it's an array or null, we want to retain the default empty object
        if (isPlainObject(this.context._value)) {
            this.proxyValue = this.clone(this.context._value);
        }
    },

    methods: {
        fieldSelectOptions() {
            const includedTypes = this.context.attrs.fieldTypes || [];
            const excludedFields = [this.field.__id];

            const fields = this.$store.getters['form/getFieldSelectOptions']({
                excludedFields,
                includedTypes,
            });

            const options = [
                { label: this.t('formie', 'Select an option'), value: '' },
                ...fields,
            ];

            return options;
        },
    },
};

</script>
