<template>
    <drop class="dropzone-new-field" :class="{ 'is-active': dropzonesActive, 'is-hover': dropzoneHover }" @on-drop="dragDrop" @on-dragenter="dragEnter" @on-dragleave="dragLeave">
        <span>{{ t('formie', 'Drag and drop a field here') }}</span>
    </drop>
</template>

<script>
import { newId } from '@utils/string';
import { canDrag } from '@utils/drag-drop';
import { Drop } from '@vendor/vue-drag-drop';

export default {
    name: 'DropzoneNewField',

    components: {
        Drop,
    },

    props: {
        pageIndex: {
            type: Number,
            default: -1,
        },

        fieldId: {
            type: [String, Number],
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
            dropzoneHover: false,
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
            this.dropzoneHover = false;
        },

        dragEnter(data, event) {
            // Protect against anything being dragged in
            if (!data) {
                return;
            }

            if (this.canDrag(data)) {
                this.dropzoneHover = true;
            }
        },

        dragLeave() {
            this.dropzoneHover = false;
        },

        dragDrop(data, event) {
            // Protect against anything being dragged in
            if (!data) {
                return;
            }

            // Reset the state
            this.$events.emit('formie:dragging-inactive');

            if (!this.canDrag(data)) {
                return;
            }

            const newField = this.$store.getters['fieldtypes/newField'](data.type, {
                brandNewField: true,
            });

            const payload = {
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

        canDrag(data) {
            return canDrag(this.pageIndex, this.sourceField, data);
        },
    },
};

</script>
