class FormieTable {
    constructor(settings = {}) {
        this.formId = '#formie-form-' + settings.formId;
        this.$form = document.querySelector(this.formId);

        if (this.$form) {
            this.initTables();
        }
    }

    initTables() {
        const $tables = this.$form.querySelectorAll('.fui-type-table');

        $tables.forEach(($repeater) => {
            const $addButton = $repeater.querySelector('[data-add-table-row]');

            if ($addButton) {
                $addButton.addEventListener('click', e => {
                    this.addRow(e, $repeater);
                });
            }
        });
    }

    addRow(e, $table) {
        const button = e.target;
        const handle = button.getAttribute('data-add-table-row');
        const maxRows = parseInt(button.getAttribute('data-max-rows'));
        const template = document.querySelector(`[data-table-template="${handle}"]`);

        if (template) {
            const numRows = this.getNumRows($table);
            if (numRows >= maxRows) {
                return;
            }

            const id = `${numRows + 1}`;
            const html = template.innerHTML.replace(/__ROW__/g, id);
            const $newRow = document.createElement('tr');
            $newRow.className = 'fui-table-row';
            $newRow.innerHTML = html;

            $table.querySelector('tbody').appendChild($newRow);

            setTimeout(() => {
                if (this.getNumRows($table) >= maxRows) {
                    button.className += ' fui-disabled';
                    button.setAttribute('disabled', 'disabled');

                    return;
                }

                $table.dispatchEvent(new CustomEvent('append', {
                    bubbles: true,
                    detail: {
                        row: $newRow,
                        form: this.$form,
                    },
                }));
            }, 0);
        }
    }

    getRows($table) {
        return $table.querySelectorAll('.fui-table-row');
    }

    getLastRow($table) {
        const rows = this.getRows($table);

        if (rows.length > 0) {
            return rows[rows.length - 1];
        }

        return null;
    }

    getNumRows($table) {
        return this.getRows($table).length;
    }
}

window.FormieTable = FormieTable;
