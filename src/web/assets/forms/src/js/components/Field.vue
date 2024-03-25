<template>
    <div :class="'fui-col-' + columnWidth" style="display: flex;">
        <div v-show="fieldIndex === 0 && showDropzones" class="form-field-drop-target" :class="{ 'is-active': dropzonesActive, 'is-hover': dropzoneLeftHover }">
            <div class="dropzone-holder">
                <drop
                    ref="dropzoneLeft"
                    class="form-field-dropzone form-field-dropzone-vertical"
                    :data-field="fieldIndex"
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
            :class="{ 'is-active': dragActive, 'has-errors': hasError }"
            :transfer-data="{
                trigger: 'field',
                hasNestedFields: fieldtype.hasNestedFields,
                fieldId: field.__id,
            }"
            :hide-image-html="!isSafari"
            @on-dragstart="dragStart"
            @on-dragend="dragEnd"
        >
            <div v-if="fieldtype.hasEditableFields" class="fui-edit-overlay" @click.prevent="openModal"></div>

            <div class="fui-field-info">
                <label v-if="fieldtype.hasLabel" class="fui-field-label">
                    <span v-if="field.settings.label && field.settings.label.length">{{ field.settings.label }}</span>
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

                <code class="fui-field-handle">{{ field.settings.handle }}</code>

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

            <field-preview :id="field.__id" class="fui-field-preview" :class="`fui-type-${nameKebab}`" :expected-type="expectedType" />

            <markdown v-if="fieldtype.data.warning" class="warning with-icon" :source="fieldtype.data.warning" />

            <field-edit-modal
                v-if="showModal"
                v-model:showModal="showModal"
                :field="field"
                :field-ref="this"
                :fields-schema="fieldsSchema"
                :tabs-schema="tabsSchema"
                @update:field="field = $event"
                @delete="deleteField"
                @closed="onModalClosed"
            />

            <template v-if="!isSafari" #image>
                <div class="fui-field-pill" style="width: 148px;">
                    <span class="fui-field-pill-icon" v-html="fieldtype.icon"></span>

                    <span class="fui-field-pill-name">{{ field.settings.label }}</span>
                    <span class="fui-field-pill-drag"></span>
                </div>
            </template>
        </drag>

        <div v-show="showDropzones" class="form-field-drop-target" :class="{ 'is-active': dropzonesActive, 'is-hover': dropzoneRightHover }">
            <div class="dropzone-holder">
                <drop
                    ref="dropzoneRight"
                    class="form-field-dropzone form-field-dropzone-vertical"
                    :data-field="fieldIndex + 1"
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
import { Drag, Drop } from '@vendor/vue-drag-drop';
import { cloneDeep, isEmpty } from 'lodash-es';

// eslint-disable-next-line
import { generateHandle, getNextAvailableHandle, generateKebab, getDisplayName, newId } from '@utils/string';
import { isSafari } from '@utils/browser';
import { clonedFieldSettings } from '@utils/fields';

