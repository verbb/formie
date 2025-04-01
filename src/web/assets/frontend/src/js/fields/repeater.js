import {
    t, addClasses, removeClasses, eventKey,
} from '../utils/utils';

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
                this.addRow();
            });
        }

        // Initialise any rendered rows
        if ($rows && $rows.length) {
            $rows.forEach(($row) => {
                this.initRow($row);
            });
        }

        // Create any minRows automatically if the field is empty
        if ((!$rows || !$rows.length) && this.minRows) {
            this.addMultipleRows(this.minRows);
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
            const { Formie } = this.form.config;
            const fieldConfigs = Formie.parseFieldConfig($row, this.$form);

            Object.keys(fieldConfigs).forEach((module) => {
                fieldConfigs[module].forEach((fieldConfig) => {
                    Formie.initJsClass(module, fieldConfig);
                });
            });
        }

        // Increment the number of rows "in store"
        this.rowCounter++;

        // Emit an "initRow" event
        this.$field.dispatchEvent(new CustomEvent('initRow', {
            bubbles: true,
            detail: {
                repeater: this,
                $row,
            },
        }));

        // Trigger a lazy global event, to allow other things to pick up on an initialized row.
        // This is most useful for conditions, where already initialized rows won't be picked up due to race conditions.
        this.form.triggerEvent('repeater:initRow', {
            repeater: this,
            $row,
        });
    }

    async addRow() {
        const handle = this.$addButton.getAttribute('data-add-repeater-row');
        const template = document.querySelector(`[data-repeater-template="${handle}"]`);
        const numRows = this.getNumRows();

        if (template) {
            if (numRows >= this.maxRows) {
                return;
            }

            const id = this.rowCounter;
            const html = template.innerHTML.replace(/__ROW__/g, id);

            let $newRow = document.createElement('div');
            $newRow.innerHTML = html.trim();
            $newRow = $newRow.querySelector('div:first-of-type');

            this.$field.querySelector('[data-repeater-rows]').appendChild($newRow);

            // Wait a little for the UI to be ready. Use a promise to ensure proper callback handling
            await new Promise((resolve) => { return setTimeout(resolve, 50); });

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
        }
    }

    async addMultipleRows(count) {
        for (let i = 0; i < count; i++) {
            await this.addRow();
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

            const event = new CustomEvent('remove', {
                bubbles: true,
                detail: {
                    repeater: this,
                    row: $row,
                    form: this.$form,
                },
            });
            this.$field.dispatchEvent(event);

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
            addClasses(this.$addButton, this.disabledClass);

            this.$addButton.setAttribute('disabled', 'disabled');
        } else {
            removeClasses(this.$addButton, this.disabledClass);

            this.$addButton.removeAttribute('disabled');
        }
    }
}

window.FormieRepeater = FormieRepeater;
