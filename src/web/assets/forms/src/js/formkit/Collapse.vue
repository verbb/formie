<template>
    <span class="fui-toggle" :class="{ 'expanded': !context._value, 'collapsed': context._value }" @click.prevent="toggleCollapse"></span>
</template>

<script>

export default {
    props: {
        context: {
            type: Object,
            required: true,
        },
    },

    methods: {
        toggleCollapse() {
            this.context.node.input(!this.context._value);
        },
    },

};

</script>
