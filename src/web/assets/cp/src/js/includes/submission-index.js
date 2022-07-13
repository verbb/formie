if (typeof Craft.Formie === typeof undefined) {
    Craft.Formie = {};
}

Craft.Formie.SubmissionIndex = Craft.BaseElementIndex.extend({
    editableForms: null,
    $newSubmissionBtnGroup: null,
    $newSubmissionBtn: null,

    init(elementType, $container, settings) {
        this.on('selectSource', $.proxy(this, 'updateButton'));
        this.on('selectSite', $.proxy(this, 'updateButton'));

        this.base(elementType, $container, settings);

        this.settings.criteria = {
            isIncomplete: false,
            isSpam: false,
        };

        // Find the settings menubtn, and add a new option to it
        var $menubtn = this.$statusMenuBtn.menubtn().data('menubtn');

        if ($menubtn) {
            var $incomplete = $('<li><a data-incomplete><span class="icon" data-icon="draft"></span> ' + Craft.t('formie', 'Incomplete') + '</a></li>');
            var $spam = $('<li><a data-spam><span class="icon" data-icon="error"></span> ' + Craft.t('formie', 'Spam') + '</a></li>');
            var $hr = $('<hr class="padded">');

            $menubtn.menu.addOptions($incomplete.children());
            $menubtn.menu.addOptions($spam.children());

            $hr.appendTo($menubtn.menu.$container.find('ul:first'));
            $incomplete.appendTo($menubtn.menu.$container.find('ul:first'));
            $spam.appendTo($menubtn.menu.$container.find('ul:first'));

            // Hijack the event
            $menubtn.menu.on('optionselect', $.proxy(this, '_handleStatusChange'));
        }
    },

    afterInit() {
        this.editableForms = [];

        var { editableSubmissions } = Craft.Formie;

        if (editableSubmissions) {
            for (var i = 0; i < editableSubmissions.length; i++) {
                var form = editableSubmissions[i];

                if (this.getSourceByKey('form:' + form.id)) {
                    this.editableForms.push(form);
                }
            }
        }

        this.base();
    },

    _handleStatusChange(ev) {
        this.statusMenu.$options.removeClass('sel');
        var $option = $(ev.selectedOption).addClass('sel');
        this.$statusMenuBtn.html($option.html());

        this.trashed = false;
        this.drafts = false;
        this.status = null;
        this.settings.criteria.isIncomplete = false;
        this.settings.criteria.isSpam = false;

        if (Garnish.hasAttr($option, 'data-spam')) {
            this.settings.criteria.isSpam = true;
        } else if (Garnish.hasAttr($option, 'data-incomplete')) {
            this.settings.criteria.isIncomplete = true;
        } else if (Garnish.hasAttr($option, 'data-trashed')) {
            this.trashed = true;
            this.settings.criteria.isIncomplete = null;
            this.settings.criteria.isSpam = null;
        } else if (Garnish.hasAttr($option, 'data-drafts')) {
            this.drafts = true;
        } else {
            this.status = $option.data('status');
        }

        this._updateStructureSortOption();
        this.updateElements();
    },
    
    getViewClass(mode) {
        if (mode === 'table') {
            return Craft.Formie.SubmissionTableView;
        } else {
            return this.base(mode);
        }
    },

    getDefaultSort() {
        return ['dateCreated', 'desc'];
    },

    getDefaultSourceKey() {
        if (this.settings.context === 'index' && typeof defaultFormieFormHandle !== 'undefined') {
            for (var i = 0; i < this.$sources.length; i++) {
                var $source = $(this.$sources[i]);

                if ($source.data('handle') === defaultFormieFormHandle) {
                    return $source.data('key');
                }
            }
        }

        return this.base();
    },

    updateButton() {
        if (!this.$source) {
            return;
        }

        var handle = this.$source.data('handle');
        var i, href, label;

        if (this.editableForms.length) {
            // Remove the old button, if there is one
            if (this.$newSubmissionBtnGroup) {
                this.$newSubmissionBtnGroup.remove();
            }

            var selectedForm;

            if (handle) {
                for (i = 0; i < this.editableForms.length; i++) {
                    if (this.editableForms[i].handle === handle) {
                        selectedForm = this.editableForms[i];
                        break;
                    }
                }
            }

            this.$newSubmissionBtnGroup = $('<div class="btngroup submit"/>');
            var $menuBtn;

            if (selectedForm) {
                href = this._getFormTriggerHref(selectedForm);
                label = (this.settings.context === 'index' ? Craft.t('formie', 'New submission') : Craft.t('formie', 'New {form} submission', { form: selectedForm.name }));
                this.$newSubmissionBtn = $('<a class="btn submit add icon" ' + href + ' role="button" tabindex="0">' + Craft.escapeHtml(label) + '</a>').appendTo(this.$newSubmissionBtnGroup);

                if (this.settings.context !== 'index') {
                    this.addListener(this.$newSubmissionBtn, 'click', function(ev) {
                        this._openCreateSubmissionModal(ev.currentTarget.getAttribute('data-id'));
                    });
                }

                if (this.editableForms.length > 1) {
                    $menuBtn = $('<button/>', {
                        type: 'button',
                        class: 'btn submit menubtn',
                    }).appendTo(this.$newSubmissionBtnGroup);
                }
            } else {
                this.$newSubmissionBtn = $menuBtn = $('<button/>', {
                    type: 'button',
                    class: 'btn submit add icon menubtn',
                    text: Craft.t('formie', 'New submission'),
                }).appendTo(this.$newSubmissionBtnGroup);
            }

            if ($menuBtn) {
                var menuHtml = '<div class="menu"><ul>';

                for (i = 0; i < this.editableForms.length; i++) {
                    var form = this.editableForms[i];

                    if ((this.settings.context === 'index' && $.inArray(this.siteId, form.sites) !== -1) || (this.settings.context !== 'index' && form !== selectedForm)) {
                        href = this._getFormTriggerHref(form);
                        label = (this.settings.context === 'index' ? form.name : Craft.t('formie', 'New {form} submission', { form: form.name }));
                        menuHtml += '<li><a ' + href + '>' + Craft.escapeHtml(label) + '</a></li>';
                    }
                }

                menuHtml += '</ul></div>';

                $(menuHtml).appendTo(this.$newSubmissionBtnGroup);
                var menuBtn = new Garnish.MenuBtn($menuBtn);

                if (this.settings.context !== 'index') {
                    menuBtn.on('optionSelect', ev => {
                        this._openCreateSubmissionModal(ev.option.getAttribute('data-id'));
                    });
                }
            }

            this.addButton(this.$newSubmissionBtnGroup);
        }

        if (this.settings.context === 'index' && typeof history !== 'undefined') {
            var uri = 'formie/submissions';

            if (handle) {
                uri += '/' + handle;
            }

            history.replaceState({}, '', Craft.getUrl(uri));
        }
    },

    getSite() {
        if (!this.siteId) {
            return undefined;
        }
        return Craft.sites.find(s => s.id == this.siteId);
    },

    _getFormTriggerHref(form) {
        if (this.settings.context === 'index') {
            const uri = `formie/submissions/${form.handle}/new`;
            const site = this.getSite();
            const params = site ? { site: site.handle } : undefined;
            return `href="${Craft.getUrl(uri, params)}"`;
        }

        return `data-id="${form.id}"`;
    },

    _openCreateSubmissionModal(formId) {
        if (this.$newSubmissionBtn.hasClass('loading')) {
            return;
        }

        var form;

        for (var i = 0; i < this.editableForms.length; i++) {
            if (this.editableForms[i].id == formId) {
                form = this.editableForms[i];
                break;
            }
        }

        if (!form) {
            return;
        }

        this.$newSubmissionBtn.addClass('inactive');
        var newSubmissionBtnText = this.$newSubmissionBtn.text();
        this.$newSubmissionBtn.text(Craft.t('formie', 'New {form} submission', { form: form.name }));

        Craft.createElementEditor(this.elementType, {
            hudTrigger: this.$newSubmissionBtnGroup,
            siteId: this.siteId,
            attributes: {
                formId,
            },
            onHideHud: () => {
                this.$newSubmissionBtn.removeClass('inactive').text(newSubmissionBtnText);
            },
            onSaveElement: response => {
                var formSourceKey = 'form:' + form.id;

                if (this.sourceKey !== formSourceKey) {
                    this.selectSourceByKey(formSourceKey);
                }

                this.selectElementAfterUpdate(response.id);
                this.updateElements();
            },
        });
    },
});

