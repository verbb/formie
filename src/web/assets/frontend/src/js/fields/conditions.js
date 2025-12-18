import { t, eventKey } from '../utils/utils';

export class FormieConditions {
    constructor(settings = {}) {
        this.$form = settings.$form;
        this.form = this.$form.form;

        // Best-practice for storing data keyed by DOM nodes
        // https://fitzgeraldnick.com/2014/01/13/hiding-implementation-details-with-e6-weakmaps.html
        this.conditionsStore = new WeakMap();

        this.initFieldConditions(this.$form);

        // Handle dynamic fields like Repeater, which should be evaluated when added
        this.form.registerEvent('repeater:initRow', (e) => {
            this.initFieldConditions(e.$row);
        });
    }

    initFieldConditions($container) {
        $container.querySelectorAll('[data-field-conditions]').forEach(($field) => {
            // Save our condition settings and targets against the origin fields. We'll use this to evaluate conditions
            const fieldConditions = this.getFieldConditions($field);

            // Check if this is a Repeater or Group field, and load in any of the child conditions so they can be triggered
            const fieldType = $field.getAttribute('data-field-type');
            const isNested = fieldType === 'group' || fieldType === 'repeater';

            if (isNested) {
                fieldConditions.nestedFieldConditions = $field.querySelectorAll('[data-field-conditions]');
            }

            this.conditionsStore.set($field, fieldConditions);

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

    getFieldConditions($field) {
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

            // Special handling for Repeater/Groups that have `__ROW__` in their name for conditions placeholders.
            if ((!$targets || !$targets.length) && condition.field.includes('__ROW__')) {
                // Get tricky with Regex. Find the element that matches everything except `[__ROW__]` for `[0]`.
                // Escape special characters `[]` in the string, and swap `[__ROW__]` with `[\d+]`.
                const regexString = condition.field.replace(/[.*+?^${}()|[\]\\]/g, '\\$&').replace(/__ROW__/g, '\\d+');

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

                // Visibility changes should use IntersectionObserver
                if (condition.condition === 'visible' || condition.condition === 'hidden') {
                    const observer = new IntersectionObserver((entries) => {
                        const isVisible = entries[0].intersectionRatio == 0 ? false : true;

                        $field.dispatchEvent(new CustomEvent('onFormieEvaluateConditions', { bubbles: true, detail: { conditions: this, isVisible } }));
                    }, { root: this.$form });

                    observer.observe($target);
                } else {

                    // Watch for changes on the target field. When one occurs, fire off a custom event on the source field
                    // We need to do this because target fields can be targetted by multiple conditions, and source
                    // fields can have multiple conditions - we need to check them all for all/any logic.
                    this.form.addEventListener($target, eventKey(eventType), () => {
                        return $field.dispatchEvent(new CustomEvent('onFormieEvaluateConditions', { bubbles: true, detail: { conditions: this } }));
                    });
                }

            });
        });

        return {
            showRule: conditionSettings.showRule,
            conditionRule: conditionSettings.conditionRule,
            isNested: conditionSettings.isNested || false,
            conditions,
        };
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
            showRule,
            conditionRule,
            conditions,
            isNested,
            nestedFieldConditions,
        } = conditionSettings;

        const results = {};

        // Check if this condition is nested in a Group/Repeater field. Only proceed if the parent field
        // conditional evaluation has passed. But we don't want this to run on page load, as that'll setup initial state
        if (isNested) {
            const $parentField = $field.closest('[data-field-type="group"], [data-field-type="repeater"]');

            if ($parentField) {
                // If the parent field is conditionally hidden, don't proceed further with testing this condition
                if ($parentField.conditionallyHidden) {
                    return;
                }
            }
        }

