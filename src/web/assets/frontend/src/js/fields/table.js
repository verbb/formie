import { eventKey } from '../utils/utils';

export class FormieTable {
    constructor(settings = {}) {
        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;
        this.disabledClass = this.form.getClasses('disabled');
        this.rowCounter = 0;

        this.static = settings.static;

        this.initTable();
    }

    initTable() {
        const $rows = this.getRows();

        // Assign this instance to the field's DOM, so it can be accessed by third parties
        this.$field.table = this;

        // Save a bunch of properties
        this.$addButton = this.$field.querySelector('[data-add-table-row]');

        // Bind the click event to the add button
        if (this.$addButton) {
            this.minRows = parseInt(this.$addButton.getAttribute('data-min-rows'));
            this.maxRows = parseInt(this.$addButton.getAttribute('data-max-rows'));

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
                table: this,
            },
        }));
    }

    initRow($row) {
        if (!$row) {
            console.error($row);
            return;
        }

        const $removeButton = $row.querySelector('[data-remove-table-row]');

        if ($removeButton) {
            // Add the click event, but use a namespace so we can track these dynamically-added items
            this.form.addEventListener($removeButton, eventKey('click'), (e) => {
                this.removeRow(e);
            });
        }

        // Increment the number of rows "in store"
        this.rowCounter++;
    }

    addRow(e) {
        const button = e.target;
        const handle = this.$addButton.getAttribute('data-add-table-row');
        const template = document.querySelector(`[data-table-template="${handle}"]`);
        const numRows = this.getNumRows();

        if (template) {
            if (numRows >= this.maxRows) {
                return;
            }

            // We don't want this real-time. We want to maintain a counter to ensure
            // there's no collisions of new rows overwriting or jumbling up old rows
            // when removing them (adding 2, remove 1st, add new - results in issues).
            const id = this.rowCounter;
            const html = template.innerHTML.replace(/__ROW__/g, id);

            const $newRow = document.createElement('tr');
            $newRow.dataset.tableRow = true;
            $newRow.innerHTML = html;

            this.$field.querySelector('tbody').appendChild($newRow);

            setTimeout(() => {
                this.updateButton();

                const event = new CustomEvent('append', {
                    bubbles: true,
                    detail: {
                        row: $newRow,
                        form: this.$form,
                    },
                });
                this.$field.dispatchEvent(event);

                this.initRow(event.detail.row);
            }, 50);
        }
    }

    removeRow(e) {
        const button = e.target;
        const $row = button.closest('[data-table-row]');

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
        return this.$field.querySelectorAll('[data-table-row]');
    }

    getNumRows() {
        return this.getRows().length;
    }

    updateButton() {
        if (this.$addButton) {
            if (this.getNumRows() >= this.maxRows) {
                this.$addButton.classList.add = this.disabledClass;
                this.$addButton.setAttribute('disabled', 'disabled');
            } else {
                this.$addButton.classList.remove = this.disabledClass;
                this.$addButton.removeAttribute('disabled');
            }
        }
    }
}

window.FormieTable = FormieTable;
