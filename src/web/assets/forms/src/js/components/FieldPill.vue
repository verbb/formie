<template>
    <drag
        class="fui-field-pill"
        :hide-image-html="!isSafari"
        :transfer-data="{ trigger: 'pill', supportsNested: fieldtype.supportsNested, type }"
        @dragstart="dragStart"
        @dragend="dragEnd"
    >
        <span class="fui-field-pill-icon" v-html="fieldtype.icon"></span>
        <span class="fui-field-pill-name">{{ fieldtype.label }}</span>
        <span class="fui-field-pill-drag"></span>

        <template v-if="!isSafari" slot="image" style="position: absolute">
            <div class="fui-field-pill" style="width: 148px;">
                <span class="fui-field-pill-icon" v-html="fieldtype.icon"></span>
                <span class="fui-field-pill-name">{{ fieldtype.label }}</span>
                <span class="fui-field-pill-drag"></span>
            </div>
        </template>
    </drag>
</template>

<script>
import { Drag } from 'vue-drag-drop';
import { isSafari } from '../utils/browser';

export default {
    name: 'FieldPill',

    components: {
        Drag,
    },

    props: {
        type: {
            type: String,
            default: 'text',
        },
    },

    data() {
        return {
            isSafari: isSafari(),
        };
    },

    computed: {
        fieldtype() {
            return this.$store.getters['fieldtypes/fieldtype'](this.type);
        },
    },

    methods: {
        dragStart(data, event) {
            // Emit event for dropzones
            this.$events.emit('formie:dragging-active', data, event);
        },

        dragEnd(data, event) {
            // Emit event for dropzones
            this.$events.emit('formie:dragging-inactive');
        },
    },
};

</script>
