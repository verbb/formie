<template>
    <div class="input">
        <input
            v-model="context.model"
            type="text"
            v-bind="context.attributes"
            class="text fullwidth code"
            v-on="$listeners"
            @blur="context.blurHandler"
        >

        <div class="fui-field-handle-generate-icon" :style="{ transform: 'translateY(-50%) rotate(' + rotate + 'deg)'}" @click.prevent="generateHandle">
            <svg aria-hidden="true" focusable="false" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M440.65 12.57l4 82.77A247.16 247.16 0 0 0 255.83 8C134.73 8 33.91 94.92 12.29 209.82A12 12 0 0 0 24.09 224h49.05a12 12 0 0 0 11.67-9.26 175.91 175.91 0 0 1 317-56.94l-101.46-4.86a12 12 0 0 0-12.57 12v47.41a12 12 0 0 0 12 12H500a12 12 0 0 0 12-12V12a12 12 0 0 0-12-12h-47.37a12 12 0 0 0-11.98 12.57zM255.83 432a175.61 175.61 0 0 1-146-77.8l101.8 4.87a12 12 0 0 0 12.57-12v-47.4a12 12 0 0 0-12-12H12a12 12 0 0 0-12 12V500a12 12 0 0 0 12 12h47.35a12 12 0 0 0 12-12.6l-4.15-82.57A247.17 247.17 0 0 0 255.83 504c121.11 0 221.93-86.92 243.55-201.82a12 12 0 0 0-11.8-14.18h-49.05a12 12 0 0 0-11.67 9.26A175.86 175.86 0 0 1 255.83 432z" /></svg>
        </div>
    </div>
</template>

<script>
import FormulateInputMixin from '@braid/vue-formulate/src/FormulateInputMixin';

// eslint-disable-next-line
import { generateHandle, getNextAvailableHandle } from '@utils/string';

export default {
    name: 'HandleField',

    mixins: [FormulateInputMixin],

    props: {
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
            default: () => [],
        },
    },

    data() {
        return {
            savedValue: '',
            rotate: 0,
        };
    },

    computed: {
        proxySourceValue() {
            if (this.$editingField) {
                return this.$editingField.field.label;
            }

            if (this.context.slotProps.label.sourceValue !== undefined) {
                return this.context.slotProps.label.sourceValue;
            }

            return this.sourceValue;
        },

        proxyFieldId() {
            if (this.$editingField) {
                return this.$editingField.field.vid;
            }

            if (this.context.slotProps.label.fieldId !== undefined) {
                return this.context.slotProps.label.fieldId;
            }

            return this.fieldId;
        },

        proxyCollection() {
            if (this.context.slotProps.label.collection !== undefined) {
                return this.context.slotProps.label.collection;
            }

            return this.collection;
        },
    },

    watch: {
        proxySourceValue(newValue) {
            // We only care when there's not a handle set
            if (this.savedValue === '') {
                this.generateHandle();
            }
        },
    },

    created() {
        // Save the original, persisted value for the handle, so we can see if we should
        // be updating with a generating handle or not
        this.savedValue = this.context.model;
    },

    methods: {
        generateHandle(e) {
            // When hitting the button...
            if (e) {
                this.rotate = this.rotate + 180;
            }

            // For Repeater/Groups, we need to fetch handles from the parent field for the nested field
            let parentFieldId = (this.$editingField && this.$editingField.parentFieldId) ? this.$editingField.parentFieldId : null;

            // Let's get smart about generating a handle. Check if its unqique - if it isn't, make it unique
            const generatedHandle = generateHandle(this.proxySourceValue);
            let handles = this.$store.getters['form/fieldHandlesExcluding'](this.proxyFieldId, parentFieldId);

            if (this.proxyCollection.length) {
                handles = this.proxyCollection;
            }

            // Be sure to restrict handles well below their limit
            const value = getNextAvailableHandle(handles, generatedHandle, 0);
            this.context.model = value.substr(0, 50);
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
