<template>
    <drop
        tag="li"
        :data-page="pageIndex"
        @on-drop="dragDrop"
        @on-dragenter="dragEnter"
        @on-dragleave="dragLeave"
    >
        <a
            class="fui-tab-item"
            :class="{ 'is-hover': dropzoneHover, 'sel': isActive, 'error': hasError }"
            :data-page="pageIndex"
            :href="hash"
            @click.prevent="selectTab"
        >
            <span>{{ label }}</span>
        </a>
    </drop>
</template>

<script>
import { mapState } from 'vuex';
import { Drop } from '@vendor/vue-drag-drop';
import { flattenDeep, isEmpty } from 'lodash-es';

import { newId } from '@utils/string';

export default {
    name: 'FieldPageTab',

    components: {
        Drop,
    },

    props: {
        pageIndex: {
            type: Number,
            default: 0,
        },

        label: {
            type: String,
            default: '',
        },

        active: {
            type: String,
            default: '',
        },
    },

    emits: ['selected'],

    data() {
        return {
            dropzoneHover: false,
        };
    },

    computed: {
        ...mapState({
            pages: (state) => { return state.form.pages; },
        }),

        page() {
            return this.pages[this.pageIndex];
        },

        hash() {
            return `#tab-fields-page-${this.pageIndex + 1}`;
        },

        isActive() {
            return this.hash === this.active;
        },

        hasError() {
            return !isEmpty(this.page.errors);
        },
    },

    mounted() {
        // Trigger the event if there's a history state
        if (document.location.hash === this.hash) {
            this.selectTab();
        }
    },

    methods: {
        selectTab(event) {
            this.$emit('selected', this.hash);

            this.$events.emit('formie:page-selected', this.pageIndex);
        },

        dragEnter(data, event) {
            // Protect against anything being dragged in
            // Only allow existing fields to be dropped
            if (!data || data.trigger !== 'field') {
                return;
            }

            this.dropzoneHover = true;
        },

        dragLeave(data, event) {
            this.dropzoneHover = false;
        },

        dragDrop(data, event) {
            // Protect against anything being dragged in
            // Only allow existing fields to be dropped
            if (!data || data.trigger !== 'field') {
                return;
            }

            // Reset the state
            this.$events.emit('formie:dragging-inactive');
            this.dropzoneHover = false;

            // Get the field key path
            const sourcePath = this.$store.getters['form/keyPath'](data.fieldId);
            const fieldToMove = this.$store.getters['form/valueByKeyPath'](sourcePath);

            // Construct the key path for the new page manually
            const destinationPath = ['pages', this.pageIndex, 'rows'];

            // Find the last row on the destination page and use that as the ID
            const destinationRows = this.$store.getters['form/valueByKeyPath'](destinationPath);

            // Figure out how to append it, by getting the last item index
            if (destinationRows && Array.isArray(destinationRows) && destinationRows.length) {
                destinationPath.push(destinationRows.length);
            } else {
                destinationPath.push(0);
            }

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
    },
};

</script>
