export class FormieRepeater {
    constructor(settings = {}) {
        this.fieldId = '#' + settings.fieldId;
        this.$field = document.querySelector(this.fieldId);

        if (this.$field) {
            this.$form = this.$field.closest('form');

            this.initRepeater();
        }
    }

    initRepeater() {
        const $rows = this.getRows();

        // Save a bunch of properties
        this.$addButton = this.$field.querySelector('[data-add-repeater-row]');
        this.minRows = parseInt(this.$addButton.getAttribute('data-min-rows'));
        this.maxRows = parseInt(this.$addButton.getAttribute('data-max-rows'));

        // Bind the click event to the add button
        if (this.$addButton) {
            this.$addButton.addEventListener('click', e => {
                this.addRow(e);
            });
        }

        // Initialise any rendered rows
        if ($rows && $rows.length) {
            $rows.forEach(($row) => {
                this.initRow($row);
            });
        }
    }

    initRow($row) {
        if (!$row) {
            console.error($row);
            return;
        }

        const $removeButton = $row.querySelector('[data-remove-repeater-row]');

        if ($removeButton) {
            $removeButton.addEventListener('click', e => {
                this.removeRow(e);
            });
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

            const id = `new${numRows + 1}`;
            const html = template.innerHTML.replace(/__ROW__/g, id);

            let $newRow = document.createElement('div');
            $newRow.innerHTML = html.trim();
            $newRow = $newRow.firstChild;

            this.$field.querySelector('.fui-repeater-rows').appendChild($newRow);

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
        const $row = button.closest('.fui-repeater-row');

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
        return this.$field.querySelectorAll('.fui-repeater-row');
    }

    getNumRows() {
        return this.getRows().length;
    }

    updateButton() {
        if (this.getNumRows() >= this.maxRows) {
            this.$addButton.classList.add = 'fui-disabled';
            this.$addButton.setAttribute('disabled', 'disabled');
        } else {
            this.$addButton.classList.remove = 'fui-disabled';
            this.$addButton.removeAttribute('disabled');
        }
    }
}

window.FormieRepeater = FormieRepeater;
