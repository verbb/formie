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
                :key="field.__id"
                :field-index="index"
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

import Field from '@components/Field.vue';
import { Drop } from '@vendor/vue-drag-drop';

export default {
    name: 'FieldRow',

    components: {
        Field,
        Drop,
    },

    props: {
        // eslint-disable-next-line
        __id: {
            type: String,
            default: '',
        },

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
            if (!data || !this.canDrag(data)) {
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
                this.moveRow(rowIndex, data.fieldId);
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
            // Get the path to _this_ row, which is close to where we want to insert the new row
            const destinationPath = this.$store.getters['form/parentKeyPath'](this.__id, [rowIndex]);

            const newField = this.$store.getters['fieldtypes/newField'](type, {
                brandNewField: true,
            });

            const newRow = {
                __id: newId(),
                fields: [newField],
            };

            this.$store.dispatch('form/addField', {
                destinationPath,
                value: newRow,
            });

            this.$events.emit('formie:add-field');
        },

        moveRow(rowIndex, fieldId) {
            // Get the source field to move
            const sourcePath = this.$store.getters['form/keyPath'](fieldId);

            // Get the path to _this_ row, which is close to where we want to insert the new row
            const destinationPath = this.$store.getters['form/parentKeyPath'](this.__id, [rowIndex]);

            // Get the parent `rows` so that we can insert it at the index
            const fieldToMove = this.$store.getters['form/valueByKeyPath'](sourcePath);

            const newRow = {
                __id: newId(),
                fields: [fieldToMove],
            };

            this.$store.dispatch('form/moveField', {
                sourcePath,
                destinationPath,
                value: newRow,
            });
        },

        canDrag(data) {
            // Disable nested Group/Repeater fields
            if (data.hasNestedFields && this.isNested) {
                return false;
            }

            // When moving a field from outside a nested field into a nested field, only allow this for Group
            // fields, or within the same parent (Repeater).
            // Adding a new field is fair game for any field, so only guard for moving.
            if (data.trigger === 'field') {
                if (this.parentFieldId) {
                    const parentField = this.$store.getters['form/field'](this.parentFieldId);

                    if (parentField && parentField.type !== 'verbb\\formie\\fields\\Group') {
                        // Only allow moving in the same parent field
                        if (data.parentFieldId === this.parentFieldId) {
                            return true;
                        }

                        return false;
                    }
                }

                // When moving a nested field, prevent it from being moved outside for anything other than a Group
                // but allow moving inside the parent (Repeater)
                if (data.parentFieldId) {
                    const parentField = this.$store.getters['form/field'](data.parentFieldId);

                    if (parentField && parentField.type !== 'verbb\\formie\\fields\\Group') {
                        return false;
                    }
                }
            }

            return true;
        },
    },

};

</script>
