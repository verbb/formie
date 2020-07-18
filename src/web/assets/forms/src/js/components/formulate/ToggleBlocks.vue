<template>
    <div>
        <slot></slot>
    </div>
</template>

<script>

export default {
    name: 'ToggleBlocks',

    props: {
        context: {
            type: Object,
            required: true,
        },
    },
};

</script>

