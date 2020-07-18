<template>
    <vue-simple-markdown v-if="warning" class="warning with-icon" :source="warning" />
</template>

<script>

export default {
    name: 'Help',

    props: {
        context: {
            type: Object,
            required: true,
        },
    },

    computed: {
        warning() {
            // Very special case for some fields...
            if (this.context.name === 'required' && this.$editingField) {
                if (this.$editingField.field.isSynced) {
                    return Craft.t('formie', 'The required attribute will not be synced across field instances.');
                }
            }

            return this.context.slotProps.label.warning;
        },
    },
};

</script>
