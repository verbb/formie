<template>
    <tr>
        <template v-for="(col, i) in columns">
            <td v-if="col.type === 'optgroup'" :key="i" :class="col.class">
                <div class="checkbox-wrapper">
                    <input :id="'isOptgroup-' + index" v-model="model.isOptgroup" type="checkbox" class="checkbox">
                    <label :for="'isOptgroup-' + index"></label>
                </div>
            </td>

            <td v-if="col.type === 'label'" :key="i" :class="[col.class, { error: getError('label', model[labelName]) }]">
                <input
                    v-model="model[labelName]"
                    type="text"
                    class="text fullwidth"
                    autocomplete="off"
                    @input="labelUpdate"
                    @blur="labelBlur = true"
                >
            </td>

            <td v-if="col.type === 'value'" :key="i" :class="[col.class, { disabled: model.isOptgroup, error: getError('value', model[valueName]) }]">
                <input
                    v-model="model[valueName]"
                    type="text"
                    class="text fullwidth"
                    autocomplete="off"
                    :disabled="model.isOptgroup"
                    @input="valueUpdate"
                    @blur="valueBlur = true"
                >
            </td>

            <td v-if="col.type === 'default'" :key="i" :class="[col.class, { disabled: model.isOptgroup }]">
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

            <td v-if="col.type === 'width'" :key="i" :class="col.class" :width="col.width">
                <input v-model="model.width" type="text" class="text fullwidth" autocomplete="off">
            </td>

            <td v-if="col.type === 'type'" :key="i" :class="col.class">
                <div class="flex flex-nowrap">
                    <div class="select small">
                        <select v-model="model.type">
                            <option v-for="(option, j) in typeOptions" :key="j" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>

                    <a class="settings light" :class="{ 'hidden': model.type !== 'select' }" role="button" data-icon="settings" @click.prevent="openModal"></a>

                    <table-dropdown
                        v-if="modalActive"
                        ref="editFieldModal"
                        :visible="modalVisible"
                        @close="onModalClose"
                    />
                </div>
            </td>

            <!-- //////////////////////// -->
            <!-- Fieldtypes -->
            <!-- //////////////////////// -->
            <td v-if="col.type === 'checkbox'" :key="i" class="checkbox-cell" :width="col.width">
                <div class="checkbox-wrapper">
                    <input :id="col.handle + '-' + index" v-model="model[col.id]" type="checkbox" class="checkbox">
                    <label :for="col.handle + '-' + index"></label>
                </div>
            </td>

            <td v-if="col.type === 'color'" :key="i" class="color-cell textual code" :width="col.width">
                <div class="flex color-container">
                    <div class="color small">
                        <div class="color-preview">
                            <input type="color" class="color-preview-input">
                        </div>
                    </div>

                    <input v-model="model[col.id]" type="text" class="text color-input" size="10" autocomplete="off">
                </div>
            </td>

            <td v-if="col.type === 'date'" :key="i" class="date-cell textual" :width="col.width">
                <div class="datewrapper">
                    <input
                        v-model="model[col.id]"
                        type="text"
                        class="text hasDatepicker"
                        size="10"
                        autocomplete="off"
                        placeholder=" "
                    >
                    <div data-icon="date"></div>
                </div>
            </td>

            <td v-if="col.type === 'email'" :key="i" class="email-cell textual" :width="col.width">
                <input v-model="model[col.id]" type="email" class="text fullwidth" autocomplete="off">
            </td>

            <td v-if="col.type === 'heading'" :key="i" class="" :width="col.width">
                <input v-model="model[col.id]" type="text" class="text fullwidth" autocomplete="off">
            </td>

            <td v-if="col.type === 'lightswitch'" :key="i" class="lightswitch-cell" :width="col.width">
                <div class="lightswitch small" tabindex="0" role="checkbox" aria-checked="false">
                    <div class="lightswitch-container">
                        <div class="handle"></div>
                    </div>
                    <input v-model="model[col.id]" type="hidden">
                </div>
            </td>

            <td v-if="col.type === 'multiline'" :key="i" class="multiline-cell textual" :width="col.width">
                <textarea v-model="model[col.id]" rows="1" style="min-height: 36px;"></textarea>
            </td>

            <td v-if="col.type === 'number'" :key="i" class="number-cell textual" :width="col.width">
                <input v-model="model[col.id]" type="number" class="text fullwidth" autocomplete="off">
            </td>

            <td v-if="col.type === 'time'" :key="i" class="time-cell textual" :width="col.width">
                <div class="timewrapper">
                    <input
                        v-model="model[col.id]"
                        type="text"
                        class="text ui-timepicker-input"
                        size="10"
                        autocomplete="off"
                        placeholder=" "
                    >
                    <div data-icon="time"></div>
                </div>
            </td>

            <td v-if="col.type === 'select'" :key="i" class="select-cell" :width="col.width">
                <div class="select small">
                    <select v-model="model[col.id]">
                        <option v-for="(option, optIndex) in col.options" :key="optIndex" :value="option.value">{{ option.label }}</option>
                    </select>
                </div>
            </td>

            <td v-if="col.type === 'singleline'" :key="i" class="singleline-cell textual" :width="col.width">
                <input v-model="model[col.id]" type="text" class="text fullwidth" autocomplete="off">
            </td>

            <td v-if="col.type === 'url'" :key="i" class="url-cell textual " :width="col.width">
                <input v-model="model[col.id]" type="url" class="text fullwidth" autocomplete="off">
            </td>
        </template>

        <td class="thin action">
            <a class="move icon" :title="'Reorder' | t('formie')" role="button"></a>
        </td>

        <td class="thin action">
            <a class="delete icon" :title="'Delete' | t('formie')" role="button" @click.prevent="removeItem"></a>
        </td>
    </tr>
