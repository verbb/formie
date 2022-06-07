<template>
    <input
        v-model="context._value"
        :name="context.node.name"
        type="text"
        class="text fullwidth code"
        @input="context.handlers.DOMInput"
        @blur="context.handlers.blur"
    >

    <div class="fui-field-handle-generate-icon" :style="{ transform: 'translateY(-50%) rotate(' + rotate + 'deg)'}" @click.prevent="refreshHandle">
        <svg aria-hidden="true" focusable="false" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M440.65 12.57l4 82.77A247.16 247.16 0 0 0 255.83 8C134.73 8 33.91 94.92 12.29 209.82A12 12 0 0 0 24.09 224h49.05a12 12 0 0 0 11.67-9.26 175.91 175.91 0 0 1 317-56.94l-101.46-4.86a12 12 0 0 0-12.57 12v47.41a12 12 0 0 0 12 12H500a12 12 0 0 0 12-12V12a12 12 0 0 0-12-12h-47.37a12 12 0 0 0-11.98 12.57zM255.83 432a175.61 175.61 0 0 1-146-77.8l101.8 4.87a12 12 0 0 0 12.57-12v-47.4a12 12 0 0 0-12-12H12a12 12 0 0 0-12 12V500a12 12 0 0 0 12 12h47.35a12 12 0 0 0 12-12.6l-4.15-82.57A247.17 247.17 0 0 0 255.83 504c121.11 0 221.93-86.92 243.55-201.82a12 12 0 0 0-11.8-14.18h-49.05a12 12 0 0 0-11.67 9.26A175.86 175.86 0 0 1 255.83 432z" /></svg>
    </div>
</template>

<script>
import { get } from 'lodash-es';
import { mapState } from 'vuex';

// eslint-disable-next-line
import { generateHandle, getNextAvailableHandle } from '@utils/string';

export default {
    props: {
        context: {
            type: Object,
            default: () => {},
        },

        fieldId: {
            type: [String, Number],
            default: '',
        },

        sourceValue: {
            type: String,
            default: '',
        },

        collection: {
            type: Array,
            default: () => { return []; },
        },
    },

    data() {
        return {
            savedValue: '',
            rotate: 0,
        };
    },

    computed: {
        ...mapState({
            editingField: (state) => { return state.formie.editingField; },
        }),

        proxySourceValue() {
            if (this.editingField) {
                return this.editingField.field.label;
            }

            return get(this.context.attrs, 'source-value', this.sourceValue);
        },

        proxyFieldId() {
            if (this.editingField) {
                return this.editingField.field.vid;
            }

            return get(this.context.attrs, 'field-id', this.fieldId);
        },

        proxyCollection() {
            return get(this.context.attrs, 'collection', this.collection);
        },
    },

    watch: {
        proxySourceValue(newValue) {
            // We only care when there's not a handle set
            if (!this.savedValue) {
                this.generateHandle();
            }
        },
    },

    created() {
        // Save the original, persisted value for the handle, so we can see if we should
        // be updating with a generating handle or not
        this.savedValue = this.clone(this.context._value);
    },

    methods: {
        refreshHandle(e) {
            this.rotate = this.rotate + 180;

            this.generateHandle();
        },

        generateHandle() {
            // For Repeater/Groups, we need to fetch handles from the parent field for the nested field
            const parentFieldId = (this.editingField && this.editingField.parentFieldId) ? this.editingField.parentFieldId : null;

            if (!this.proxySourceValue) {
                return;
            }

            // Let's get smart about generating a handle. Check if its unqique - if it isn't, make it unique
            const generatedHandle = generateHandle(this.proxySourceValue);
            let handles = this.$store.getters['form/fieldHandlesExcluding'](this.proxyFieldId, parentFieldId);

            if (this.proxyCollection.length) {
                handles = this.proxyCollection;
            }

            // Be sure to restrict handles well below their limit
            const value = getNextAvailableHandle(handles, generatedHandle, 0);

            const maxHandleLength = this.$store.getters['formie/maxFieldHandleLength']();

            this.context.node.input(value.substr(0, maxHandleLength), false);
        },
    },
};

</script>

<style lang="scss">

.input-wrap {
    position: relative;
}

.fui-field-handle-generate-icon {
    position: absolute;
    top: 50%;
    right: 0;
    padding: 7px 10px;
    opacity: 0.5;
    cursor: pointer;
    color: #606d7b;
    transition: all 0.2s ease;
    user-select: none;

    svg {
        width: 14px;
        height: 14px;
        display: block;
    }

    &:hover {
        opacity: 1;
    }
}

</style>
