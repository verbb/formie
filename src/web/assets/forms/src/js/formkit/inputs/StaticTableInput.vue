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
import { get, truncate, isEmpty } from 'lodash-es';

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

    mounted() {
        if (isEmpty(this.context._value)) {
            // If brand-new and fresh, ensure we set the value to an object so reactivity kicks in
            this.context.node.input({});

            // Wait for FormKit to settle
            setTimeout(() => {
                this.proxyValue = this.context._value;
            }, 20);
        } else {
            this.proxyValue = this.context._value;
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
