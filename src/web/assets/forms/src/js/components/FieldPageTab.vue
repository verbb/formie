<template>
    <drop
        tag="li"
        :data-page="pageIndex"
        @drop="dragDrop"
        @dragenter="dragEnter"
        @dragleave="dragLeave"
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
import { Drop } from 'vue-drag-drop';
import flattenDeep from 'lodash/flattenDeep';
import { newId } from '../utils/string';

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

    data() {
        return {
            dropzoneHover: false,
        };
    },

    computed: {
        ...mapState({
            pages: state => state.form.pages,
        }),

        hash() {
            return '#tab-fields-page-' + (this.pageIndex + 1);
        },

        isActive() {
            return this.hash === this.active;
        },

        hasError() {
            var hasError = false;

            var fields = this.$store.getters['form/fieldsForPage'](this.pageIndex);

            fields.forEach(field => {
                if (field.hasError) {
                    hasError = true;
                }
            });

            return hasError;
        },
    },

    mounted() {
        // Trigger the event if there's a history state
        if (document.location.hash === this.hash) {
            this.$emit('selected', this.hash);
        }
    },

    methods: {
        selectTab(event) {
            this.$emit('selected', this.hash);
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
            this.$events.$emit('formie:dragging-inactive');
            this.dropzoneHover = false;

            const pageIndex = event.target.getAttribute('data-page');
            const sourcePageIndex = data.pageIndex;
            const sourceRowIndex = data.rowIndex;
            const sourceColumnIndex = data.columnIndex;

            const payload = {
                pageIndex,
                sourcePageIndex,
                sourceRowIndex,
                sourceColumnIndex,
                data: {
                    id: newId(),
                },
            };

            this.$store.dispatch('form/appendRowToPage', payload);
        },
    },
};

</script>
