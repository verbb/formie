<template>
    <div class="select">
        <select
            v-bind="context.attrs"
            :id="context.id"
            :name="context.node.name"
            :aria-describedby="context.describedBy"
            :value="context._value"
            @input="selectInput"
        >
            <option value disabled hidden :selected="!context._value">{{ t('formie', 'Select an option') }}</option>

            <option v-for="(option, j) in getFieldOptions()" :key="j" :value="option.value" :selected="isSelected(context.node, option.value)">
                {{ option.label }}
            </option>
        </select>
    </div>
</template>

<script>
import { mapState } from 'vuex';

export default {
    props: {
        context: {
            type: Object,
            default: () => {},
        },
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
    },

    methods: {
        isSelected(node, option) {
            // Here we trick reactivity (if at play) to watch this function.
            node.context && node.context.value;

            return Array.isArray(node._value)
                ? node._value.includes(option)
                : (node.value === undefined && !option) || node._value == option;
        },

        selectInput(e) {
            this.context.node.input(e.target.value);
        },

        getFieldOptions() {
            const fields = [];
            const allFields = this.$store.getters['form/fields'];

            const excludeSelf = this.context.attrs.excludeSelf || false;
            const fieldTypes = this.context.attrs.fieldTypes || [];

            allFields.forEach((field) => {
                if (fieldTypes.length && !fieldTypes.includes(field.type)) {
                    return;
                }

                if (excludeSelf && this.field && (this.field.handle === field.handle)) {
                    return;
                }

                fields.push({ label: field.label, value: `{${field.handle}}` });
            });

            return fields;
        },
    },
};

</script>
