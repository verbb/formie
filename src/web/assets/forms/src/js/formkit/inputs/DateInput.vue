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
                timezone: Craft.timezone,
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
                this.proxyValue.date = e.target.value;

                this.context.node.input(this.proxyValue);
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
                this.proxyValue.time = e.target.value;

                this.context.node.input(this.proxyValue);
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
