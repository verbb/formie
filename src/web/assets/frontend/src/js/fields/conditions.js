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

            if (!conditionSettings || !conditionSettings.conditions.length) {
                return;
            }

            // Store the conditions against the target field object for later access/testing
            const conditions = [];

            conditionSettings.conditions.forEach((condition) => {
                // Get the field(s) we're targeting to watch for changes. Note we need to handle multiple fields (checkboxes)
                let $targets = this.$form.querySelectorAll(`[name="${condition.field}"]`);

                // Check if we're dealing with multiple fields, like checkboxes. This overrides the above
                const $multiFields = this.$form.querySelectorAll(`[name="${condition.field}[]"]`);

                if ($multiFields.length) {
                    $targets = $multiFields;
                }

                // Special handling for Repeater/Groups that have `new1` in their name but for page reload forms
                // this will be replaced by the blockId, and will fail to match the conditions settings.
                if ((!$targets || !$targets.length) && condition.field.includes('[new1]')) {
                    // Get tricky with Regex. Find the element that matches everything except `[new1]` for `[1234]`.
                    // Escape special characters `[]` in the string, and swap `[new1]` with `[\d+]`.
                    const regexString = condition.field.replace(/[.*+?^${}()|[\]\\]/g, '\\$&').replace(/new1/g, '\\d+');

                    // Find all targets via Regex.
                    $targets = this.querySelectorAllRegex(new RegExp(regexString), 'name');
                }

                if (!$targets || !$targets.length) {
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
                    this.form.addEventListener($target, eventKey(eventType), () => {
                        return $field.dispatchEvent(new CustomEvent('onFormieEvaluateConditions', { bubbles: true, detail: { conditions: this } }));
                    });
                });
            });

            // Save our condition settings and targets against the origin fields. We'll use this to evaluate conditions
            this.conditionsStore.set($field, {
                showRule: conditionSettings.showRule,
                conditionRule: conditionSettings.conditionRule,
                isNested: conditionSettings.isNested || false,
                conditions,
            });

            // Add a custom event listener to fire when the field event listener fires
            this.form.addEventListener($field, eventKey('onFormieEvaluateConditions'), this.evaluateConditions.bind(this));

            // Also - trigger the event right now to evaluate immediately. Namely if we need to hide
            // field that are set to show if conditions are met. Pass in a param to let fields know if this is "init".
            $field.dispatchEvent(new CustomEvent('onFormieEvaluateConditions', { bubbles: true, detail: { conditions: this, init: true } }));
        });

        // Update the form hash, so we don't get change warnings
        if (this.form.formTheme) {
            this.form.formTheme.updateFormHash();
        }
    }

    evaluateConditions(e) {
        const $field = e.target;
        const isInit = e.detail ? e.detail.init : false;

        // Get the prepped conditions for this field
        const conditionSettings = this.conditionsStore.get($field);

        if (!conditionSettings) {
            return;
        }

        const {
            showRule, conditionRule, conditions, isNested,
        } = conditionSettings;
        const results = {};

        conditions.forEach((condition, i) => {
            const {
                condition: logic, value, $targets, field,
            } = condition;

            // We're always dealing with a collection of targets, even if the target is a text field
            // The reason being is this normalises behaviour for some fields (checkbox/radio) that
            // have multiple fields in a group.
            $targets.forEach(($target) => {
                let result = false;
                const testOptions = {};
                const tagName = $target.tagName.toLowerCase();
                const inputType = $target.getAttribute('type') ? $target.getAttribute('type').toLowerCase() : '';

                // Create a key for this condition rule that we'll use to store (potentially multiple) results against.
                // It's not visibly needed for anything, but using the target's field name helps with debugging.
                const resultKey = `${field}_${i}`;

                // Store all results as an array, and we'll normalise afterwards. Group results by their condition rule.
                // For example: { dropdown_0: [false], radio_1: [true, false] }
                if (!results[resultKey]) {
                    results[resultKey] = [];
                }

                // Handle some special options like dates - tell our condition tester about them
                if (inputType === 'date') {
                    testOptions.isDate = true;
                }

                // Handle agree fields, which are a single checkbox, checked/unchecked
                if ($target.getAttribute('data-fui-input-type') === 'agree') {
                    // Ignore the empty, hidden checkbox
                    if (inputType === 'hidden') {
                        return;
                    }

                    // Convert the value to boolean to compare
                    result = this.testCondition(logic, (value == '0') ? false : true, $target.checked);

                    results[resultKey].push(result);
                } else if (inputType === 'checkbox' || inputType === 'radio') {
                    // Handle (multi) checkboxes and radio, which are a bit of a pain
                    result = this.testCondition(logic, value, $target.value) && $target.checked;

                    results[resultKey].push(result);
                } else if (tagName === 'select' && $target.hasAttribute('multiple')) {
                    // Handle multi-selects
                    Array.from($target.options).forEach(($option) => {
                        result = this.testCondition(logic, value, $option.value) && $option.selected;

                        results[resultKey].push(result);
                    });
                } else {
                    result = this.testCondition(logic, value, $target.value, testOptions);

                    results[resultKey].push(result);
                }
            });
        });

        // Normalise the results before going further, as this'll be keyed as an object, so convert to an array
        // and because we can have multiple inputs, each with their own value, reduce them to a single boolean.
        // For example: { dropdown_0: [false], radio_1: [true, false] } changes to [false, true].
        const normalisedResults = [];

        Object.values(results).forEach((result) => {
            normalisedResults.push(result.includes(true));
        });

        let finalResult = false;

        // Check to see how to compare the result (any or all).
        if (normalisedResults.length) {
            if (conditionRule === 'all') {
                // Are _all_ the conditions the same?
                finalResult = normalisedResults.every((val) => { return val === true; });
            } else {
                finalResult = normalisedResults.includes(true);
            }
        }

        // Check if this condition is nested in a Group/Repeater field. Only proceed if the parent field
        // conditional evaluation has passed.
        let overrideResult = false;

        // But *do* setup conditions on the first run, when initialising all the fields
        if (isNested && !isInit) {
            const $parentField = $field.closest('[data-field-type="group"], [data-field-type="repeater"]');

            if ($parentField) {
                // Is the parent field conditionally hidden? Force the evaluation to be true (this field is
                // is conditionallu hidden), to prevent inner field conditions having a higher priority than the
                // parent Group/Repeater fields.
                if ($parentField.conditionallyHidden) {
                    overrideResult = true;
                }
            }
        }

        // Show or hide? Also toggle the disabled state to sort out any hidden required fields
        if (overrideResult || (finalResult && showRule !== 'show') || (!finalResult && showRule === 'show')) {
            $field.conditionallyHidden = true;
            $field.setAttribute('data-conditionally-hidden', true);

            $field.querySelectorAll('input, textarea, select').forEach(($input) => {
                $input.setAttribute('disabled', true);
            });
        } else {
            $field.conditionallyHidden = false;
            $field.removeAttribute('data-conditionally-hidden');

            $field.querySelectorAll('input, textarea, select').forEach(($input) => {
                $input.removeAttribute('disabled');
            });
        }

        // Fire an event to notify that the field's conditions have been evaluated
        $field.dispatchEvent(new CustomEvent('onAfterFormieEvaluateConditions', {
            bubbles: true,
            detail: {
                conditions: this,
                init: isInit,
            },
        }));
    }

    parseJsonConditions($field) {
        const json = $field.getAttribute('data-field-conditions');

        if (json) {
            try {
                return JSON.parse(json);
            } catch (e) {
                console.error(`Unable to parse JSON conditions: ${e}`);
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

    querySelectorAllRegex(regex, attributeToSearch) {
        const output = [];

        for (const element of this.$form.querySelectorAll(`[${attributeToSearch}]`)) {
            if (regex.test(element.getAttribute(attributeToSearch))) {
                output.push(element);
            }
        }

        return output;
    }
}

window.FormieConditions = FormieConditions;
