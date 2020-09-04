export class FormieTable {
    constructor(settings = {}) {
        this.fieldId = '#' + settings.fieldId;
        this.$field = document.querySelector(this.fieldId);

        if (this.$field) {
            this.$form = this.$field.closest('form');
            
            this.initTable();
        }
    }

    initTable() {
        const $addButton = this.$field.querySelector('[data-add-table-row]');

        if ($addButton) {
            $addButton.addEventListener('click', e => {
                this.addRow(e);
            });
        }
    }

    addRow(e) {
        const button = e.target;
        const handle = button.getAttribute('data-add-table-row');
        const maxRows = parseInt(button.getAttribute('data-max-rows'));
        const template = document.querySelector(`[data-table-template="${handle}"]`);

        if (template) {
            const numRows = this.getNumRows();
            if (numRows >= maxRows) {
                return;
            }

            const id = `${numRows + 1}`;
            const html = template.innerHTML.replace(/__ROW__/g, id);
            const $newRow = document.createElement('tr');
            $newRow.className = 'fui-table-row';
            $newRow.innerHTML = html;

            this.$field.querySelector('tbody').appendChild($newRow);

            setTimeout(() => {
                if (this.getNumRows() >= maxRows) {
                    button.className += ' fui-disabled';
                    button.setAttribute('disabled', 'disabled');

                    return;
                }

                this.$field.dispatchEvent(new CustomEvent('append', {
                    bubbles: true,
                    detail: {
                        row: $newRow,
                        form: this.$form,
                    },
                }));
            }, 0);
        }
    }

    getRows() {
        return this.$field.querySelectorAll('.fui-table-row');
    }

    getLastRow() {
        const rows = this.getRows();

        if (rows.length > 0) {
            return rows[rows.length - 1];
        }

        return null;
    }

    getNumRows() {
        return this.getRows().length;
    }
}

window.FormieTable = FormieTable;
