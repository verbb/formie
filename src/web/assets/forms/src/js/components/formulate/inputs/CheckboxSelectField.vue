<template>
    <div class="checkbox-select">
        <div v-for="(option, index) in options" :key="index">
            <input
                v-if="option.value === '*' || context.model !== '*'"
                :id="context.id + '-' + index"
                :checked="checked(option.value)"
                class="checkbox"
                :class="{ 'all': option.value === '*' }"
                type="checkbox"
                :name="`${context.name}[]`"
                :value="option.value"
                v-on="$listeners"
                @input="onInput"
            >

            <input
                v-else
                :id="context.id + '-' + index"
                class="checkbox"
                type="checkbox"
                :checked="true"
                disabled
                v-on="$listeners"
            >

            <label v-if="option.value === '*'" :for="context.id + '-' + index"><b>{{ option.label }}</b></label>
            <label v-else :for="context.id + '-' + index">{{ option.label }}</label>
        </div>
    </div>
</template>

<script>
import FormulateInputMixin from '@braid/vue-formulate/src/FormulateInputMixin';

export default {
    name: 'CheckboxSelectField',

    mixins: [FormulateInputMixin],

    computed: {

        options() {
            let options = [];

            // Check if we need to normalise
            if (this.context.options[0] && !this.context.options[0].label) {
                this.context.options.forEach(value => {
                    options.push({
                        label: value,
                        value,
                    });
                });
            } else {
                // Create a local copy of options, otherwise things get pretty hairy
                options = clone(this.context.options);
            }

            // Move the all option, if it exists
            options.forEach((option, index) => {
                if (option.value === '*') {
                    options.unshift(options.splice(index, 1)[0]);
                }
            });

            return options;
        },
    },

    methods: {
        checked(value) {
            if (this.context.model === '*') {
                return true;
            }

            if (Array.isArray(this.context.model) && this.context.model.includes(value)) {
                return true;
            }

            return false;
        },

        onInput(e) {
            let { checked, value } = e.target;

            if (value === '*') {
                if (checked) {
                    this.context.model = '*';
                } else {
                    this.context.model = [];
                }
            } else {
                if (!Array.isArray(this.context.model)) {
                    this.context.model = [];
                }

                if (checked) {
                    this.context.model.push(value);
                } else {
                    const index = this.context.model.indexOf(value);

                    if (index > -1) {
                        this.context.model.splice(index, 1);
                    }
                }
            }
        },
    },
};

</script>