        conditions.forEach((condition, i) => {
            const {
                condition: logic,
                value,
                $targets,
                field,
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

                // Ensure that the value is cast as a string, numbers compared with strings don't work so well.
                const conditionValue = value == null ? '' : String(value);

                // If we've passed in a visibility value from IntersectObserver, override the field value
                if (e.detail && typeof e.detail.isVisible !== 'undefined') {
                    testOptions.visibility = e.detail.isVisible;
                }

                if ($target.getAttribute('data-fui-input-type') === 'agree') {
                    // Handle agree fields, which are a single checkbox, checked/unchecked
                    // Ignore the empty, hidden checkbox
                    if (inputType === 'hidden') {
                        return;
                    }

                    // Convert the value to boolean to compare
                    result = this.testCondition(logic, (conditionValue == '0') ? false : true, $target.checked);

                    results[resultKey].push(result);
                } else if (inputType === 'checkbox' || inputType === 'radio') {
                    // Handle (multi) checkboxes and radio, which are a bit of a pain
                    result = this.testCondition(logic, conditionValue, $target.value) && $target.checked;

                    results[resultKey].push(result);
                } else if (tagName === 'select' && $target.hasAttribute('multiple')) {
                    // Handle multi-selects
                    Array.from($target.options).forEach(($option) => {
                        result = this.testCondition(logic, conditionValue, $option.value) && $option.selected;

                        results[resultKey].push(result);
                    });
                } else {
                    result = this.testCondition(logic, conditionValue, $target.value, testOptions);

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

        // Show or hide? Also toggle the disabled state to sort out any hidden required fields
        if ((finalResult && showRule !== 'show') || (!finalResult && showRule === 'show')) {
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

        // Update the parent row to show the correct number of visible fields
        this.updateRowVisibility($field);

        // Fire an event to notify that the field's conditions have been evaluated
        $field.dispatchEvent(new CustomEvent('onAfterFormieEvaluateConditions', {
            bubbles: true,
            detail: {
                conditions: this,
                init: isInit,
            },
        }));

        // When triggering Group/Repeater conditions, ensure that we trigger any child conditions, now that the
        // Group/Repeater field has had its conditions evaluated. This is because inner fields aren't evaluated when
        // their outer parent is conditionally hidden, but when that parent field is shown, the fields inside should be evaluated.
        if (nestedFieldConditions && !isInit) {
            nestedFieldConditions.forEach(($nestedField) => {
                this.evaluateConditions({
                    target: $nestedField,
                });
            });
        }
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

    getEventType($input) {
        const $field = $input.closest('[data-field-type]');
        const fieldType = $field?.getAttribute('data-field-type');
        const tagName = $input.tagName.toLowerCase();
        const inputType = $input.getAttribute('type') ? $input.getAttribute('type').toLowerCase() : '';

        if (
            inputType === 'file' ||
            inputType === 'radio' ||
            inputType === 'checkbox' ||
            tagName === 'select' ||
            inputType === 'date'
        ) {
            return 'change';
        }

        return 'input';
    }

    testCondition(logic, value, fieldValue, testOptions = {}) {
        let result = false;

        // Are we dealing with dates? That's a whole other mess...
        if (testOptions.isDate) {
            value = new Date(value).valueOf();
            fieldValue = new Date(fieldValue).valueOf();
        }

        const isEmptyValue = (val) => {
            if (val == null) {
                return true;
            }

            if (typeof val === 'string' && val.trim() === '') {
                return true;
            }

            if (Array.isArray(val) && val.length === 0) {
                return true;
            }

            if (typeof val === 'object' && !Array.isArray(val) && Object.keys(val).length === 0) {
                return true;
            }

            return false;
        };

        if (logic === '=') {
            result = value === fieldValue;
        } else if (logic === '!=') {
            result = value !== fieldValue;
        } else if (logic === '>') {
            result = parseFloat(fieldValue, 10) > parseFloat(value, 10);
        } else if (logic === '<') {
            result = parseFloat(fieldValue, 10) < parseFloat(value, 10);
        } else if (logic === 'contains') {
            result = fieldValue?.includes?.(value);
        } else if (logic === 'startsWith') {
            result = fieldValue?.startsWith?.(value);
        } else if (logic === 'endsWith') {
            result = fieldValue?.endsWith?.(value);
        } else if (logic === 'empty') {
            result = isEmptyValue(fieldValue);
        } else if (logic === 'notEmpty') {
            result = !isEmptyValue(fieldValue);
        } else if (logic === 'visible') {
            result = testOptions.visibility === true;
        } else if (logic === 'hidden') {
            result = testOptions.visibility === false;
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

    updateRowVisibility($field) {
        const $parent = $field.closest('[data-fui-field-count]');

        if ($parent) {
            const allFields = $parent.querySelectorAll('[data-field-handle]:not([data-conditionally-hidden])');

            // Ensure that we're only checking on the first "level" of fields. For isntance, a Group field itself
            // might be conditionally hidden, but their inner fields won't be, producing incorrect results.
            // https://github.com/verbb/formie/issues/2337
            const $fields = Array.from(allFields).filter((el) => {
                return el.closest('[data-fui-field-count]') === $parent;
            });

            $parent.setAttribute('data-fui-field-count', $fields.length);

            // Update the class if we have classes enabled
            if ($parent.classList.contains('fui-row')) {
                if ($fields.length === 0) {
                    $parent.classList.add('fui-row-empty');
                } else {
                    $parent.classList.remove('fui-row-empty');
                }
            }
        }
    }
}

window.FormieConditions = FormieConditions;
