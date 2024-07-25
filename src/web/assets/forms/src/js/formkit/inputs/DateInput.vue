<template>
    <div>
        <div class="datewrapper">
            <input
                ref="dateInput"
                size="10"
                autocomplete="off"
                class="text"
                placeholder=" "
                v-bind="context.attrs"
                @blur="context.handlers.blur"
            >

            <div data-icon="date"></div>
        </div>

        <div v-show="includeTime" class="timewrapper">
            <input
                ref="timeInput"
                size="10"
                autocomplete="off"
                class="text"
                placeholder=" "
                v-bind="context.attrs"
                @blur="context.handlers.blur"
            >

            <div data-icon="time"></div>
        </div>
    </div>
</template>

<script>
import { mapState } from 'vuex';

import { parseDate } from '@utils/string';

export default {
    props: {
        context: {
            type: Object,
            default: () => {},
        },
    },

    data() {
        return {
            savedDate: null,
            proxyValue: {
                date: '',
                time: '',
            },
        };
    },

    computed: {
        ...mapState({
            editingField: (state) => { return state.formie.editingField; },
        }),

        includeTime() {
            if (this.editingField) {
                return this.editingField.field.settings.includeTime;
            }

            return false;
        },
    },

    created() {
        // Store to populate the picker fields
        this.savedDate = parseDate(this.clone(this.context._value));
    },

    mounted() {
        const { dateInput, timeInput } = this.$refs;

        // Init jQuery datepicker
        if (dateInput) {
            this.$datePicker = $(dateInput).datepicker($.extend({}, Craft.datepickerOptions));

            this.$datePicker.on('change', (e) => {
                // Construct the value as an ISO-string, as the `e.target.value` will be localised
                const datepickerDate = this.$datePicker.data('datepicker');

                if (e.target.value && datepickerDate) {
                    const year = datepickerDate.selectedYear;
                    const month = String(datepickerDate.selectedMonth + 1).padStart(2, '0');
                    const day = String(datepickerDate.selectedDay).padStart(2, '0');

                    this.proxyValue.date = `${year}-${month}-${day}`;

                    this.context.node.input(this.proxyValue);
                }
            });

            if (this.savedDate) {
                this.$datePicker.datepicker('setDate', new Date(parseDate(this.savedDate)));
            }

            // Trigger a change now to update the model
            this.$datePicker.trigger('change');
        }

        // The time input is always available so we have a ref - but is hidden
        if (timeInput) {
            this.$timePicker = $(timeInput).timepicker($.extend({}, Craft.timepickerOptions));

            this.$timePicker.on('change', (e) => {
                // Convert the date to ISO-string
                const timePickerDate = this.$timePicker.timepicker('getTime');

                if (e.target.value && timePickerDate) {
                    const hours = String(timePickerDate.getHours()).padStart(2, '0');
                    const minutes = String(timePickerDate.getMinutes()).padStart(2, '0');
                    const seconds = String(timePickerDate.getSeconds()).padStart(2, '0');

                    this.proxyValue.time = `${hours}:${minutes}:${seconds}`;

                    this.context.node.input(this.proxyValue);
                }
            });

            if (this.savedDate) {
                this.$timePicker.timepicker('setTime', new Date(parseDate(this.savedDate)));
            }

            // Trigger a change now to update the model
            this.$timePicker.trigger('change');
        }
    },
};

</script>
