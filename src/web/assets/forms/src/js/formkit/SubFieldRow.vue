<template>
    <div>
        <div v-show="rowIndex === 0" class="form-field-drop-target-container" :class="{ 'is-active': dropzonesActive, 'is-hover': dropzoneTopHover }">
            <div class="dropzone-holder">
                <drop
                    ref="dropzoneTop"
                    class="form-field-dropzone form-field-dropzone-horizontal"
                    :data-row="rowIndex"
                    @on-drop="dragDrop"
                    @on-dragenter="dragEnter"
                    @on-dragleave="dragLeave"
                />

                <div class="dashed-dropzone dashed-dropzone-horizontal"></div>
            </div>
        </div>

        <div class="fui-subfield-row">
            <SubField v-for="(field, fieldIndex) in row.fields" :key="fieldIndex" :field="field" :field-index="fieldIndex" />
        </div>

        <div class="form-field-drop-target-container" :class="{ 'is-active': dropzonesActive, 'is-hover': dropzoneBottomHover }">
            <div class="dropzone-holder">
                <drop
                    ref="dropzoneBottom"
                    class="form-field-dropzone form-field-dropzone-horizontal"
                    :data-row="rowIndex + 1"
                    @on-drop="dragDrop"
                    @on-dragenter="dragEnter"
                    @on-dragleave="dragLeave"
                />

                <div class="dashed-dropzone dashed-dropzone-horizontal"></div>
            </div>
        </div>
    </div>
</template>

<script>
import { Drop } from '@vendor/vue-drag-drop';

import SubField from '@formkit-components/SubField.vue';

export default {
    name: 'SubFieldRow',

    components: {
        SubField,
        Drop,
    },

    props: {
        row: {
            type: Object,
            default: () => {},
        },

        rowIndex: {
            type: Number,
            default: 0,
        },
    },

    data() {
        return {
            dropzonesActive: false,
            dropzoneTopHover: false,
            dropzoneBottomHover: false,
        };
    },

    created() {
        this.$events.on('formie:subfield-dragging-active', this.draggingActive);
        this.$events.on('formie:subfield-dragging-inactive', this.draggingInactive);
    },

    beforeUnmount() {
        this.$events.off('formie:subfield-dragging-active', this.draggingActive);
        this.$events.off('formie:subfield-dragging-inactive', this.draggingInactive);
    },

    methods: {
        draggingActive(data) {
            this.dropzonesActive = true;
        },

        draggingInactive() {
            this.dropzonesActive = false;
            this.dropzoneTopHover = false;
            this.dropzoneBottomHover = false;
        },

        dragEnter(data, event) {
            // Protect against anything being dragged in
            if (!data) {
                return;
            }

            this.toggleDropzone(event, true);
        },

        dragLeave(data, event) {
            this.toggleDropzone(event, false);
        },

        dragDrop(data, event) {
            // Protect against anything being dragged in
            if (!data) {
                return;
            }

            // Reset the state
            this.$events.emit('formie:subfield-dragging-inactive');

            const rowIndex = event.target.getAttribute('data-row');
            const sourceRowIndex = data.rowIndex;
            const sourceFieldIndex = data.fieldIndex;

            this.$parent.moveRow(sourceRowIndex, sourceFieldIndex, rowIndex);
        },

        toggleDropzone(event, state) {
            if (event.target === this.$refs.dropzoneTop.$el) {
                this.dropzoneTopHover = state;
            } else if (event.target === this.$refs.dropzoneBottom.$el) {
                this.dropzoneBottomHover = state;
            }
        },
    },
};

</script>
