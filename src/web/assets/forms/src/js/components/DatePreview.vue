<template>
    <div>
        <div v-if="field.settings.displayType === 'calendar'" class="fui-row">
            <div v-if="field.settings.includeDate" class="fui-col-auto">
                <label v-if="field.settings.includeTime && field.settings.timeLabel" class="fui-field-label">{{ field.name }}</label>

                <div class="fui-field-preview">
                    <input
                        type="text"
                        class="fui-field-input"
                        :value="date"
                    >

                    <span class="fui-field-icon">
                        <slot></slot>
                    </span>
                </div>
            </div>

            <div v-if="field.settings.includeTime" class="fui-col-auto">
                <label v-if="field.settings.timeLabel && field.settings.includeDate" class="fui-field-label">{{ field.settings.timeLabel }}</label>

                <div class="fui-field-preview">
                    <input
                        v-if="field.settings.includeTime"
                        type="text"
                        class="fui-field-input"
                        :value="time"
                    >

                    <span class="fui-field-icon">
                        <slot></slot>
                    </span>
                </div>
            </div>
        </div>

        <div v-else-if="field.settings.displayType === 'dropdowns'">
            <div class="fui-row">
                <div v-for="subfield in fields" :key="subfield.char" class="fui-col-auto">
                    <div class="fui-field-preview">
                        <label class="fui-field-label">{{ subfield.label }}</label>

                        <select class="fui-field-select">
                            <option value="" selected>
                                {{ subfield.value !== null ? subfield.value : subfield.placeholder }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div v-else-if="field.settings.displayType === 'inputs'">
            <div class="fui-row">
                <div v-for="subfield in fields" :key="subfield.char" class="fui-col-auto">
                    <div class="fui-field-preview">
                        <label class="fui-field-label">{{ subfield.label }}</label>

                        <input type="text" class="fui-field-input" :placeholder="subfield.placeholder" :value="subfield.value !== null ? subfield.value : subfield.placeholder">
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

        fields() {
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

            const format = (this.field.settings.includeDate ? this.field.settings.dateFormat : '') + (this.field.settings.includeTime ? this.field.settings.timeFormat : '');

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

                dateFields.push({
                    char,
                    value,
                    label: this.field.settings[`${chars[char]}Label`],
                    placeholder: this.field.settings[`${chars[char]}Placeholder`],
                });
            }

            return dateFields;
        },
    },
};

</script>
