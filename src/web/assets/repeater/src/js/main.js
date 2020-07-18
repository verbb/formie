if (typeof Craft.Formie === typeof undefined) {
    Craft.Formie = {};
}

if (typeof Craft.Formie.Repeater === typeof undefined) {
    Craft.Formie.Repeater = {};
}

(function($) {

    Craft.Formie.Repeater.Input = Garnish.Base.extend({
        id: null,
        rowInfo: null,
        inputNamePrefix: null,

        totalNewBlocks: 0,

        sorter: null,

        $div: null,
        $divInner: null,

        $table: null,
        $tbody: null,
        $addRowBtn: null,

        $field: null,

        init(id, rowInfo, inputNamePrefix, settings) {
            this.rowInfo = rowInfo;
            this.$field = new Craft.Formie.Repeater.InputRow(id, rowInfo, inputNamePrefix, settings);
        },

        addRow() {
            this.$field.addRow();
        },
    });

    Craft.Formie.Repeater.InputRow = Garnish.Base.extend({
        id: null,
        rowInfo: null,
        inputNamePrefix: null,
        settings: null,

        totalNewRows: 0,

        sorter: null,

        $div: null,
        $divInner: null,
        $rows: null,

        $table: null,
        $tbody: null,
        $addRowBtn: null,

        init(id, rowInfo, inputNamePrefix, settings) {
            this.id = id;
            this.rowInfo = rowInfo;
            this.inputNamePrefix = inputNamePrefix;
            this.settings = settings;

            this.$div = $(`div#${id}`);
            this.$divInner = this.$div.children('.rowLayoutContainer');

            this.$rows = this.$divInner.children('.repeaterRow');

            this.sorter = new Garnish.DragSort(this.$rows, {
                handle: '.tfoot-actions .reorder .move',
                axis: 'y',
                collapseDraggees: true,
                magnetStrength: 4,
                helperLagBase: 1.5,
                helperOpacity: 0.9,
            });

            for (let i = 0; i < this.$rows.length; i++) {
                new Craft.Formie.Repeater.InputRow.Row(this, this.$rows[i]);

                const $row = $(this.$rows[i]);
                id = $row.data('id');

                // Is this a new row?
                const newMatch = (typeof id == 'string' && id.match(/new(\d+)/));

                if (newMatch && newMatch[1] > this.totalNewRows) {
                    this.totalNewRows = parseInt(newMatch[1]);
                }
            }

            this.$addRowBtn = this.$divInner.next('.add');
            this.addListener(this.$addRowBtn, 'activate', 'addRow');

            this.updateAddRowBtn();
        },

        addRow() {
            this.totalNewRows++;

            const id = `new${this.totalNewRows}`;

            const bodyHtml = this.getParsedRowHtml(this.rowInfo ? this.rowInfo.bodyHtml : '', id),
                footHtml = this.getParsedRowHtml(this.rowInfo ? this.rowInfo.footHtml : '', id);

            const html = `
                <div class="repeaterRow" data-id="${id}">
                    <input type="hidden" name="${this.inputNamePrefix}[sortOrder][]" value="${id}" />
                    
                    <div id="${id}" class="repeater-layout-row-new">
                        <div class="repeater-layout-row-new-body">${bodyHtml}</div>
                        <div class="repeater-layout-row-new-actions tfoot-actions">
                            <div class="floating reorder"><a class="move icon" title="${Craft.t('super-table', 'Reorder')}"></a></div>
                            <div class="floating delete"><a class="delete icon" title="${Craft.t('super-table', 'Delete')}"></a></div>
                        </div>
                    </div>
                </div>
            `;

            const $tr = $(html).appendTo(this.$divInner);

            Garnish.$bod.append(footHtml);

            Craft.initUiElements($tr);

            const row = new Craft.Formie.Repeater.InputRow.Row(this, $tr);
            this.sorter.addItems($tr);

            row.expand();

            this.updateAddRowBtn();
        },

        getParsedRowHtml(html, id) {
            if (typeof html == 'string') {
                return html.replace(/__ROW__/g, id);
            } else {
                return '';
            }
        },

        canAddMoreRows() {
            return (!this.settings.maxRows || this.$divInner.children('.repeaterRow').length < this.settings.maxRows);
        },

        updateAddRowBtn() {
            if (this.canAddMoreRows()) {
                this.$addRowBtn.removeClass('disabled');
            } else {
                this.$addRowBtn.addClass('disabled');
            }
        },
    });

    Craft.Formie.Repeater.InputRow.Row = Garnish.Base.extend({
        table: null,

        $tr: null,
        $deleteBtn: null,

        init(table, tr) {
            this.table = table;
            this.$tr = $(tr);

            const $deleteBtn = this.$tr.children().last().find('.tfoot-actions .delete');
            this.addListener($deleteBtn, 'click', 'deleteRow');
        },

        canDeleteRows() {
            return (!this.table.settings.minRows || this.table.$divInner.children('.repeaterRow').length > this.table.settings.minRows);
        },

        deleteRow() {
            if (!this.canDeleteRows()) {
                return;
            }

            // Pause the draft editor
            if (window.draftEditor) {
                window.draftEditor.pause();
            }

            this.table.sorter.removeItems(this.$tr);

            this.contract(function() {
                this.$tr.remove();

                this.table.updateAddRowBtn();

                // Resume the draft editor
                if (window.draftEditor) {
                    window.draftEditor.resume();
                }
            });
        },

        expand(callback) {
            this.$tr
                .css(this._getContractedStyles())
                .velocity(this._getExpandedStyles(), 'fast', callback ? $.proxy(callback, this) : null);
        },

        contract(callback) {
            this.$tr
                .css(this._getExpandedStyles())
                .velocity(this._getContractedStyles(), 'fast', callback ? $.proxy(callback, this) : null);
        },

        _getExpandedStyles() {
            return {
                opacity: 1,
                marginBottom: 10,
            };
        },

        _getContractedStyles() {
            return {
                opacity: 0,
                marginBottom: -(this.$tr.outerHeight()),
            };
        },

    });

})(jQuery);
