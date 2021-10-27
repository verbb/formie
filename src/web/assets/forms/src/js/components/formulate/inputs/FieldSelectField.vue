<template>
    <div class="input ltr">
        <div class="select">
            <select
                v-model="context.model"
                v-bind="attributes"
                v-on="$listeners"
                @blur="context.blurHandler"
            >
                <option value disabled>{{ 'Select an option' | t('formie') }}</option>

                <option v-for="(option, j) in getFieldOptions()" :key="j" :value="option.value">
                    {{ option.label }}
                </option>
            </select>
        </div>
    </div>
</template>

<script>
import FormulateInputMixin from '@braid/vue-formulate/src/FormulateInputMixin';

export default {
    name: 'FieldSelectField',

    mixins: [FormulateInputMixin],

    computed: {
        field() {
            if (this.$editingField) {
                return this.$editingField.field;
            }

            return [];
        },
    },

    methods: {
        getFieldOptions() {
            var fields = [];
            var allFields = this.$store.getters['form/fields'];

            var excludeSelf = this.context.attributes.excludeSelf || false;
            var fieldTypes = this.context.attributes.fieldTypes || [];

            allFields.forEach(field => {
                if (fieldTypes.length && !fieldTypes.includes(field.type)) {
                    return;
                }

                if (excludeSelf && this.field && (this.field.handle === field.handle)) {
                    return;
                }

                fields.push({ label: field.label, value: '{' + field.handle + '}' });
            });

            return fields;
        },
    },
};

</script>
