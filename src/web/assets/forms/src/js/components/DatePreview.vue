<template>
    <div>
        <div v-if="field.settings.displayType === 'calendar' || field.settings.displayType === 'datePicker'" class="fui-row">
            <div v-for="subField in calendarFields" :key="subField.__id" class="fui-col-auto">
                <div class="fui-field-preview">
                    <label v-if="subField.labelPosition != 'verbb\\formie\\positions\\Hidden'" class="fui-field-label">{{ subField.label }}</label>

                    <input type="text" class="fui-field-input" :placeholder="subField.placeholder" :value="subField.value !== null ? subField.value : subField.placeholder">

                    <span class="fui-field-icon">
                        <slot></slot>
                    </span>
                </div>
            </div>
        </div>

        <div v-else-if="field.settings.displayType === 'dropdowns'">
            <div class="fui-row">
                <div v-for="subField in dropdownFields" :key="subField.__id" class="fui-col-auto">
                    <div class="fui-field-preview">
                        <label v-if="subField.settings.labelPosition != 'verbb\\formie\\positions\\Hidden'" class="fui-field-label">{{ subField.label }}</label>

                        <select class="fui-field-select">
                            <option value="" selected>
                                {{ subField.value !== null ? subField.value : subField.placeholder }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div v-else-if="field.settings.displayType === 'inputs'">
            <div class="fui-row">
                <div v-for="subField in dropdownFields" :key="subField.__id" class="fui-col-auto">
                    <div class="fui-field-preview">
                        <label v-if="subField.settings.labelPosition != 'verbb\\formie\\positions\\Hidden'" class="fui-field-label">{{ subField.label }}</label>

                        <input type="text" class="fui-field-input" :placeholder="subField.placeholder" :value="subField.value !== null ? subField.value : subField.placeholder">
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>

import { parseDate } from '@utils/string';

export default {
    name: 'DatePreview',

    props: {
        field: {
            type: Object,
            required: true,
        },
    },

    computed: {
        date() {
            let { defaultValue } = this.field.settings;
            defaultValue = new Date(parseDate(defaultValue));

            if (!(defaultValue instanceof Date) || isNaN(defaultValue)) {
                return null;
            }

            let day = defaultValue.getDate();
            let month = defaultValue.getMonth() + 1;
            const year = defaultValue.getFullYear();

            month = (month < 10 ? '0' : '') + month;
            day = (day < 10 ? '0' : '') + day;

            return `${year}-${month}-${day}`;
        },

        time() {
            let { defaultValue } = this.field.settings;
            defaultValue = new Date(parseDate(defaultValue));

            if (!(defaultValue instanceof Date) || isNaN(defaultValue)) {
                return null;
            }

            let hour = defaultValue.getHours();
            let min = defaultValue.getMinutes();
            let sec = defaultValue.getSeconds();

            hour = (hour < 10 ? '0' : '') + hour;
            min = (min < 10 ? '0' : '') + min;
            sec = (sec < 10 ? '0' : '') + sec;

            return `${hour}:${min}:${sec}`;
        },

        calendarFields() {
            const fields = [];

            const dateField = this.getSubFieldByHandle('date');
            const timeField = this.getSubFieldByHandle('time');

            if (dateField && dateField.settings.enabled) {
                fields.push(dateField.settings);
            }

            if (timeField && timeField.settings.enabled) {
                fields.push(timeField.settings);
            }

            return fields;
        },

        dropdownFields() {
            const chars = {
                Y: 'year',
                m: 'month',
                d: 'day',
                H: 'hour',
                h: 'hour',
                i: 'minute',
                s: 'second',
                A: 'ampm',
            };

            const format = this.field.settings.dateFormat + this.field.settings.timeFormat;

            const dateFields = [];

            let defaultValue = new Date();
            if (this.field.settings.defaultValue && this.field.settings.defaultValue.length) {
                defaultValue = new Date(parseDate(this.field.settings.defaultValue));
            }

            for (const char of format.replace(/[.\-:/ ]/g, '').split('')) {
                let value = null;

                if (defaultValue) {
                    switch (char) {
                    case 'Y':
                        value = defaultValue.getFullYear();
                        break;
                    case 'm':
                        value = defaultValue.toLocaleString('default', { month: 'long' });
                        break;
                    case 'd':
                        value = defaultValue.getDate();
                        break;
                    case 'H':
                        value = (defaultValue.getHours() + 24) % 12 || 12;
                        break;
                    case 'h':
                        value = defaultValue.getHours();
                        break;
                    case 'i':
                        value = defaultValue.getMinutes();
                        break;
                    case 's':
                        value = defaultValue.getSeconds();
                        break;
                    case 'A':
                        value = defaultValue.getHours() >= 12 ? 'PM' : 'AM';
                        break;
                    }
                }

                const handle = chars[char];
                const subField = this.getSubFieldByHandle(handle);

                dateFields.push({
                    char,
                    value,
                    label: subField?.settings.label ?? '',
                    placeholder: subField?.settings.placeholder ?? '',
                    settings: subField?.settings ?? {},
                });
            }

            return dateFields;
        },
    },

    methods: {
        getSubFieldByHandle(handle) {
            for (let i = 0; i < this.field.settings.rows.length; i++) {
                const obj = this.field.settings.rows[i];

                if (obj.fields) {
                    for (let j = 0; j < obj.fields.length; j++) {
                        const field = obj.fields[j];

                        if (field.settings && field.settings.handle === handle) {
                            return field;
                        }
                    }
                }
            }

            return null;
        },
    },
};

</script>
