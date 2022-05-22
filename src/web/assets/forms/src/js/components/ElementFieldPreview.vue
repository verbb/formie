<template>
    <div>
        <div v-if="field.settings.displayType == 'dropdown'">
            <select class="fui-field-select" :multiple="field.settings.multiple">
                <option value="">{{ defaultValue.label || field.settings.placeholder }}</option>

                <option v-for="(option, index) in options" :key="index" :selected="defaultValue.value === option.value ? true : false">
                    {{ option.label }}
                </option>
            </select>
        </div>

        <div v-if="field.settings.displayType == 'checkboxes'">
            <div v-for="(option, index) in options" :key="index" class="fui-field-checkbox">
                <input :value="option.value" type="checkbox" :checked="defaultValue.value === option.value ? true : false">
                <label>{{ option.label }}</label>
            </div>

            <div v-if="total > options.length" class="fui-field-instructions">... {{ total - options.length }} {{ t('formie', 'more') }}</div>
        </div>

        <div v-if="field.settings.displayType == 'radio'">
            <div v-for="(option, index) in options" :key="index" class="fui-field-radio">
                <input :value="option.value" type="radio" :checked="defaultValue.value === option.value ? true : false">
                <label>{{ option.label }}</label>
            </div>

            <div v-if="total > options.length" class="fui-field-instructions">... {{ total - options.length }} {{ t('formie', 'more') }}</div>
        </div>
    </div>
</template>

<script>

import { debounce } from 'lodash-es';

export default {
    name: 'ElementFieldPreview',

    props: {
        field: {
            type: Object,
            required: true,
        },
    },

    data() {
        return {
            total: null,
            options: [],
        };
    },

    computed: {
        sources() {
            return this.field.settings.sources;
        },

        source() {
            return this.field.settings.source;
        },

        displayType() {
            return this.field.settings.displayType;
        },

        defaultValue() {
            if (this.field.defaultValueOptions) {
                if (this.field.defaultValueOptions.length && this.field.defaultValueOptions[0]) {
                    return this.field.defaultValueOptions[0];
                }
            }

            return {};
        },
    },

    watch: {
        // Wait for the user to pick a few checkboxes for sources
        sources: debounce(function(newValue) {
            this.updateSources();
        }, 2000),

        source: debounce(function(newValue) {
            this.updateSources();
        }, 2000),

        displayType(newValue) {
            this.updateSources();
        },
    },

    created() {
        // If no elements fetch them. Likely when cloning an existing field, but because we don't
        // store the elements in field settings, we have to re-fetch them on-demand.
        if (this.field.elements) {
            this.options = this.field.elements.options;
            this.total = this.field.elements.total;
        } else {
            this.updateSources();
        }
    },

    methods: {
        updateSources() {
            if (this.field.settings.displayType === 'dropdown') {
                return;
            }

            const data = { field: this.field };

            Craft.sendActionRequest('POST', 'formie/fields/get-element-select-options', { data }).then((response) => {
                this.options = response.data.options;
                this.total = response.data.total;
            });
        },
    },
};

</script>
