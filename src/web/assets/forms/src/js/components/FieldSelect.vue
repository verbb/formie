<template>
    <div class="field">
        <div class="heading">
            <label :id="id + '-label'" :for="id">{{ label }}</label>

            <div class="instructions">
                <p>{{ instructions }}</p>
            </div>
        </div>

        <div class="fui-element-mapping input ltr">
            <div class="select">
                <select v-model="proxyValue" :name="name">
                    <option value="">{{ 'Always Opt-in' | t('formie') }}</option>

                    <option v-for="(option, j) in getFieldOptions()" :key="j" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
            </div>
        </div>
    </div>
</template>

<script>

export default {
    name: 'FieldSelect',

    props: {
        label: {
            type: String,
            default: '',
        },

        instructions: {
            type: String,
            default: '',
        },

        id: {
            type: String,
            default: '',
        },

        name: {
            type: String,
            default: '',
        },

        value: {
            type: String,
            default: '',
        },
    },

    data() {
        return {
            error: false,
            errorMessage: '',
            loading: false,
            proxyValue: '',
        };
    },

    created() {
        this.proxyValue = this.value || '';
    },

    methods: {
        getFieldOptions() {
            var fields = this.$store.getters['form/fields'];

            return fields.map(field => {
                return { label: field.label, value: '{' + field.handle + '}' };
            });
        },
    },
};

</script>
