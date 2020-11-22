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

            if (Vue.prototype.$editingNotification) {
                model = Vue.prototype.$editingNotification.notification;
            }

            return parse(this.$attrs.conditional, model);
        },
    },
};

</script>
