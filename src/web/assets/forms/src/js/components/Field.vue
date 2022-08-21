<template>
    <div :class="'fui-col-' + columnWidth" style="display: flex;">
        <div v-show="columnIndex === 0 && showDropzones" class="form-field-drop-target" :class="{ 'is-active': dropzonesActive, 'is-hover': dropzoneLeftHover }">
            <div class="dropzone-holder">
                <drop
                    ref="dropzoneLeft"
                    class="form-field-dropzone form-field-dropzone-vertical"
                    :data-column="columnIndex"
                    :data-row="rowIndex"
                    @on-drop="dragDrop"
                    @on-dragenter="dragEnter"
                    @on-dragleave="dragLeave"
                />

                <div class="dashed-dropzone dashed-dropzone-vertical"></div>
            </div>
        </div>

        <drag
            ref="draggableField"
            class="fui-field-block"
            :class="{ 'is-active': dragActive, 'has-errors': field.hasError }"
            :transfer-data="{
                trigger: 'field',
                supportsNested: fieldtype.supportsNested,
                rowIndex,
                fieldId,
                pageIndex,
                columnIndex,
            }"
            :hide-image-html="!isSafari"
            @on-dragstart="dragStart"
            @on-dragend="dragEnd"
        >
            <div v-if="!fieldtype.supportsNested" class="fui-edit-overlay" @click.prevent="openModal"></div>

            <div class="fui-field-info">
                <label v-if="fieldtype.hasLabel" class="fui-field-label">
                    <span v-if="field.label && field.label.length">{{ field.label }}</span>
                    <span v-else>{{ fieldtype.label }}</span>

                    <span v-if="field.settings.required" class="error"> *</span>
                </label>

                <span v-if="field.isSynced" class="fui-field-synced">
                    <svg aria-hidden="true" focusable="false" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M440.65 12.57l4 82.77A247.16 247.16 0 0 0 255.83 8C134.73 8 33.91 94.92 12.29 209.82A12 12 0 0 0 24.09 224h49.05a12 12 0 0 0 11.67-9.26 175.91 175.91 0 0 1 317-56.94l-101.46-4.86a12 12 0 0 0-12.57 12v47.41a12 12 0 0 0 12 12H500a12 12 0 0 0 12-12V12a12 12 0 0 0-12-12h-47.37a12 12 0 0 0-11.98 12.57zM255.83 432a175.61 175.61 0 0 1-146-77.8l101.8 4.87a12 12 0 0 0 12.57-12v-47.4a12 12 0 0 0-12-12H12a12 12 0 0 0-12 12V500a12 12 0 0 0 12 12h47.35a12 12 0 0 0 12-12.6l-4.15-82.57A247.17 247.17 0 0 0 255.83 504c121.11 0 221.93-86.92 243.55-201.82a12 12 0 0 0-11.8-14.18h-49.05a12 12 0 0 0-11.67 9.26A175.86 175.86 0 0 1 255.83 432z" /></svg>
                    {{ t('formie', 'Synced') }}
                </span>

                <span v-if="field.hasConditions" class="fui-field-conditions">
                    <svg aria-hidden="true" focusable="false" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M288 144a110.94 110.94 0 0 0-31.24 5 55.4 55.4 0 0 1 7.24 27 56 56 0 0 1-56 56 55.4 55.4 0 0 1-27-7.24A111.71 111.71 0 1 0 288 144zm284.52 97.4C518.29 135.59 410.93 64 288 64S57.68 135.64 3.48 241.41a32.35 32.35 0 0 0 0 29.19C57.71 376.41 165.07 448 288 448s230.32-71.64 284.52-177.41a32.35 32.35 0 0 0 0-29.19zM288 400c-98.65 0-189.09-55-237.93-144C98.91 167 189.34 112 288 112s189.09 55 237.93 144C477.1 345 386.66 400 288 400z" /></svg>
                    {{ t('formie', 'Conditions') }}
                </span>

                <code class="fui-field-handle">{{ field.handle }}</code>

                <span class="fui-flex-break"></span>
                <span class="fui-field-instructions">{{ field.settings.instructions }}</span>

                <field-dropdown
                    :is-required="field.settings.required"
                    :can-require="fieldCanRequire"
                    @edit="openModal"
                    @require="requireField"
                    @unrequire="unrequireField"
                    @clone="cloneField"
                    @delete="deleteField"
                />
            </div>

            <field-preview :id="field.vid" class="fui-field-preview" :class="`fui-type-${nameKebab}`" :expected-type="expectedType" />

            <markdown v-if="fieldtype.data.warning" class="warning with-icon" :source="fieldtype.data.warning" />

            <field-edit-modal
                v-if="showModal"
                v-model:showModal="showModal"
                v-model:field="field"
                :field-ref="this"
                :fields-schema="fieldsSchema"
                :tabs-schema="tabsSchema"
                @delete="deleteField"
                @closed="onModalClosed"
            />

            <template v-if="!isSafari" #image>
                <div class="fui-field-pill" style="width: 148px;">
                    <span class="fui-field-pill-icon" v-html="fieldtype.icon"></span>

                    <span class="fui-field-pill-name">{{ field.label }}</span>
                    <span class="fui-field-pill-drag"></span>
                </div>
            </template>
        </drag>

        <div v-show="showDropzones" class="form-field-drop-target" :class="{ 'is-active': dropzonesActive, 'is-hover': dropzoneRightHover }">
            <div class="dropzone-holder">
                <drop
                    ref="dropzoneRight"
                    class="form-field-dropzone form-field-dropzone-vertical"
                    :data-column="columnIndex + 1"
                    :data-row="rowIndex"
                    @on-drop="dragDrop"
                    @on-dragenter="dragEnter"
                    @on-dragleave="dragLeave"
                />

                <div class="dashed-dropzone dashed-dropzone-vertical"></div>
            </div>
        </div>
    </div>