</template>

<script>
import get from 'lodash/get';

import { parse } from '../../../utils/conditionals';
import { generateHandle } from '../../../utils/string';
import TableDropdown from './TableDropdown.vue';

export default {
    name: 'TableRow',

    components: {
        TableDropdown,
    },

    inject: ['labelsWithError', 'valuesWithError'],

    props: {
        context: {
            type: Object,
            default: () => {},
        },

        index: {
            type: Number,
            default: 0,
        },

        removeItem: {
            type: Function,
            default: () => {},
        },
    },

    data() {
        return {
            modalActive: false,
            modalVisible: false,
            labelBlur: false,
            valueBlur: false,
        };
    },

    computed: {
        model() {
            return this.context.model[this.index] || {};
        },

        columns() {
            const columns = this._getSlotProp('columns');

            if (typeof columns === 'string') {
                if (this.$editingField) {
                    return get(this.$editingField.field, columns);
                }
            }

            if (columns !== undefined) {
                return columns;
            }

            return [];
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
            const attribute = this._getSlotProp('allowMultipleDefault', true);
            return parse(attribute, this.$editingField ? this.$editingField.field : null);
        },

        generateHandle() {
            return this._getSlotProp('generateHandle', false);
        },

        generateValue() {
            return this._getSlotProp('generateValue', true);
        },

        hasDefault() {
            let hasDefault = false;

            if (!this.allowMultipleDefault) {
                this.context.model.filter((row, i) => {
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

    watch: {
        allowMultipleDefault(newValue, oldValue) {
            this.$nextTick(() => {
                // Reset all default values to false (except the first option) if going
                // from multiple options to single to avoid locking all the options out.
                if (!newValue && oldValue) {
                    let first = true;

                    this.context.model.forEach(row => {
                        if (row[this.defaultName]) {
                            if (!first) {
                                row[this.defaultName] = false;
                            }

                            first = false;
                        }
                    });
                }
            });
        },
    },

    methods: {
        onModalClose() {
            this.modalActive = false;
            this.modalVisible = false;
        },

        openModal() {
            this.modalActive = true;
            this.modalVisible = true;
        },

        _getSlotProp(prop, fallback) {
            if (this.context.slotProps.repeatable[prop] !== undefined) {
                return this.context.slotProps.repeatable[prop];
            }

            return fallback;
        },

        _getName(type, fallback) {
            const name = this.columns.find(o => o.type === type);

            if (name && name.name) {
                return name.name;
            }

            return fallback;
        },

        getError(field, value) {
            if (field === 'label' && this.labelBlur) {
                return this.labelsWithError.includes(value);
            } else if (field === 'value' && this.valueBlur) {
                return this.valuesWithError.includes(value);
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

td .field[data-type="text"] {
    &[data-is-showing-errors="true"] {
        box-shadow: inset 0 0 0 1px #EF4E4E;
    }

    .errors {
        display: none;
    }
}

</style>
