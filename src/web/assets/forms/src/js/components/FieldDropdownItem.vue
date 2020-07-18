<template>
    <li>
        <a :data-icon="icon" role="option" :class="classes" @click.prevent="click">{{ label }}</a>
    </li>
</template>

<script>

export default {
    name: 'FieldDropdownItem',

    props: {
        icon: {
            type: String,
            default: '',
        },

        action: {
            type: String,
            default: '',
        },

        label: {
            type: String,
            default: '',
        },

        classes: {
            type: String,
            default: '',
        },
    },

    methods: {
        click(event) {
            this.$emit('click', this.action);
        },
    },
};

</script>
