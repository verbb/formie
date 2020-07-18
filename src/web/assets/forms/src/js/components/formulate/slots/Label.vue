<template>
    <div :class="localClasses">
        <label :id="context.id + '-label'" :for="context.id" :class="{ 'required': required }">{{ context.label }}</label>

        {{ context.slotProps.label.tab }}

        <!-- Hard-code this so we don't have to specify a position all the time -->
        <div v-if="context.help" :id="context.id + '-help'" class="instructions">
            <p>{{ context.help }}</p>
        </div>
    </div>
</template>

<script>

export default {
    name: 'Label',

    props: {
        context: {
            type: Object,
            required: true,
        },
    },

    computed: {
        required() {
            return this.context.slotProps.label.required;
        },

        localClasses() {
            if (this.context.classification === 'box') {
                return 'box-heading';
            }

            return 'heading';
        },
    },
};

</script>
