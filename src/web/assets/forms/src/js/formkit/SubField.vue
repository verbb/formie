<template>
    <div class="fui-subfield-col">
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
            class="fui-subfield-block"
            :class="{ 'is-active': dragActive }"
            :transfer-data="{
                trigger: 'field',
                rowIndex,
                fieldIndex,
            }"
            :hide-image-html="!isSafari"
            @on-dragstart="dragStart"
            @on-dragend="dragEnd"
        >
            <div v-if="field.type === 'verbb\\formie\\fields\\MissingField'" class="fui-subfield-content is-missing">
                <div class="fui-subfield-content-title">{{ field.settings.label }}</div>
                <div class="fui-subfield-content-error">{{ field.settings.errorMessage }}</div>
            </div>

            <div v-else class="fui-subfield-content">
                <FormKit
                    id="enabled"
                    v-model="field.settings.enabled"
                    type="lightswitch"
                    :extra-small="true"
                    name="enabled"
                    :ignore="true"
                />

                <div class="fui-subfield-overlay" @click.prevent="openModal"></div>

                <span class="fui-subfield-content-title">{{ field.settings.label }}</span>

                <span v-if="field.settings.required" class="error">&nbsp;*</span>

                <field-dropdown
                    :can-edit="true"
                    :can-require="true"
                    :can-clone="false"
                    :can-delete="false"
                    :is-required="field.settings.required"
                    @edit="openModal"
                    @require="requireField"
                    @unrequire="unrequireField"
                />
            </div>

            <field-edit-modal
                v-if="showModal"
                v-model:showModal="showModal"
                :field="field"
                :field-ref="this"
                :fields-schema="fieldsSchema"
                :tabs-schema="tabsSchema"
                class="fui-edit-subfield-modal"
                :can-delete="false"
                :ignore-form="true"
                :is-sub-field="true"
                @update:field="field = $event"
                @closed="onModalClosed"
            />

            <template v-if="!isSafari" #image>
                <div class="fui-subfield-block ghost" style="width: 148px;">
                    <div class="fui-subfield-content">
                        <span class="fui-subfield-content-title">{{ field.settings.label }}</span>
                    </div>
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

import { newId } from '@utils/string';
import { isSafari } from '@utils/browser';

import FieldEditModal from '@components/FieldEditModal.vue';
import FieldDropdown from '@components/FieldDropdown.vue';

export default {
    name: 'SubField',

    components: {
        FieldEditModal,
        FieldDropdown,
        Drag,
        Drop,
    },

    props: {
        field: {
            type: Object,
            default: () => {},
        },

        fieldIndex: {
            type: Number,
            default: 0,
        },
    },

    data() {
        return {
            dropzonesActive: false,
            dropzoneLeftHover: false,
            dropzoneRightHover: false,
            dragActive: false,
            showModal: false,
            isSafari: isSafari(),
        };
    },

    computed: {
        showDropzones() {
            return true;
        },

        fieldtype() {
            return this.$store.getters['fieldtypes/fieldtype'](this.field.type);
        },

        rowIndex() {
            return this.$parent.rowIndex;
        },

        fieldHandles() {
            return [];
        },

        fieldsSchema() {
            // Disable some things not allowed for sub-fields
            let { fields } = this.fieldtype.schema;

            // Limit most things for the moment
            const excludedNames = [
                'matchField',
                'includeInEmail',
                'uniqueValue',
                'handle',
                'enableContentEncryption',
            ];

            fields = fields.map((item) => {
                return {
                    ...item,
                    children: item.children.map((child) => {
                        return {
                            ...child,
                            children: child.children.filter((innerChild) => { return !excludedNames.includes(innerChild.name); }),
                        };
                    }).filter((child) => { return child.children.length > 0; }),
                };
            });

            return fields;
        },

        tabsSchema() {
            // Disable some things not allowed for sub-fields
            let { tabs } = this.fieldtype.schema;

            // Don't rely on labels, they could be translated
            tabs = tabs.filter((tab) => {
                if (tab.fields.includes('conditions')) {
                    return false;
                }

                return true;
            });

            return tabs;
        },
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
        },

        dragStart(data, event) {
            if (this.pageIndex < 0) {
                event.stopPropagation();
            }

            // Give it a second so that the z-index has a chance to bring the row dropzones into the forefront
            setTimeout(() => {
                // Emit event for dropzones
                this.$events.emit('formie:subfield-dragging-active', data, event);
            }, 50);

            this.dragActive = true;
        },

        dragEnd(data, event) {
            // Emit event for dropzones
            this.$events.emit('formie:subfield-dragging-inactive', data, event);
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
            this.$events.emit('formie:subfield-dragging-inactive');

            const fieldIndex = event.target.getAttribute('data-field');
            const rowIndex = event.target.getAttribute('data-row');
            const sourceRowIndex = data.rowIndex;
            const sourceFieldIndex = data.fieldIndex;

            this.$parent.$parent.moveField(sourceRowIndex, sourceFieldIndex, rowIndex, fieldIndex);
        },

        toggleDropzone(event, state) {
            if (event.target === this.$refs.dropzoneLeft.$el) {
                this.dropzoneLeftHover = state;
            } else if (event.target === this.$refs.dropzoneRight.$el) {
                this.dropzoneRightHover = state;
            }
        },


        requireField() {
            this.field.settings.required = true;
        },

        unrequireField() {
            this.field.settings.required = false;
        },
    },
};

</script>

<style lang="scss">

.fui-subfield-block {
    border-radius: 5px;
    border: 1px solid rgba(51, 64, 77, 0.1);
    background-color: #f3f7fc;
    width: 100%;
    margin: 10px;
    cursor: grab;
    position: relative;
    z-index: 2;
    user-select: none;

    background-color: #fff;
    border: none;
    box-shadow: 0 0 0 1px rgba(31,41,51,.1), 0 2px 5px -2px rgba(31,41,51,.2);

    &.ghost {
        margin: 0;
    }
}

.fui-subfield-content {
    position: relative;
    display: flex;
    align-items: center;
    padding: 8px;

    .field {
        margin: 0;
    }

    &.is-missing {
        flex-direction: column;
        align-items: flex-start;

        .fui-subfield-content-title {
            margin: 0;
        }

        .fui-subfield-content-error {
            color: var(--rose-500);
            font-size: 10px;
            line-height: 1.4;
        }
    }
}

.fui-subfield-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 0;
}

.fui-subfield-content .lightswitch {
    position: relative;
    z-index: 2;
    margin-right: 5px;
}

.fui-subfield-content-title {
    font-weight: 600;
    font-size: 14px;
    margin-left: 5px;
    color: #5f6c7b;
}

.fui-subfield-content {
    display: flex;
    margin-left: auto;

    .fui-field-actions {
        opacity: 1;
        position: relative;
        top: 0;
        left: 0;
    }

    button {
        height: var(--touch-target-size);
        padding: 0;
        width: var(--touch-target-size);
        background-color: transparent;

        &:hover {
            background-color: var(--ui-control-hover-bg-color);
        }
    }
}

</style>
