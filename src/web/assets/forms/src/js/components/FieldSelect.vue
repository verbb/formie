<template>
    <div class="field">
        <div class="heading">
            <label :id="id + '-label'" :for="id">{{ label }}</label>

            <div class="instructions">
                <p>{{ instructions }}</p>
            </div>
        </div>

        <div class="fui-element-mapping input ltr">
            <div class="select">
                <select v-model="proxyValue" :name="name">
                    <option value="">{{ t('formie', 'Always Opt-in') }}</option>

                    <option v-for="(option, j) in getFieldOptions()" :key="j" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
            </div>
        </div>
    </div>
</template>

<script>
import { truncate } from 'lodash-es';

import { toBoolean } from '@utils/bool';

export default {
    name: 'FieldSelect',

    props: {
        label: {
            type: String,
            default: '',
        },

        instructions: {
            type: String,
            default: '',
        },

        id: {
            type: String,
            default: '',
        },

        name: {
            type: String,
            default: '',
        },

        value: {
            type: String,
            default: '',
        },
    },

    data() {
        return {
            error: false,
            errorMessage: '',
            loading: false,
            proxyValue: '',
        };
    },

    created() {
        this.proxyValue = this.value || '';
    },

    methods: {
        getFieldOptions() {
            return this.$store.getters['form/getFieldSelectOptions']();
        },
    },
};

</script>
