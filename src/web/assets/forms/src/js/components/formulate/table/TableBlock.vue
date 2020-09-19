<template>
    <FormulateInput
        v-bind="$attrs"
        type="table"
        :repeatable="true"
        :validation-rules="getValidationRules()"
        :validation-messages="getValidationMessages()"
        :validation-name="$attrs.label"
        error-behavior="submit"
        :show-value="false"
    />
</template>

<script>

export default {
    name: 'TableBlock',

    provide() {
        return {
            labelsWithError: this.labelsWithError,
            valuesWithError: this.valuesWithError,
        };
    },

    data() {
        return {
            rendered: false,
            labelsWithError: [],
            valuesWithError: [],
        };
    },

    mounted() {
        // A little bit dicky, but our validation rules fire as soon as the component
        // is loaded. That doesn't look great for errors on a table field, where our custom validators
        // will show a red outline. Just set a flag here to only show errors after the initial run.
        setTimeout(() => {
            this.rendered = true;
        }, 500);
    },

    methods: {
        getValidationRules() {
            let rules = {};

            // Only load up the rules we include
            if (this.$attrs.validation.includes('uniqueLabels')) {
                rules.uniqueLabels = this.uniqueLabels;
            }

            if (this.$attrs.validation.includes('uniqueValues')) {
                rules.uniqueValues = this.uniqueValues;
            }

            if (this.$attrs.validation.includes('requiredLabels')) {
                rules.requiredLabels = this.requiredLabels;
            }

            if (this.$attrs.validation.includes('requiredValues')) {
                rules.requiredValues = this.requiredValues;
            }

            return rules;
        },
        
        getValidationMessages() {
            let messages = {};

            if (this.$attrs.validation.includes('uniqueLabels')) {
                messages.uniqueLabels = this.uniqueMessage('label');
            }

            if (this.$attrs.validation.includes('uniqueValues')) {
                messages.uniqueValues = this.uniqueMessage('value');
            }

            if (this.$attrs.validation.includes('requiredLabels')) {
                messages.requiredLabels = this.requiredMessage('label');
            }

            if (this.$attrs.validation.includes('requiredValues')) {
                messages.requiredValues = this.requiredMessage('value');
            }

            return messages;
        },

        uniqueLabels(context) {
            return this.unique('label', context);
        },

        uniqueValues(context) {
            return this.unique('value', context);
        },

        requiredLabels(context) {
            return this.required('label', context);
        },

        requiredValues(context) {
            return this.required('value', context);
        },

        uniqueMessage(prop) {
            return this.getMessage(prop, 'All {label} must be unique.');
        },

        requiredMessage(prop) {
            return this.getMessage(prop, '{label} is required.');
        },

        unique(prop, context) {
            return Promise.resolve((() => {
                // Prevent errors from showing early
                if (!this.rendered) {
                    return true;
                }

                let options = context.value;

                // Reactive reset
                if (prop === 'label') {
                    this.labelsWithError.splice(0, this.labelsWithError.length);
                } else if (prop === 'value') {
                    this.valuesWithError.splice(0, this.valuesWithError.length);
                }

                if (!options || options.length < 2) {
                    return true;
                }

                if (!Array.isArray(this.$attrs.columns)) {
                    return true;
                }

                const value = this.$attrs.columns.find(o => o.type === prop).name || prop;

                if (prop === 'value') {
                    options = options.filter(option => !option.isOptgroup);
                }

                const duplicates = this._checkDuplicates(options, value);

                duplicates.forEach(duplicate => {
                    if (prop === 'value') {
                        this.valuesWithError.push(duplicate);
                    } else if (prop === 'label') {
                        this.labelsWithError.push(duplicate);
                    }
                });

                return !duplicates.length;
            })());
        },

        required(prop, context) {
            return Promise.resolve((() => {
                // Prevent errors from showing early
                if (!this.rendered) {
                    return true;
                }

                const options = context.value;

                if (!Array.isArray(options)) {
                    return true;
                }

                const emptyFields = options.filter(row => {
                    if (!Array.isArray(this.$attrs.columns)) {
                        return false;
                    }

                    const valueKey = this.$attrs.columns.find(o => o.type === prop).name || prop;

                    // Opt-groups cancel out values
                    if (prop === 'value' && row.isOptgroup) {
                        return false;
                    }

                    var value = row[valueKey].trim();

                    if (!value) {
                        if (prop === 'value') {
                            this.valuesWithError.push(row[valueKey]);
                        } else if (prop === 'label') {
                            this.labelsWithError.push(row[valueKey]);
                        }
                    }

                    return !value;
                });

                return !emptyFields.length;
            })());
        },

        getMessage(prop, message) {
            let col;

            if (!Array.isArray(this.$attrs.columns)) {
                return '';
            }

            if (!col && prop === 'label') {
                col = this.$attrs.columns.find(o => {
                    return o.type === 'label' || o.type === 'heading';
                });
            }

            if (!col && prop === 'value') {
                col = this.$attrs.columns.find(o => {
                    return o.type === 'value' || o.type === 'handle';
                });
            }

            if (col) {
                return this.$options.filters.t(message, 'formie', { label: col.label });
            }
        },

        _checkDuplicates(options, field) {
            const occurrences = options.reduce((counter, item) => {
                counter[item[field]] = counter[item[field]] + 1 || 1;
                return counter;
            }, {});

            return Object.keys(occurrences).filter(item => {
                return occurrences[item] > 1 ? item : false;
            });
        },
    },
};

</script>
