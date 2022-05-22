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

        <div class="fui-row no-padding">
            <field
                v-for="(field, index) in fields"
                ref="fields"
                :key="field.vid"
                :column-index="index"
                :page-index="pageIndex"
                :row-index="rowIndex"
                :field-id="fieldId"
                :field="field"
                v-bind="field"
                :parent-field-id="parentFieldId"
            />
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
import { newId } from '@utils/string';
import { canDrag } from '@utils/drag-drop';

import Field from '@components/Field.vue';
import { Drop } from '@vendor/vue-drag-drop';

export default {
    name: 'FieldRow',

    components: {
        Field,
        Drop,
    },

    props: {
        id: {
            type: [String, Number],
            default: '',
        },

        pageIndex: {
            type: Number,
            default: -1,
        },

        rowIndex: {
            type: Number,
            default: 0,
        },

        fieldId: {
            type: String,
            default: '',
        },

        fields: {
            type: Array,
            default: () => { return []; },
        },

        parentFieldId: {
            type: String,
            default: '',
        },

        isNested: {
            type: Boolean,
            default: false,
        },
    },

    data() {
        return {
            dropzonesActive: false,
            dropzoneTopHover: false,
            dropzoneBottomHover: false,
        };
    },

    computed: {
        sourceField() {
            return this.$store.getters['form/field'](this.fieldId);
        },
    },

    created() {
        this.$events.on('formie:dragging-active', this.draggingActive);
        this.$events.on('formie:dragging-inactive', this.draggingInactive);
    },

    beforeUnmount() {
        this.$events.off('formie:dragging-active', this.draggingActive);
        this.$events.off('formie:dragging-inactive', this.draggingInactive);
    },

    methods: {
        draggingActive(data) {
            if (!this.canDrag(data)) {
                return;
            }

            this.dropzonesActive = true;
        },

        draggingInactive() {
            this.dropzonesActive = false;
            this.dropzoneTopHover = false;
            this.dropzoneBottomHover = false;
        },

        dragEnter(data, event) {
            // Protect against anything being dragged in
            if (!data || !this.canDrag(data)) {
                return;
            }

            this.toggleDropzone(event, true);
        },

        dragLeave(data, event) {
            this.toggleDropzone(event, false);
        },

        dragDrop(data, event) {
            // Protect against anything being dragged in
            if (!data || !this.canDrag(data)) {
                return;
            }

            // Reset the state
            this.$events.emit('formie:dragging-inactive');

            // Is this a pill? If so, we need to insert
            const isPill = (data.trigger === 'pill');
            const rowIndex = event.target.getAttribute('data-row');

            if (isPill) {
                const fieldtype = this.$store.getters['fieldtypes/fieldtype'](data.type);

                this.addRow(rowIndex, fieldtype.type);
            } else {
                const sourceRowIndex = data.rowIndex;
                const sourceColumnIndex = data.columnIndex;

                this.moveRow(sourceRowIndex, sourceColumnIndex, rowIndex);
            }
        },

        toggleDropzone(event, state) {
            if (event.target === this.$refs.dropzoneTop.$el) {
                this.dropzoneTopHover = state;
            } else if (event.target === this.$refs.dropzoneBottom.$el) {
                this.dropzoneBottomHover = state;
            }
        },

        addRow(rowIndex, type) {
            const newField = this.$store.getters['fieldtypes/newField'](type, {
                brandNewField: true,
                isNested: this.isNested,
            });

            const payload = {
                rowIndex,
                data: {
                    id: newId(),
                    fields: [
                        newField,
                    ],
                },
            };

            if (this.fieldId) {
                payload.fieldId = this.fieldId;
            } else {
                payload.pageIndex = this.pageIndex;
            }

            this.$store.dispatch('form/addRow', payload);
        },

        moveRow(sourceRowIndex, sourceColumnIndex, rowIndex) {
            const payload = {
                sourceRowIndex,
                sourceColumnIndex,
                rowIndex,
                data: {
                    id: newId(),
                },
            };

            if (this.fieldId) {
                payload.fieldId = this.fieldId;
            } else {
                payload.pageIndex = this.pageIndex;
            }

            this.$store.dispatch('form/moveRow', payload);
        },

        canDrag(data) {
            return canDrag(this.pageIndex, this.sourceField, data);
        },
    },

};

</script>
