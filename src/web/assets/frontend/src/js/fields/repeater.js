export class FormieRepeater {
    constructor(settings = {}) {
        this.formId = '#formie-form-' + settings.formId;
        this.$form = document.querySelector(this.formId);
        
        if (this.$form) {
            this.initRepeaters();
        }
    }

    initRepeaters() {
        const $repeaters = this.$form.querySelectorAll('.fui-type-repeater');

        $repeaters.forEach(($repeater) => {
            const $addButton = $repeater.querySelector('[data-add-repeater-row]');

            if ($addButton) {
                $addButton.addEventListener('click', e => {
                    this.addRow(e, $repeater);
                });
            }

            const $rows = $repeater.querySelectorAll('.fui-repeater-row');

            if ($rows) {
                $rows.forEach(($row) => {
                    this.initRows($row);
                });
            }
        });
    }

    initRows($row) {
        if ($row) {
            const $removeButton = $row.querySelector('[data-remove-repeater-row]');

            if ($removeButton) {
                $removeButton.addEventListener('click', e => {
                    this.removeRow(e);
                });
            }
        }
    }

    addRow(e, $repeater) {
        const button = e.target;
        const handle = button.getAttribute('data-add-repeater-row');
        const maxRows = parseInt(button.getAttribute('data-max-rows'));
        const template = document.querySelector(`[data-repeater-template="${handle}"]`);
        const numRows = this.getNumRows($repeater);

        if (template) {
            if (numRows >= maxRows) {
                return;
            }

            const id = `new${numRows + 1}`;
            const html = template.innerHTML.replace(/__ROW__/g, id);

            let $newRow = document.createElement('div');
            $newRow.innerHTML = html.trim();
            $newRow = $newRow.firstChild;

            $repeater.querySelector('.fui-repeater-rows').appendChild($newRow);

            setTimeout(() => {
                this.updateButton($repeater);

                const event = new CustomEvent('append', {
                    bubbles: true,
                    detail: {
                        row: $newRow,
                        form: this.$form,
                    },
                });
                $repeater.dispatchEvent(event);

                this.initRows(event.detail.row);
            }, 50);
        }
    }

    removeRow(e) {
        const button = e.target;
        const $row = button.closest('.fui-repeater-row');
        const $repeater = button.closest('.fui-type-repeater');

        if ($row && $repeater) {
            const $addButton = $repeater.querySelector('[data-add-repeater-row]');
            const minRows = parseInt($addButton.getAttribute('data-min-rows'));
            const numRows = this.getNumRows($repeater);

            if (numRows <= minRows) {
                return;
            }

            $row.parentNode.removeChild($row);

            this.updateButton($repeater);
        }
    }

    getRows($repeater) {
        return $repeater.querySelectorAll('.fui-repeater-row');
    }

    getLastRow($repeater) {
        const rows = this.getRows($repeater);

        if (rows.length > 0) {
            return rows[rows.length - 1];
        }

        return null;
    }

    getNumRows($repeater) {
        return this.getRows($repeater).length;
    }

    updateButton($repeater) {
        const $addButton = $repeater.querySelector('[data-add-repeater-row]');
        const maxRows = parseInt($addButton.getAttribute('data-max-rows'));

        if (this.getNumRows($repeater) >= maxRows) {
            $addButton.classList.add = 'fui-disabled';
            $addButton.setAttribute('disabled', 'disabled');
        } else {
            $addButton.classList.remove = 'fui-disabled';
            $addButton.removeAttribute('disabled');
        }
    }
}

window.FormieRepeater = FormieRepeater;
