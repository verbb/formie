import { eventKey } from '../utils/utils';

export class FormieRepeater {
    constructor(settings = {}) {
        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;
        this.disabledClass = this.form.getClasses('disabled');
        this.rowCounter = 0;

        this.initRepeater();
    }

    initRepeater() {
        const $rows = this.getRows();

        // Assign this instance to the field's DOM, so it can be accessed by third parties
        this.$field.repeater = this;

        // Save a bunch of properties
        this.$addButton = this.$field.querySelector('[data-add-repeater-row]');
        this.minRows = parseInt(this.$addButton.getAttribute('data-min-rows'));
        this.maxRows = parseInt(this.$addButton.getAttribute('data-max-rows'));

        // Bind the click event to the add button
        if (this.$addButton) {
            // Add the click event, but use a namespace so we can track these dynamically-added items
            this.form.addEventListener(this.$addButton, eventKey('click'), (e) => {
                this.addRow(e);
            });
        }

        // Initialise any rendered rows
        if ($rows && $rows.length) {
            $rows.forEach(($row) => {
                this.initRow($row);
            });
        }

        // Emit an "init" event
        this.$field.dispatchEvent(new CustomEvent('init', {
            bubbles: true,
            detail: {
                repeater: this,
            },
        }));
    }

    initRow($row, isNew = false) {
        if (!$row) {
            console.error($row);
            return;
        }

        const $removeButton = $row.querySelector('[data-remove-repeater-row]');

        if ($removeButton) {
            // Add the click event, but use a namespace so we can track these dynamically-added items
            this.form.addEventListener($removeButton, eventKey('click'), (e) => {
                this.removeRow(e);
            });
        }

        // Initialize any new nested fields with JS
        if (isNew) {
            const fieldConfigs = Formie.parseFieldConfig($row, this.$form);

            Object.keys(fieldConfigs).forEach((module) => {
                fieldConfigs[module].forEach((fieldConfig) => {
                    this.initFieldClass(module, fieldConfig);
                });
            });
        }

        // Increment the number of rows "in store"
        this.rowCounter++;
    }

    initFieldClass(className, params) {
        const moduleClass = window[className];

        if (moduleClass) {
            new moduleClass(params);
        }
    }

    addRow(e) {
        const button = e.target;
        const handle = this.$addButton.getAttribute('data-add-repeater-row');
        const template = document.querySelector(`[data-repeater-template="${handle}"]`);
        const numRows = this.getNumRows();

        if (template) {
            if (numRows >= this.maxRows) {
                return;
            }

            // We don't want this real-time. We want to maintain a counter to ensure
            // there's no collisions of new rows overwriting or jumbling up old rows
            // when removing them (adding 2, remove 1st, add new - results in issues).
            const id = `new${this.rowCounter + 1}`;
            const html = template.innerHTML.replace(/__ROW__/g, id);

            let $newRow = document.createElement('div');
            $newRow.innerHTML = html.trim();
            $newRow = $newRow.querySelector('div:first-of-type');

            this.$field.querySelector('[data-repeater-rows]').appendChild($newRow);

            setTimeout(() => {
                this.updateButton();

                const event = new CustomEvent('append', {
                    bubbles: true,
                    detail: {
                        repeater: this,
                        row: $newRow,
                        form: this.$form,
                    },
                });
                this.$field.dispatchEvent(event);

                this.initRow(event.detail.row, true);
            }, 50);
        }
    }

    removeRow(e) {
        const button = e.target;
        const $row = button.closest('[data-repeater-row]');

        if ($row) {
            const numRows = this.getNumRows();

            if (numRows <= this.minRows) {
                return;
            }

            $row.parentNode.removeChild($row);

            this.updateButton();
        }
    }

    getRows() {
        return this.$field.querySelectorAll('[data-repeater-row]');
    }

    getNumRows() {
        return this.getRows().length;
    }

    updateButton() {
        if (this.getNumRows() >= this.maxRows) {
            this.$addButton.classList.add = this.disabledClass;
            this.$addButton.setAttribute('disabled', 'disabled');
        } else {
            this.$addButton.classList.remove = this.disabledClass;
            this.$addButton.removeAttribute('disabled');
        }
    }
}

window.FormieRepeater = FormieRepeater;