Craft.Formie.SubmissionTableView = Craft.TableElementIndexView.extend({
    startDate: null,
    endDate: null,

    startDatepicker: null,
    endDatepicker: null,

    $chartExplorer: null,
    $totalValue: null,
    $chartContainer: null,
    $spinner: null,
    $error: null,
    $chart: null,
    $startDate: null,
    $endDate: null,

    afterInit() {
        this.$explorerContainer = $('<div class="chart-explorer-container"></div>').prependTo(this.$container);
        this.createChartExplorer();
        this.base();
    },

    getStorage(key) {
        return Craft.Formie.SubmissionTableView.getStorage(this.elementIndex._namespace, key);
    },

    setStorage(key, value) {
        Craft.Formie.SubmissionTableView.setStorage(this.elementIndex._namespace, key, value);
    },

    createChartExplorer() {
        // chart explorer
        var $chartExplorer = $('<div class="chart-explorer"></div>').appendTo(this.$explorerContainer),
            $chartHeader = $('<div class="chart-header"></div>').appendTo($chartExplorer),
            $dateRange = $('<div class="date-range" />').appendTo($chartHeader),
            $startDateContainer = $('<div class="datewrapper"></div>').appendTo($dateRange),
            $to = $('<span class="to light">to</span>').appendTo($dateRange),
            $endDateContainer = $('<div class="datewrapper"></div>').appendTo($dateRange),
            $total = $('<div class="total"></div>').appendTo($chartHeader),
            $totalLabel = $('<div class="total-label light">' + Craft.t('formie', 'Total Submissions') + '</div>').appendTo($total),
            $totalValueWrapper = $('<div class="total-value-wrapper"></div>').appendTo($total),
            $totalValue = $('<span class="total-value">&nbsp;</span>').appendTo($totalValueWrapper);

        this.$chartExplorer = $chartExplorer;
        this.$totalValue = $totalValue;
        this.$chartContainer = $('<div class="chart-container"></div>').appendTo($chartExplorer);
        this.$spinner = $('<div class="spinner hidden" />').prependTo($chartHeader);
        this.$error = $('<div class="error"></div>').appendTo(this.$chartContainer);
        this.$chart = $('<div class="chart"></div>').appendTo(this.$chartContainer);

        this.$startDate = $('<input type="text" class="text" size="20" autocomplete="off" />').appendTo($startDateContainer);
        this.$endDate = $('<input type="text" class="text" size="20" autocomplete="off" />').appendTo($endDateContainer);

        this.$startDate.datepicker($.extend({
            onSelect: $.proxy(this, 'handleStartDateChange'),
        }, Craft.datepickerOptions));

        this.$endDate.datepicker($.extend({
            onSelect: $.proxy(this, 'handleEndDateChange'),
        }, Craft.datepickerOptions));

        this.startDatepicker = this.$startDate.data('datepicker');
        this.endDatepicker = this.$endDate.data('datepicker');

        this.addListener(this.$startDate, 'keyup', 'handleStartDateChange');
        this.addListener(this.$endDate, 'keyup', 'handleEndDateChange');

        // Set the start/end dates
        var startTime = this.getStorage('startTime') || ((new Date()).getTime() - (60 * 60 * 24 * 7 * 1000)),
            endTime = this.getStorage('endTime') || ((new Date()).getTime());

        this.setStartDate(new Date(startTime));
        this.setEndDate(new Date(endTime));

        // Load the report
        this.loadReport();
    },

    handleStartDateChange() {
        if (this.setStartDate(Craft.Formie.SubmissionTableView.getDateFromDatepickerInstance(this.startDatepicker))) {
            this.loadReport();
        }
    },

    handleEndDateChange() {
        if (this.setEndDate(Craft.Formie.SubmissionTableView.getDateFromDatepickerInstance(this.endDatepicker))) {
            this.loadReport();
        }
    },

    setStartDate(date) {
        // Make sure it has actually changed
        if (this.startDate && date.getTime() === this.startDate.getTime()) {
            return false;
        }

        this.startDate = date;
        this.setStorage('startTime', this.startDate.getTime());
        this.$startDate.val(Craft.formatDate(this.startDate));

        // If this is after the current end date, set the end date to match it
        if (this.endDate && this.startDate.getTime() > this.endDate.getTime()) {
            this.setEndDate(new Date(this.startDate.getTime()));
        }

        return true;
    },

    setEndDate(date) {
        // Make sure it has actually changed
        if (this.endDate && date.getTime() === this.endDate.getTime()) {
            return false;
        }

        this.endDate = date;
        this.setStorage('endTime', this.endDate.getTime());
        this.$endDate.val(Craft.formatDate(this.endDate));

        // If this is before the current start date, set the start date to match it
        if (this.startDate && this.endDate.getTime() < this.startDate.getTime()) {
            this.setStartDate(new Date(this.endDate.getTime()));
        }

        return true;
    },

    loadReport() {
        var data = this.settings.params;

        data.startDate = Craft.Formie.SubmissionTableView.getDateValue(this.startDate);
        data.endDate = Craft.Formie.SubmissionTableView.getDateValue(this.endDate);

        this.$spinner.removeClass('hidden');
        this.$error.addClass('hidden');
        this.$chart.removeClass('error');

        Craft.sendActionRequest('POST', 'formie/charts/get-submissions-data', { data })
            .then((response) => {
                if (!this.chart) {
                    this.chart = new Craft.charts.Area(this.$chart);
                }

                var chartDataTable = new Craft.charts.DataTable(response.data.dataTable);

                var chartSettings = {
                    formatLocaleDefinition: response.data.formatLocaleDefinition,
                    orientation: response.data.orientation,
                    formats: response.data.formats,
                    dataScale: response.data.scale,
                };

                this.chart.draw(chartDataTable, chartSettings);

                this.$totalValue.html(response.data.totalHtml);
            })
            .catch(({response}) => {
                var msg = Craft.t('formie', 'An unknown error occurred.');

                if (response && response.data && response.data.message) {
                    msg = response.data.message;
                }

                this.$error.html(msg);
                this.$error.removeClass('hidden');
                this.$chart.addClass('error');
            })
            .finally(() => {
                this.$spinner.addClass('hidden');
            });
    },
},
{
    storage: {},

    getStorage(namespace, key) {
        if (Craft.Formie.SubmissionTableView.storage[namespace] && Craft.Formie.SubmissionTableView.storage[namespace][key]) {
            return Craft.Formie.SubmissionTableView.storage[namespace][key];
        }

        return null;
    },

    setStorage(namespace, key, value) {
        if (typeof Craft.Formie.SubmissionTableView.storage[namespace] === typeof undefined) {
            Craft.Formie.SubmissionTableView.storage[namespace] = {};
        }

        Craft.Formie.SubmissionTableView.storage[namespace][key] = value;
    },

    getDateFromDatepickerInstance(inst) {
        return new Date(inst.currentYear, inst.currentMonth, inst.currentDay);
    },

    getDateValue(date) {
        return date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + date.getDate();
    },
});