</template>

<script>
import Markdown from 'vue3-markdown-it';
import { Drag, Drop } from '@vendor/vue-drag-drop';
import { cloneDeep } from 'lodash-es';

// eslint-disable-next-line
import { generateHandle, getNextAvailableHandle, generateKebab, getDisplayName, newId } from '@utils/string';
import { isSafari } from '@utils/browser';
import { canDrag } from '@utils/drag-drop';

import FieldEditModal from '@components/FieldEditModal.vue';
import FieldPreview from '@components/FieldPreview.vue';
import FieldDropdown from '@components/FieldDropdown.vue';

export default {
    name: 'Field',

    components: {
        FieldEditModal,
        FieldPreview,
        FieldDropdown,
        Drag,
        Drop,
        Markdown,
    },

    props: {
        field: {
            type: Object,
            default: () => {},
        },

        columnIndex: {
            type: Number,
            default: 0,
        },

        pageIndex: {
            type: Number,
            default: null,
        },

        rowIndex: {
            type: Number,
            default: 0,
        },

        fieldId: {
            type: [String, Number],
            default: '',
        },

        expectedType: {
            type: String,
            default: '',
        },

        brandNewField: {
            type: Boolean,
            default: false,
        },

        parentFieldId: {
            type: String,
            default: '',
        },
    },

    data() {
        return {
            dropzonesActive: false,
            dropzoneLeftHover: false,
            dropzoneRightHover: false,
            dragActive: false,
            showModal: false,
            submitButton: false,
            isSafari: isSafari(),
        };
    },

    computed: {
        columnWidth() {
            const columns = this.$parent.fields.length;

            return 12 / columns;
        },

        showDropzones() {
            const columns = this.$parent.fields.length;

            return columns < 4;
        },

        fieldtype() {
            return this.$store.getters['fieldtypes/fieldtype'](this.field.type);
        },

        sourceField() {
            return this.$store.getters['form/field'](this.fieldId);
        },

        displayName() {
            return getDisplayName(this.field.type);
        },

        nameKebab() {
            return generateKebab(this.displayName);
        },

        fieldHandles() {
            return this.$store.getters['form/fieldHandlesExcluding'](this.field.vid, this.parentFieldId);
        },

        fieldCanRequire() {
            const disallowedFields = {
                'verbb\\formie\\fields\\formfields\\Address': false,
                'verbb\\formie\\fields\\formfields\\Heading': false,
                'verbb\\formie\\fields\\formfields\\Hidden': false,
                'verbb\\formie\\fields\\formfields\\Html': false,
                'verbb\\formie\\fields\\formfields\\Repeater': false,
                'verbb\\formie\\fields\\formfields\\Section': false,
                'verbb\\formie\\fields\\formfields\\Name': (field) => {
                    return !field.settings.useMultipleFields;
                },
            };

            // TODO: Probably refactor this to PHP
            const disallowedField = disallowedFields[this.field.type];
            if (typeof disallowedField === 'boolean') {
                return disallowedField;
            } if (typeof disallowedField === 'function') {
                const field = this.$store.getters['form/field'](this.field.vid);
                return disallowedField(field);
            }

            return true;
        },

        fieldsSchema() {
            return this.fieldtype.fieldsSchema;
        },

        tabsSchema() {
            return this.fieldtype.tabsSchema;
        },
    },

    created() {
        this.$events.on('formie:dragging-active', this.draggingActive);
        this.$events.on('formie:dragging-inactive', this.draggingInactive);

        // Open the modal immediately for brand new fields
        if (this.brandNewField) {
            this.openModal();

            // Testing
            // this.field.label = this.fieldtype.label;
            // this.field.handle = generateHandle(this.fieldtype.label);
        }
    },

    mounted() {
        // Testing
        if (this.$parent.$parent.pageIndex == 0 && this.$parent.rowIndex == 5 && this.columnIndex == 0) {
            // this.openModal();
        }
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
            this.dragActive = false;
            this.dropzoneLeftHover = false;
            this.dropzoneRightHover = false;
            this.dropzonesActive = false;
        },

        openModal() {
            this.showModal = true;
        },

        onModalClosed() {
            this.showModal = false;

            if (this.brandNewField) {
                this.$store.dispatch('form/deleteField', { id: this.field.vid });
            }
        },

        requireField() {
            const payload = {
                rowIndex: this.rowIndex,
                columnIndex: this.columnIndex,
                prop: 'required',
                value: true,
            };

            if (this.fieldId) {
                payload.fieldId = this.fieldId;
            } else {
                payload.pageIndex = this.pageIndex;
            }

            this.$store.dispatch('form/updateFieldSettings', payload);
        },

        unrequireField() {
            const payload = {
                rowIndex: this.rowIndex,
                columnIndex: this.columnIndex,
                prop: 'required',
                value: false,
            };

            if (this.fieldId) {
                payload.fieldId = this.fieldId;
            } else {
                payload.pageIndex = this.pageIndex;
            }

            this.$store.dispatch('form/updateFieldSettings', payload);
        },

        cloneField() {
            // Let's get smart about generating a handle. Check if its unique - if it isn't, make it unique
            const generatedHandle = generateHandle(this.field.label);
            let handles = this.$store.getters['form/fieldHandles'];

            // Get field handles for the parent field (group, repeater)
            if (this.parentFieldId) {
                handles = this.$store.getters['form/fieldHandlesForField'](this.parentFieldId);
            }

            // Generate a unique handle, and ensure it's under the 64 char db limit (factoring in `field_`
            // and the field suffix)
            const value = getNextAvailableHandle(handles, generatedHandle, 0);

            const maxHandleLength = this.$store.getters['formie/maxFieldHandleLength']();
            const newHandle = value.substr(0, maxHandleLength);

            const newField = this.$store.getters['fieldtypes/newField'](this.field.type, {
                label: this.field.label,
                handle: newHandle,
                settings: cloneDeep(this.field.settings),
            });

            // Clone the old field rows.
            if (this.field.supportsNested) {
                newField.rows = cloneDeep(this.field.rows);
                newField.rows.forEach((row) => {
                    row.id = newId();

                    row.fields.forEach((field) => {
                        delete field.id;
                        field.vid = newId();
                    });
                });
            }

            // Add a new row after this one
            const payload = {
                rowIndex: this.rowIndex + 1,
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

            this.$store.dispatch('form/appendRow', payload);
        },

        deleteField() {
            const name = this.field.label || this.fieldtype.label;

            const confirmationMessage = Craft.t('formie', 'Are you sure you want to delete “{name}”?', { name });

            if (confirm(confirmationMessage)) {
                this.$store.dispatch('form/deleteField', { id: this.field.vid });
            }
        },

        dragStart(data, event) {
            if (this.pageIndex < 0) {
                event.stopPropagation();
            }

            // Give it a second so that the z-index has a chance to bring the row dropzones into the forefront
            setTimeout(() => {
                // Emit event for dropzones
                this.$events.emit('formie:dragging-active', data, event);
            }, 50);

            this.dragActive = true;
        },

        dragEnd(data, event) {
            // Emit event for dropzones
            this.$events.emit('formie:dragging-inactive', data, event);
        },

        dragEnter(data, event) {
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
            this.$events.emit('formie:dragging-inactive');

            // Is this a pill? If so, we need to insert
            const isPill = (data.trigger === 'pill');
            const columnIndex = event.target.getAttribute('data-column');
            const rowIndex = event.target.getAttribute('data-row');

            if (isPill) {
                this.addColumn(columnIndex, data.type);
            } else {
                const sourceRowIndex = data.rowIndex;
                const sourceColumnIndex = data.columnIndex;

                this.moveColumn(sourceRowIndex, sourceColumnIndex, rowIndex, columnIndex);
            }
        },

        canDrag(data) {
            return canDrag(this.pageIndex, this.sourceField, data);
        },

        toggleDropzone(event, state) {
            if (event.target === this.$refs.dropzoneLeft.$el) {
                this.dropzoneLeftHover = state;
            } else if (event.target === this.$refs.dropzoneRight.$el) {
                this.dropzoneRightHover = state;
            }
        },

        addColumn(columnIndex, type) {
            const newColumns = 12 / (this.$parent.fields.length + 1);

            const newField = this.$store.getters['fieldtypes/newField'](type, {
                columnWidth: newColumns,
                brandNewField: true,
                isNested: this.field.isNested,
            });

            const payload = {
                rowIndex: this.rowIndex,
                data: newField,
                columnIndex,
            };

            if (this.fieldId) {
                payload.fieldId = this.fieldId;
            } else {
                payload.pageIndex = this.pageIndex;
            }

            this.$store.dispatch('form/addColumn', payload);
        },

        moveColumn(sourceRowIndex, sourceColumnIndex, rowIndex, columnIndex) {
            const payload = {
                sourceRowIndex,
                sourceColumnIndex,
                rowIndex,
                columnIndex,
            };

            if (this.fieldId) {
                payload.fieldId = this.fieldId;
            } else {
                payload.pageIndex = this.pageIndex;
            }

            this.$store.dispatch('form/moveColumn', payload);
        },

        markAsSaved() {
            // Update the state of Vuex to mark the field as no longer brand-new
            // Used for when a brand-new field is saved for the first time
            const payload = {
                rowIndex: this.rowIndex,
                columnIndex: this.columnIndex,
                prop: 'brandNewField',
                value: false,
            };

            if (this.fieldId) {
                payload.fieldId = this.fieldId;
            } else {
                payload.pageIndex = this.pageIndex;
            }

            this.$store.dispatch('form/setFieldProp', payload);
        },

        markAsError(error) {
            const payload = {
                rowIndex: this.rowIndex,
                columnIndex: this.columnIndex,
                prop: 'hasError',
                value: error,
            };

            if (this.fieldId) {
                payload.fieldId = this.fieldId;
            } else {
                payload.pageIndex = this.pageIndex;
            }

            // This payload is for updating the field status, particularly to deal with validation triggering on
            // various items. This is called in the modal when editing a form, and when saving the overall form.
            this.$store.dispatch('form/setFieldProp', payload);
        },
    },

};

</script>
