<template>
    <div class="checkbox-select">
        <div v-for="(option, index) in options" :key="index">
            <input
                v-if="option.value === '*' || context._value !== '*'"
                :id="context.id + '-' + index"
                :checked="checked(option.value)"
                :class="['checkbox', { 'all': option.value === '*' }]"
                type="checkbox"
                :name="`${context.name}[]`"
                :value="option.value"
                @input="onInput"
            >

            <input
                v-else
                :id="context.id + '-' + index"
                class="checkbox"
                type="checkbox"
                :checked="true"
                disabled
            >

            <label v-if="option.value === '*'" :for="context.id + '-' + index">
                <strong>{{ option.label }}</strong>
            </label>

            <label v-else :for="context.id + '-' + index">{{ option.label }}</label>
        </div>
    </div>
</template>

<script>

export default {
    props: {
        context: {
            type: Object,
            default: () => {},
        },
    },

    computed: {
        options() {
            let options = [];

            // Check if we need to normalise
            if (this.context.attrs.options[0] && !this.context.attrs.options[0].label) {
                this.context.attrs.options.forEach((value) => {
                    options.push({
                        label: value,
                        value,
                    });
                });
            } else {
                // Create a local copy of options, otherwise things get pretty hairy
                options = this.clone(this.context.attrs.options);
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
            if (this.context._value === '*') {
                return true;
            }

            if (Array.isArray(this.context._value) && this.context._value.includes(value.toString())) {
                return true;
            }

            return false;
        },

        onInput(e) {
            const { checked, value } = e.target;

            if (value === '*') {
                if (checked) {
                    this.context.node.input('*');
                } else {
                    this.context.node.input([]);
                }
            } else {
                if (!Array.isArray(this.context._value)) {
                    this.context.node.input([]);
                }

                if (checked) {
                    this.context._value.push(value);
                } else {
                    const index = this.context._value.indexOf(value);

                    if (index > -1) {
                        this.context._value.splice(index, 1);
                    }
                }
            }
        },
    },
};

</script>
