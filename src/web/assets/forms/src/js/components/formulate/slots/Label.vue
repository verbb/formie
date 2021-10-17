<template>
    <div :class="localClasses">
        <label :id="context.id + '-label'" :for="context.id" :class="{ 'required': required }">
            {{ context.label }}

            <span v-if="context.attributes.info" data-icon="info"></span>
        </label>

        {{ context.slotProps.label.tab }}

        <!-- Hard-code this so we don't have to specify a position all the time -->
        <div v-if="context.help" :id="context.id + '-help'" class="instructions">
            <vue-simple-markdown :source="context.help" />
        </div>
    </div>
</template>

<script>
import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';
import 'tippy.js/themes/light.css';

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

    mounted() {
        var { info } = this.context.attributes;
        var $info = this.$el.querySelector('[data-icon="info"]');

        if (info && $info) {
            tippy($info, {
                content: info,
                theme: 'light fui-field-instructions-tooltip',
                trigger: 'click',
                interactive: true,
                appendTo: document.body,
            });
        }
    },
};

</script>

<style scoped>

label {
    display: flex;
    align-items: center;
}

label span[data-icon] {
    display: inline-flex;
    margin-left: 5px;
}

label span[data-icon]:hover {
    color: #0B69A3;
}

</style>

<style>

.tippy-box[data-theme~="fui-field-instructions-tooltip"] .tippy-content {
    font-size: 14px;
    line-height: 20px;
    color: #3f4d5a;
    padding: 24px;
}

</style>
