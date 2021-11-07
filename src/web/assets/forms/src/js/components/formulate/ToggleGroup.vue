<template>
    <div class="fui-field-toggle-group" :class="{ 'hidden': !conditional }">
        <slot></slot>
    </div>
</template>

<script>

import { parse } from '../../utils/conditionals';

export default {
    name: 'ToggleGroup',

    computed: {
        conditional() {
            var model = this.$attrs.model || null;

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

<style lang="scss">

.fui-field-toggle-group {
    margin: 24px 0;

    &:last-child {
        margin-bottom: 0 !important;
    }

    &:first-child {
        margin-top: 0 !important;
    }
}

</style>
