<template>
    <div :class="{ 'hidden': !conditional }">
        <slot></slot>
    </div>
</template>

<script>

import { parse } from '../../utils/conditionals';

export default {
    name: 'ToggleGroup',

    computed: {
        conditional() {
            return parse(this.$attrs.conditional, this.$editingField ? this.$editingField.field : null);
        },
    },
};

</script>
