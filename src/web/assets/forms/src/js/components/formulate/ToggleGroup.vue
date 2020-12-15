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
            var model = null;

            if (this.$editingField) {
                model = this.$editingField.field;
            }

            if (this.$editingNotification) {
                model = this.$editingNotification.notification;
            }

            console.log(this.$editingNotification);
            // console.log(this.$editingField);

            return parse(this.$attrs.conditional, model);
        },
    },
};

</script>
