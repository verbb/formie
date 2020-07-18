<template>
    <span class="fui-toggle" :class="{ 'expanded': !context.model, 'collapsed': context.model }" @click.prevent="toggleCollapse"></span>
</template>

<script>

export default {
    name: 'Collapse',

    props: {
        context: {
            type: Object,
            required: true,
        },
    },

    methods: {
        toggleCollapse() {
            this.context.model = !this.context.model;
        },
    },

};

</script>

