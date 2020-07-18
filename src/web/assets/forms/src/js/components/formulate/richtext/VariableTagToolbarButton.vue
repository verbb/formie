<template>
    <div v-if="variables.length" v-on-clickaway="clickAway" class="fui-toolbar-wrap">
        <button v-tooltip="button.text" class="btn fui-toolbar-btn" :class="{ 'active': isOpen }" @click.prevent="showToolbar">
            <i class="far" :class="'fa-' + button.icon"></i>
        </button>

        <variable-list :is-open="isOpen" :variables="variables" @updated="addVariable" />
    </div>
</template>

<script>
import { directive as onClickaway } from 'vue-clickaway';

import VariableList from '../variables/VariableList.vue';
import RichTextToolbarButton from './RichTextToolbarButton.vue';

export default {
    name: 'VariableTagToolbarButton',

    components: {
        VariableList,
    },

    directives: {
        onClickaway,
    },

    mixins: [RichTextToolbarButton],

    data() {
        return {
            isOpen: false,
            variables: this.field.variables,
        };
    },

    methods: {
        showToolbar() {
            this.isOpen = false;

            this.$nextTick(() => {
                this.isOpen = true;
            });
        },

        clickAway() {
            this.isOpen = false;
        },

        addVariable(e) {
            this.editor.commands.variableTag({
                label: e.target.getAttribute('data-label'),
                value: e.target.getAttribute('data-value'),
            });

            this.isOpen = false;
            this.editor.focus();
        },
    },
};

</script>


<style lang="scss" scoped>

.fui-toolbar-wrap {
    display: inline-block;
    position: relative;
}

.fui-variable-list {
    display: block;
    left: 0;
    right: auto;
    white-space: nowrap;
}

</style>
