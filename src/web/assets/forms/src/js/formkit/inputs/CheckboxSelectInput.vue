<template>
    <div class="checkbox-select">
        <div v-for="(option, index) in options" :key="index">
            <input
                v-if="option.value === '*' || !proxyValues.includes('*')"
                :id="context.id + '-' + index"
                :class="['checkbox', { 'all': option.value === '*' }]"
                type="checkbox"
                :value="option.value"
                :checked="isChecked(option.value)"
                @change="handleChange(option.value, $event.target.checked)"
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

    data() {
        return {
            proxyValues: [],
        };
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

    watch: {
        proxyValues(newValue) {
            // Change the value out of an array if selecting all for Craft compatibility
            if (newValue.includes('*')) {
                this.context.node.input('*');
            } else {
                this.context.node.input(newValue);
            }
        },
    },

    created() {
        // Normalize back to an array for proper reactiveness within this component
        if (this.context._value === '*') {
            this.proxyValues = ['*'];
        } else {
            this.proxyValues = this.clone(this.context._value);
        }

        if (this.proxyValues === undefined) {
            this.proxyValues = [];
        }
    },

    methods: {
        isChecked(value) {
            if (this.proxyValues.includes('*')) {
                return true;
            }

            // Ensure that we cast both the values and the value to string for fair comparing
            if (this.proxyValues.map((v) => {
                return v.toString();
            }).includes(value.toString())) {
                return true;
            }

            return false;
        },

        handleChange(value, checked) {
            if (checked) {
                this.proxyValues = this.proxyValues.concat(value);
            } else {
                this.proxyValues = this.proxyValues.filter((x) => { return x !== value; });
            }
        },
    },
};

</script>
