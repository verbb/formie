<template>
    <td v-if="column.type === 'optgroup'" :class="column.class">
        <div class="checkbox-wrapper">
            <input :id="'isOptgroup-' + index" v-model="model.isOptgroup" type="checkbox" class="checkbox">
            <label :for="'isOptgroup-' + index"></label>
        </div>
    </td>

    <td v-if="column.type === 'label'" :class="[column.class, { error: getError('label', model[labelName]) }]">
        <input
            v-model="model[labelName]"
            type="text"
            class="text fullwidth"
            autocomplete="off"
            @input="labelUpdate"
            @focus="labelBlur = false"
            @blur="labelBlur = true"
        >
    </td>

    <td v-if="column.type === 'value'" :class="[column.class, { disabled: model.isOptgroup, error: getError('value', model[valueName]) }]">
        <input
            v-model="model[valueName]"
            type="text"
            class="text fullwidth"
            autocomplete="off"
            :disabled="model.isOptgroup"
            @input="valueUpdate"
            @focus="valueBlur = false"
            @blur="valueBlur = true"
        >
    </td>

    <td v-if="column.type === 'default'" :class="[column.class, { disabled: model.isOptgroup }]">
        <div class="checkbox-wrapper">
            <input
                :id="'isDefault-' + index"
                v-model="model[defaultName]"
                type="checkbox"
                class="checkbox"
                :disabled="model.isOptgroup || !hasDefault"
            >
            <label :for="'isDefault-' + index"></label>
        </div>
    </td>

    <td v-if="column.type === 'disabled'" :class="column.class">
        <div class="checkbox-wrapper">
            <input
                :id="'disabled-' + index"
                v-model="model.disabled"
                type="checkbox"
                class="checkbox"
            >
            <label :for="'disabled-' + index"></label>
        </div>
    </td>

    <td v-if="column.type === 'width'" :class="column.class" :width="column.width">
        <input v-model="model.width" type="text" class="text fullwidth" autocomplete="off">
    </td>

    <td v-if="column.type === 'type'" :class="column.class">
        <div class="flex flex-nowrap">
            <div class="select small">
                <select v-model="model.type">
                    <option v-for="(option, j) in typeOptions" :key="j" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
            </div>

            <table-dropdown v-if="model.type === 'select'" :values="model" @update:values="context.node.input($event)" />
        </div>
    </td>

    <!-- //////////////////////// -->
    <!-- Fieldtypes -->
    <!-- //////////////////////// -->
    <td v-if="column.type === 'checkbox'" class="checkbox-cell" :width="column.width">
        <div class="checkbox-wrapper">
            <input :id="column.handle + '-' + index" v-model="model[column.id]" type="checkbox" class="checkbox">
            <label :for="column.handle + '-' + index"></label>
        </div>
    </td>

    <td v-if="column.type === 'color'" class="color-cell textual code" :width="column.width">
        <div class="flex color-container">
            <div class="color small">
                <div class="color-preview">
                    <input type="color" class="color-preview-input">
                </div>
            </div>

            <input v-model="model[column.id]" type="text" class="text color-input" size="10" autocomplete="off">
        </div>
    </td>

    <td v-if="column.type === 'date'" class="date-cell textual" :width="column.width">
        <div class="datewrapper">
            <input
                v-model="model[column.id]"
                type="text"
                class="text hasDatepicker"
                size="10"
                autocomplete="off"
                placeholder=" "
            >
            <div data-icon="date"></div>
        </div>
    </td>

    <td v-if="column.type === 'email'" class="email-cell textual" :width="column.width">
        <input v-model="model[column.id]" type="email" class="text fullwidth" autocomplete="off">
    </td>

    <td v-if="column.type === 'heading'" class="" :width="column.width">
        <input v-model="model[column.id]" type="text" class="text fullwidth" autocomplete="off">
    </td>

    <td v-if="column.type === 'lightswitch'" class="lightswitch-cell" :width="column.width">
        <div class="lightswitch small" tabindex="0" role="checkbox" aria-checked="false">
            <div class="lightswitch-container">
                <div class="handle"></div>
            </div>
            <input v-model="model[column.id]" type="hidden">
        </div>
    </td>

    <td v-if="column.type === 'multiline'" class="multiline-cell textual" :width="column.width">
        <textarea v-model="model[column.id]" rows="1" style="min-height: 36px;"></textarea>
    </td>

    <td v-if="column.type === 'number'" class="number-cell textual" :width="column.width">
        <input v-model="model[column.id]" type="number" class="text fullwidth" autocomplete="off">
    </td>

    <td v-if="column.type === 'time'" class="time-cell textual" :width="column.width">
        <div class="timewrapper">
            <input
                v-model="model[column.id]"
                type="text"
                class="text ui-timepicker-input"
                size="10"
                autocomplete="off"
                placeholder=" "
            >
            <div data-icon="time"></div>
        </div>
    </td>

    <td v-if="column.type === 'select'" class="select-cell" :width="column.width">
        <div class="select small">
            <select v-model="model[column.id]">
                <option v-for="(option, optIndex) in column.options" :key="optIndex" :value="option.value">{{ option.label }}</option>
            </select>
        </div>
    </td>

    <td v-if="column.type === 'singleline'" class="singleline-cell textual" :width="column.width">
        <input v-model="model[column.id]" type="text" class="text fullwidth" autocomplete="off">
    </td>

    <td v-if="column.type === 'url'" class="url-cell textual " :width="column.width">
        <input v-model="model[column.id]" type="url" class="text fullwidth" autocomplete="off">
    </td>
