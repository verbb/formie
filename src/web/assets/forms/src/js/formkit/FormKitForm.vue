<template>
    <FormKit
        v-bind="$attrs"
        ref="form"
        type="form"
        :actions="false"
        :preserve="true"
        :incomplete-message="false"
        :formie-store="$store"
        :plugins="[errorPlugin]"
    >
        <slot></slot>
    </FormKit>
</template>

<script>
import errorCollection from '@formkit-components/plugins/errorCollection.js';

const { errorMap, errorPlugin } = errorCollection();

export default {
    name: 'FormKitForm',

    computed: {
        errorPlugin() {
            return errorPlugin;
        },

        errorMap() {
            return errorMap;
        },
    },

    mounted() {
        // Add the Vuex store to the root config
        this.$refs.form.node.config.rootConfig.formieConfig = this.$store;

        // Add the error map (reactive) to the root config
        this.$refs.form.node.config.rootConfig.errorMap = this.errorMap;
    },

    methods: {
        submit() {
            this.$refs.form.node.submit();
        },

        getErrors() {
            const errors = [];

            for (const [key, value] of Object.entries(this.errorMap)) {
                if (value.blockingCount || value.errorCount) {
                    errors.push(key);
                }
            }

            return errors;
        },

        setErrors(errors) {
            this.$refs.form.node.setErrors(errors);
        },
    },
};

</script>