(function($) {
    $(document).on('click', '.js-fui-submission-modal-send-btn', function(e) {
        e.preventDefault();

        new Craft.Formie.SendNotificationModal($(this).data('id'));
    });
})(jQuery);

Craft.Formie.SendNotificationModal = Garnish.Modal.extend({
    init(id) {
        this.$form = $('<form class="modal fui-send-notification-modal" method="post" accept-charset="UTF-8"/>').appendTo(Garnish.$bod);
        this.$body = $('<div class="body"><div class="spinner big"></div></div>').appendTo(this.$form);

        var $footer = $('<div class="footer"/>').appendTo(this.$form);
        var $mainBtnGroup = $('<div class="buttons right"/>').appendTo($footer);
        this.$cancelBtn = $('<input type="button" class="btn" value="' + Craft.t('formie', 'Cancel') + '"/>').appendTo($mainBtnGroup);
        this.$updateBtn = $('<input type="button" class="btn submit" value="' + Craft.t('formie', 'Send Email Notification') + '"/>').appendTo($mainBtnGroup);
        this.$footerSpinner = $('<div class="spinner right hidden"/>').appendTo($footer);

        Craft.initUiElements(this.$form);

        this.addListener(this.$cancelBtn, 'click', 'onFadeOut');
        this.addListener(this.$updateBtn, 'click', 'onSend');

        this.base(this.$form);

        var data = { id };

        Craft.sendActionRequest('POST', 'formie/submissions/get-send-notification-modal-content', { data })
            .then((response) => {
                this.$body.html(response.data.modalHtml);
                Craft.appendHeadHtml(response.data.headHtml);
                Craft.appendBodyHtml(response.data.footHtml);
            });
    },

    onFadeOut() {
        this.$form.remove();
        this.$shade.remove();
    },

    onSend(e) {
        e.preventDefault();

        this.$footerSpinner.removeClass('hidden');

        var data = this.$form.serialize();

        // Save everything through the normal update-cart action, just like we were doing it on the front-end
        Craft.sendActionRequest('POST', 'formie/submissions/send-notification', { data })
            .then((response) => {
                location.reload();
            })
            .catch(({response}) => {
                if (response && response.data && response.data.message) {
                    Craft.cp.displayError(response.data.message);
                } else {
                    Craft.cp.displayError();
                }
            })
            .finally(() => {
                this.$footerSpinner.addClass('hidden');
            });
    },
});

Craft.registerElementIndexClass('verbb\\formie\\elements\\Submission', Craft.Formie.SubmissionIndex);