</template>

<script>
import { mapState } from 'vuex';
import { get } from 'lodash-es';
import { parse } from '@utils/conditionals';
import { generateHandle } from '@utils/string';

import TableDropdown from './TableDropdown.vue';

export default {
    name: 'TableCell',

    components: {
        TableDropdown,
    },

    props: {
        column: {
            type: Object,
            default: () => {},
        },

        context: {
            type: Object,
            default: () => {},
        },

        index: {
            type: Number,
            default: 0,
        },
    },

    data() {
        return {
            labelBlur: false,
            valueBlur: false,
        };
    },

    computed: {
        ...mapState({
            editingField: (state) => { return state.formie.editingField; },
        }),

        model() {
            return this.context._value;
        },

        tableField() {
            return this.context.node.parent.context;
        },

        columns() {
            return this.tableField.columns;
        },

        labelName() {
            return this._getName('label', 'label');
        },

        valueName() {
            return this._getName('value', 'value');
        },

        defaultName() {
            return this._getName('default', 'isDefault');
        },

        allowMultipleDefault() {
            const attribute = get(this.tableField, 'allowMultipleDefault', true);

            return parse(attribute, this.editingField ? this.editingField.field : null);
        },

        generateHandle() {
            return get(this.tableField, 'generateHandle', false);
        },

        generateValue() {
            return get(this.tableField, 'generateValue', true);
        },

        hasDefault() {
            let hasDefault = false;

            if (!this.allowMultipleDefault) {
                this.tableField._value.forEach((row, i) => {
                    if (row[this.defaultName] && i !== this.index) {
                        hasDefault = true;
                    }
                });
            }

            return !hasDefault;
        },

        typeOptions() {
            return [
                {
                    label: Craft.t('formie', 'Checkbox'),
                    value: 'checkbox',
                },
                {
                    label: Craft.t('formie', 'Color'),
                    value: 'color',
                },
                {
                    label: Craft.t('formie', 'Date'),
                    value: 'date',
                },
                {
                    label: Craft.t('formie', 'Dropdown'),
                    value: 'select',
                },
                {
                    label: Craft.t('formie', 'Email'),
                    value: 'email',
                },
                {
                    label: Craft.t('formie', 'Heading'),
                    value: 'heading',
                },
                {
                    label: Craft.t('formie', 'Multi-line Text'),
                    value: 'multiline',
                },
                {
                    label: Craft.t('formie', 'Number'),
                    value: 'number',
                },
                {
                    label: Craft.t('formie', 'Time'),
                    value: 'time',
                },
                {
                    label: Craft.t('formie', 'Single-line Text'),
                    value: 'singleline',
                },
                {
                    label: Craft.t('formie', 'URL'),
                    value: 'url',
                },
            ];
        },
    },

    methods: {
        _getName(type, fallback) {
            const name = this.tableField.columns.find((o) => { return o.type === type; });

            if (name && name.name) {
                return name.name;
            }

            return fallback;
        },

        getError(field, value) {
            const labelsWithError = this.tableField.labelsWithError || [];
            const valuesWithError = this.tableField.valuesWithError || [];

            if (field === 'label' && this.labelBlur) {
                return labelsWithError.includes(value);
            } if (field === 'value' && this.valueBlur) {
                return labelsWithError.includes(value);
            }

            return false;
        },

        labelUpdate() {
            if (this.model.isOptgroup) {
                return;
            }

            if (!this.generateValue) {
                return;
            }

            if (this.model.__isNew) {
                if (this.generateHandle) {
                    this.model[this.valueName] = generateHandle(this.model[this.labelName]);
                } else {
                    this.model[this.valueName] = this.model[this.labelName];
                }
            }
        },

        valueUpdate() {
            // If we reset the value field to an empty value, its then marked as new
            if (this.model[this.valueName].trim() === '') {
                this.model.__isNew = true;
            }
        },
    },
};

</script>

<style lang="scss">

td .focus-visible {
    z-index: 1;
    position: relative;
}

.singleline-cell {
    .text {
        border: 1px solid transparent !important;
    }

    .field[data-invalid="true"][data-submitted="true"] .text {
        border-color: var(--error-color) !important;
    }

    .errors {
        display: none;
    }
}

</style>