import FieldEditModal from '@components/FieldEditModal.vue';
import FieldPreview from '@components/FieldPreview.vue';
import FieldDropdown from '@components/FieldDropdown.vue';
import Markdown from '@components/Markdown.vue';

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

        pageIndex: {
            type: Number,
            default: null,
        },

        rowIndex: {
            type: Number,
            default: 0,
        },

        fieldIndex: {
            type: Number,
            default: 0,
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

        displayName() {
            return getDisplayName(this.field.type);
        },

        nameKebab() {
            return generateKebab(this.displayName);
        },

        fieldHandles() {
            return this.$store.getters['form/fieldHandlesExcluding'](this.field.__id, this.parentFieldId);
        },

        fieldCanRequire() {
            const disallowedFields = {
                'verbb\\formie\\fields\\Address': false,
                'verbb\\formie\\fields\\Heading': false,
                'verbb\\formie\\fields\\Hidden': false,
                'verbb\\formie\\fields\\Html': false,
                'verbb\\formie\\fields\\Repeater': false,
                'verbb\\formie\\fields\\Section': false,
                'verbb\\formie\\fields\\Name': (field) => {
                    return !field.settings.useMultipleFields;
                },
            };

            // TODO: Probably refactor this to PHP
            const disallowedField = disallowedFields[this.field.type];
            if (typeof disallowedField === 'boolean') {
                return disallowedField;
            } if (typeof disallowedField === 'function') {
                const field = this.$store.getters['form/field'](this.field.__id);
                return disallowedField(field);
            }

            return true;
        },

        fieldsSchema() {
            return this.fieldtype.schema.fields;
        },

        tabsSchema() {
            return this.fieldtype.schema.tabs;
        },

        hasError() {
            return !isEmpty(this.field.errors);
        },
    },

    created() {
        this.$events.on('formie:dragging-active', this.draggingActive);
        this.$events.on('formie:dragging-inactive', this.draggingInactive);

        // Open the modal immediately for brand new fields
        if (this.brandNewField) {
            this.openModal();

            // Testing
            // this.field.settings.label = this.fieldtype.label;
            // this.field.settings.handle = generateHandle(this.fieldtype.label);
        }
    },

    mounted() {
        // Testing
        // if (this.$parent.$parent.pageIndex == 0 && this.$parent.rowIndex == 0 && this.fieldIndex == 0) {
        //     this.openModal();
        // }
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
                this.$store.dispatch('form/deleteField', { id: this.field.__id });
            }
        },

        requireField() {
            this.field.settings.required = true;
        },

        unrequireField() {
            this.field.settings.required = false;
        },

        cloneField() {
            // Let's get smart about generating a handle. Check if its unique - if it isn't, make it unique
            const generatedHandle = generateHandle(this.field.settings.label);
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

            const config = {
                settings: clonedFieldSettings(this.field),
            };

            config.settings.label = this.field.settings.label;
            config.settings.handle = newHandle;

            const newField = this.$store.getters['fieldtypes/newField'](this.field.type, config);

            const newRow = {
                __id: newId(),
                fields: [newField],
            };

            // Get this current field's key path, and bubble up twice to `rows`, then place immediately after this field
            const destinationPath = this.$store.getters['form/keyPath'](this.field.__id);
            destinationPath.splice(-3);
            destinationPath.push(this.rowIndex + 1);

            this.$store.dispatch('form/addField', {
                destinationPath,
                value: newRow,
            });
        },

        deleteField() {
            const name = this.field.settings.label || this.fieldtype.label;

            const confirmationMessage = Craft.t('formie', 'Are you sure you want to delete “{name}”?', { name });

            if (confirm(confirmationMessage)) {
                this.$store.dispatch('form/deleteField', { id: this.field.__id });
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
            const fieldIndex = event.target.getAttribute('data-field');

            if (isPill) {
                const fieldtype = this.$store.getters['fieldtypes/fieldtype'](data.type);

                this.addField(fieldIndex, fieldtype.type);
            } else {
                this.moveField(fieldIndex, data.fieldId);
            }
        },

        canDrag(data) {
            return !(data.hasNestedFields && this.field.isNested);
        },

        toggleDropzone(event, state) {
            if (event.target === this.$refs.dropzoneLeft.$el) {
                this.dropzoneLeftHover = state;
            } else if (event.target === this.$refs.dropzoneRight.$el) {
                this.dropzoneRightHover = state;
            }
        },

        addField(fieldIndex, type) {
            // Get the path to _this_ row, which is close to where we want to insert the new row
            const destinationPath = this.$store.getters['form/parentKeyPath'](this.field.__id, [fieldIndex]);

            const newField = this.$store.getters['fieldtypes/newField'](type, {
                brandNewField: true,
            });

            this.$store.dispatch('form/addField', {
                destinationPath,
                value: newField,
            });
        },

        moveField(fieldIndex, fieldId) {
            // Get the source field to move
            const sourcePath = this.$store.getters['form/keyPath'](fieldId);

            // Get the path to _this_ row, which is close to where we want to insert the new row
            const destinationPath = this.$store.getters['form/parentKeyPath'](this.field.__id, [fieldIndex]);

            // Get the parent `rows` so that we can insert it at the index
            const fieldToMove = this.$store.getters['form/valueByKeyPath'](sourcePath);

            this.$store.dispatch('form/moveField', {
                sourcePath,
                destinationPath,
                value: fieldToMove,
            });
        },

        markAsSaved() {
            this.field.brandNewField = false;
        },

        markAsError(error) {
            this.field.hasError = error;
        },
    },

};

</script>
