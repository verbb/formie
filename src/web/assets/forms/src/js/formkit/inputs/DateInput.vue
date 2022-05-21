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

    computed: {
        ...mapState({
            editingField: state => state.formie.editingField,
        }),

        includeTime() {
            if (this.editingField) {
                return this.editingField.field.settings.includeTime;
            }

            return false;
        },
    },

    created() {
        // Ensure this model is set to a reactive object, not a single value like other fields
        // this.context._value = Object.assign({}, this.context._value);
    },

    mounted() {
        const { dateInput, timeInput } = this.$refs;

        // Init jQuery datepicker
        if (dateInput) {
            this.$datePicker = $(dateInput).datepicker($.extend({
                altFormat: 'yy-mm-dd',
                onSelect: (dateText, inst) => {
                    var dateFormatted = inst.selectedYear + '-' + (inst.selectedMonth + 1) + '-' + inst.selectedDay;

                    this.context.node.input({
                        date: dateText,
                        jsDate: dateFormatted,
                        timezone: Craft.timezone,
                    });
                },
            }, Craft.datepickerOptions));

            this.$datePicker.on('change', (e) => {
                // Update the model if the date is removed
                if (!$(e.target).val()) {
                    this.context.node.input({
                        date: '',
                        jsDate: '',
                        timezone: Craft.timezone,
                    });
                }
            });

            if (this.context._value && this.context._value.date) {
                this.$datePicker.datepicker('setDate', new Date(parseDate(this.context._value)));
            }
        }

        // The time input is always available so we have a ref - but is hidden
        if (timeInput) {
            this.$timePicker = $(timeInput).timepicker($.extend({}, Craft.timepickerOptions));

            this.$timePicker.on('change', (e) => {
                this.context.node.input({
                    time: e.target.value,
                    timezone: Craft.timezone,
                });
            });

            if (this.context._value && this.context._value.date) {
                this.$timePicker.timepicker('setTime', new Date(this.context._value.date));
            }
        }
    },
};

</script>
