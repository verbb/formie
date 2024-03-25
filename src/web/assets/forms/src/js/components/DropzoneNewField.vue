<template>
    <drop class="dropzone-new-field" :class="{ 'is-active': dropzonesActive, 'is-hover': dropzoneHover }" @on-drop="dragDrop" @on-dragenter="dragEnter" @on-dragleave="dragLeave">
        <span>{{ t('formie', 'Drag and drop a field here') }}</span>
    </drop>
</template>

<script>
import { newId } from '@utils/string';
import { Drop } from '@vendor/vue-drag-drop';

export default {
    name: 'DropzoneNewField',

    components: {
        Drop,
    },

    props: {
        parentId: {
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
            dropzoneHover: false,
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
            this.dropzoneHover = false;
        },

        dragEnter(data, event) {
            // Nesting Group/Repeater fields aren't supported
            if (!data || !this.canDrag(data)) {
                return;
            }

            this.dropzoneHover = true;
        },

        dragLeave() {
            this.dropzoneHover = false;
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

                this.addRows(fieldtype.type);
            } else {
                this.moveRows(data.fieldId);
            }
        },

        addRows(type) {
            const newField = this.$store.getters['fieldtypes/newField'](type, {
                brandNewField: true,
            });

            const newRow = {
                __id: newId(),
                fields: [newField],
            };

            const destinationPath = this.$store.getters['form/keyPath'](this.parentId);

            // Check for Group/Repeater fields
            if (this.isNested) {
                destinationPath.push('settings');
            }

            // Add a new row to the path to set
            destinationPath.push(...['rows', '0']);

            this.$store.dispatch('form/addField', {
                destinationPath,
                value: newRow,
            });
        },

        moveRows(fieldId) {
            // Get the source field to move
            const sourcePath = this.$store.getters['form/keyPath'](fieldId);

            const destinationPath = this.$store.getters['form/keyPath'](this.parentId);

            // Check for Group/Repeater fields
            if (this.isNested) {
                destinationPath.push('settings');
            }

            // Add a new row to the path to set
            destinationPath.push(...['rows', '0']);

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
            return !(data.hasNestedFields && this.isNested);
        },
    },
};

</script>
