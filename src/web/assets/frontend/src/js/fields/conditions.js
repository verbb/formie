import { eventKey } from '../utils/utils';

export class FormieConditions {
    constructor(settings = {}) {
        this.$form = settings.$form;
        this.form = this.$form.form;

        // Best-practice for storing data keyed by DOM nodes
        // https://fitzgeraldnick.com/2014/01/13/hiding-implementation-details-with-e6-weakmaps.html
        this.conditionsStore = new WeakMap();

        this.initFieldConditions();
    }

    initFieldConditions() {
        this.$form.querySelectorAll('[data-field-conditions]').forEach(($field) => {
            const conditionSettings = this.parseJsonConditions($field);

            if (!conditionSettings) {
                return;
            }

            // Store the conditions against the target field object for later access/testing
            const conditions = [];

            conditionSettings.conditions.forEach((condition) => {
                // Get the field(s) we're targeting to watch for changes. Note we need to handle multiple fields (checkboxes)
                let $targets = this.$form.querySelectorAll('[name="' + condition.field + '"]');

                // Check if we're dealing with multiple fields, like checkboxes. This overrides the above
                const $multiFields = this.$form.querySelectorAll('[name="' + condition.field + '[]"]');

                if ($multiFields.length) {
                    $targets = $multiFields;
                }

                if (!$targets) {
                    return;
                }

                // Store the conditions with the target field for later access/testing
                condition.$targets = $targets;
                conditions.push(condition);

                $targets.forEach(($target) => {
                    // Get the right event for the field
                    const eventType = this.getEventType($target);

                    // Watch for changes on the target field. When one occurs, fire off a custom event on the source field
                    // We need to do this because target fields can be targetted by multiple conditions, and source
                    // fields can have multiple conditions - we need to check them all for all/any logic.
                    this.form.addEventListener($target, eventKey(eventType), () => $field.dispatchEvent(new Event('FormieEvaluateConditions', { bubbles: true })));
                });
            });
        
            // Save our condition settings and targets against the origin fields. We'll use this to evaluate conditions
            this.conditionsStore.set($field, {
                showRule: conditionSettings.showRule,
                conditionRule: conditionSettings.conditionRule,
                conditions,
            });

            // Add a custom event listener to fire when the field event listener fires
            this.form.addEventListener($field, eventKey('FormieEvaluateConditions'), this.evaluateConditions.bind(this));

            // Also - trigger the event right now to evaluate immediately. Namely if we need to hide
            // field that are set to show if conditions are met.
            $field.dispatchEvent(new Event('FormieEvaluateConditions', { bubbles: true }));
        });

        // Update the form hash, so we don't get change warnings
        if (this.form.formTheme) {
            this.form.formTheme.updateFormHash();
        }
    }

    evaluateConditions(e) {
        const $field = e.target;

        // Get the prepped conditions for this field
        const conditionSettings = this.conditionsStore.get($field);

        if (!conditionSettings) {
            return;
        }

        const { showRule, conditionRule, conditions } = conditionSettings;
        const results = [];

        conditions.forEach((condition) => {
            const { condition: logic, value, $targets } = condition;

            // We're always dealing with a collection of targets, even if the target is a text field
            // The reason being is this normalises behaviour for some fields (checkbox/radio) that
            // have multiple fields in a group.
            $targets.forEach(($target) => {
                let result = false;
                const testOptions = {};
                const tagName = $target.tagName.toLowerCase();
                const inputType = $target.getAttribute('type') ? $target.getAttribute('type').toLowerCase() : '';

                // We don't care about hidden inputs. Mostly messes up checkboxes!
                if (inputType === 'hidden') {
                    return;
                }

                // Handle some special options like dates - tell our condition tester about them
                if (inputType === 'date') {
                    testOptions.isDate = true;
                }

                // Handle agree fields, which are a single checkbox, checked/unchecked
                if ($target.getAttribute('data-fui-input-type') === 'agree') {
                    // Convert the value to boolean to compare
                    result = this.testCondition(logic, (value == '0') ? false : true, $target.checked);

                    results.push(result);

                // Handle (multi) checkboxes and radio, which are a bit of a pain
                } else if (inputType === 'checkbox' || inputType === 'radio') {
                    // Exclude any checkboxes that don't have conditions setup. We don't need to test against them
                    if ($target.value === value) {
                        // If the checkbox isn't checked, it's automatically false, but important to record
                        // when we have multiple conditions setup for a single checkbox group
                        result = ($target.checked) ? this.testCondition(logic, value, $target.value) : false;

                        results.push(result);
                    }
                // Handle multi-selects
                } else if (tagName === 'select' && $target.hasAttribute('multiple')) {
                    Array.from($target.options).forEach(($option) => {
                        // Exclude any options that don't have conditions setup. We don't need to test against them
                        if ($option.value === value) {
                            // If the option isn't selected, it's automatically false, but important to record
                            // when we have multiple conditions setup for a single select
                            result = ($option.selected) ? this.testCondition(logic, value, $option.value) : false;

                            results.push(result);
                        }
                    });
                } else {
                    result = this.testCondition(logic, value, $target.value, testOptions);

                    results.push(result);
                }
            });
        });

        let finalResult = false;

        // Check to see how to compare the result (any or all).
        if (results.length) {
            if (conditionRule === 'all') {
                // Are _all_ the conditions the same?
                finalResult = results.every((val) => val === true);
            } else {
                finalResult = results.includes(true);
            }
        }

        // Show or hide? Also toggle the disabled state to sort out any hidden required fields
        if ((finalResult && showRule !== 'show') || (!finalResult && showRule === 'show')) {
            $field.setAttribute('data-conditionally-hidden', true);

            $field.querySelectorAll('input, textarea, select').forEach(($input) => {
                $input.setAttribute('disabled', true);
            });
        } else {
            $field.removeAttribute('data-conditionally-hidden');

            $field.querySelectorAll('input, textarea, select').forEach(($input) => {
                $input.removeAttribute('disabled');
            });
        }
    }

    parseJsonConditions($field) {
        const json = $field.getAttribute('data-field-conditions');

        if (json) {
            try {
                return JSON.parse(json);
            } catch (e) {
                console.error('Unable to parse JSON conditions: ' + e);
            }
        }

        return false;
    }

    getEventType($field) {
        const tagName = $field.tagName.toLowerCase();
        const inputType = $field.getAttribute('type') ? $field.getAttribute('type').toLowerCase() : '';

        if (tagName === 'select' || inputType === 'date') {
            return 'change';
        }

        if (inputType === 'number') {
            return 'input';
        }

        if (inputType === 'checkbox' || inputType === 'radio') {
            return 'click';
        }

        return 'keyup';
    }

    testCondition(logic, value, fieldValue, testOptions = {}) {
        let result = false;

        // Are we dealing with dates? That's a whole other mess...
        if (testOptions.isDate) {
            value = new Date(value).valueOf();
            fieldValue = new Date(fieldValue).valueOf();
        }

        if (logic === '=') {
            result = value === fieldValue;
        } else if (logic === '!=') {
            result = value !== fieldValue;
        } else if (logic === '>') {
            result = parseFloat(fieldValue, 10) > parseFloat(value, 10);
        } else if (logic === '<') {
            result = parseFloat(fieldValue, 10) < parseFloat(value, 10);
        } else if (logic === 'contains') {
            result = fieldValue.includes(value);
        } else if (logic === 'startsWith') {
            result = fieldValue.startsWith(value);
        } else if (logic === 'endsWith') {
            result = fieldValue.endsWith(value);
        }

        return result;
    }
}

window.FormieConditions = FormieConditions;
