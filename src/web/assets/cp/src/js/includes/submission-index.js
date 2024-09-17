if (typeof Craft.Formie === typeof undefined) {
    Craft.Formie = {};
}

Craft.Formie.SubmissionIndex = Craft.BaseElementIndex.extend({
    editableForms: [],
    $newSubmissionBtnGroup: null,
    $newSubmissionBtn: null,
    startDate: null,
    endDate: null,

    init(elementType, $container, settings) {
        this.on('selectSource', $.proxy(this, 'updateButton'));
        this.on('selectSite', $.proxy(this, 'updateButton'));

        // Include incomplete and spam submissions by default
        settings.criteria = {
            isIncomplete: null,
            isSpam: null,
        };

        // Find the settings menubtn, and add a new option to it. A little extra work as this needs to be done before
        var $toolbar = $container.find('#toolbar:first');

        Craft.ui.createDateRangePicker({
            onChange: function (startDate, endDate) {
                this.startDate = startDate;
                this.endDate = endDate;
                this.updateElements();
            }.bind(this),
        }).appendTo($toolbar);

        this.base(elementType, $container, settings);

        // Setup our custom state menu button
        this.setupStateButton();
    },

    afterInit() {
        const editableForms = Craft.Formie.editableForms;

        if (editableForms) {
            for (var i = 0; i < editableForms.length; i++) {
                var form = editableForms[i];

                if (this.getSourceByKey('form:' + form.id)) {
                    this.editableForms.push(form);
                }
            }
        }

        this.base();
    },

    setupStateButton() {
        let $btn = $('<button/>', {
            type: 'button',
            class: 'btn menubtn statusmenubtn',
        }).append(
            $('<span/>', {
                class: 'status disabled',
            }),
            $('<span/>', {
                text: Craft.t('formie', 'All'),
            })
        );

        let $menu = $('<div/>', { class: 'menu' }).append(
            $('<ul/>', { class: 'padded' }).append(
                $('<li/>').append(
                    $('<a/>', { 'data-state': 'all' }).append(
                        $('<span/>', { class: 'status disabled' }),
                        $('<span/>', { text: Craft.t('formie', 'All') })
                    )
                ),
                $('<li/>').append(
                    $('<a/>', { 'data-state': 'complete' }).append(
                        $('<span/>', { class: 'icon', 'data-icon': 'check' }),
                        $('<span/>', { text: Craft.t('formie', 'Complete') })
                    )
                ),
                $('<li/>').append(
                    $('<a/>', { 'data-state': 'incomplete' }).append(
                        $('<span/>', { class: 'icon', 'data-icon': 'draft' }),
                        $('<span/>', { text: Craft.t('formie', 'Incomplete') })
                    )
                ),
                $('<li/>').append(
                    $('<a/>', { 'data-state': 'spam' }).append(
                        $('<span/>', { class: 'icon', 'data-icon': 'bug' }),
                        $('<span/>', { text: Craft.t('formie', 'Spam') })
                    )
                )
            )
        );

        var self = this;

        var menu = new Garnish.Menu($menu, {
            onOptionSelect: function (option) {
                var $option = $(option);
                $btn.html($option.html());
                menu.setPositionRelativeToAnchor();
                $menu.find('.sel').removeClass('sel');
                $option.addClass('sel');

                if ($option.data('state') === 'all') {
                    self.settings.criteria.isIncomplete = null;
                    self.settings.criteria.isSpam = null;
                }

                if ($option.data('state') === 'complete') {
                    self.settings.criteria.isIncomplete = false;
                    self.settings.criteria.isSpam = false;
                }

                if ($option.data('state') === 'incomplete') {
                    self.settings.criteria.isIncomplete = true;
                    self.settings.criteria.isSpam = false;
                }

                if ($option.data('state') === 'spam') {
                    self.settings.criteria.isIncomplete = false;
                    self.settings.criteria.isSpam = true;
                }
        
                Craft.setQueryParam('state', $option.data('state'));
                self.updateElements();
            },
        });

        new Garnish.MenuBtn($btn, menu);

        $btn.insertBefore($('.search-container'));

        // Set the current state based on query string, or plugin defaults
        const currentState = Craft.getQueryParam('state') ? Craft.getQueryParam('state') : Craft.Formie.defaultState;
        const $option = menu.$options.filter('[data-state=' + currentState + ']');

        if ($option.length) {
            menu.selectOption($option[0]);
        }
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

        if (this.settings.context === 'index') {
            var uri = 'formie/submissions';

            if (handle) {
                uri += '/' + handle;
            }

            Craft.setPath(uri);
        }
    },

    getViewParams: function () {
        var params = this.base();

        if (this.startDate || this.endDate) {
            var dateAttr = this.$source.data('date-attr') || 'dateCreated';
            
            params.criteria[dateAttr] = ['and'];

            if (this.startDate) {
                params.criteria[dateAttr].push('>=' + this.startDate.getTime() / 1000);
            }

            if (this.endDate) {
                params.criteria[dateAttr].push('<' + (this.endDate.getTime() / 1000 + 86400));
            }
        }

        return params;
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
    afterInit() {
        this.$explorerContainer = $('<div class="chart-explorer-container"></div>').prependTo(this.$container);
        this.$chartExplorer = $('<div class="chart-explorer"></div>').appendTo(this.$explorerContainer);
        this.$chartContainer = $('<div class="chart-container"></div>').appendTo(this.$chartExplorer);
        this.$chart = $('<div class="chart"></div>').appendTo(this.$chartContainer);

        this.loadReport();
        this.base();
    },

    groupAndFillData(origin) {
        // Convert object into arrays
        const dataArray = Object.entries(origin);
        
        // Calculate the number of days between the first and last value
        const lastDate = new Date(dataArray[0][0]);
        const firstDate = new Date(dataArray[dataArray.length - 1][0]);
        const daysDifference = (lastDate - firstDate) / (1000 * 60 * 60 * 24);

        // Determine grouping based on the number of days
        let grouping;

        if (daysDifference >= 730) {
            grouping = 'year';
        } else if (daysDifference >= 60) {
            grouping = 'month';
        } else if (daysDifference >= 2) {
            grouping = 'day';
        } else {
            grouping = 'hour';
        }

        // Helper function to format dates based on grouping
        const formatDate = (date) => {
            // Clone the date so we don't mess things up on the original date
            var newDate = new Date(date.getTime());

            // Reset the month/day depending on grouping
            if (grouping === 'year') {
                newDate.setMonth(0);
                newDate.setDate(1);
                newDate.setHours(0);
                newDate.setMinutes(0);
                newDate.setSeconds(0);
            } else if (grouping === 'month') {
                newDate.setDate(1);
                newDate.setHours(0);
                newDate.setMinutes(0);
                newDate.setSeconds(0);
            } else if (grouping === 'day') {
                newDate.setHours(0);
                newDate.setMinutes(0);
                newDate.setSeconds(0);
            } else if (grouping === 'hour') {
                newDate.setMinutes(0);
                newDate.setSeconds(0);
            }

            if (grouping === 'hour') {
                return newDate.toISOString().slice(0, 19).replace('T', ' ');
            }

            // Return a date string
            return newDate.toISOString().split('T')[0];
        };

        // Create an array with no-gaps in values, according to our grouping
        const results = {};

        let currentDate = new Date(firstDate);
        
        // Just in case there's only one value, the chartJS will complain.
        while (currentDate <= lastDate || Object.keys(results).length < 2) {
            const formattedDate = formatDate(currentDate);
            
            results[formattedDate] = 0;
            
            if (grouping === 'year') {
                currentDate.setFullYear(currentDate.getFullYear() + 1);
            } else if (grouping === 'month') {
                currentDate.setMonth(currentDate.getMonth() + 1);
            } else if (grouping === 'day') {
                currentDate.setDate(currentDate.getDate() + 1);
            } else {
                currentDate.setHours(currentDate.getHours() + 1);
            }
        }

        // Now, populate each item in our grouped array, now it's been prepped
        for (const [dateStr, value] of dataArray) {
            var key = formatDate(new Date(dateStr));

            if (key in results) {
                results[key] += value;
            }
        }

        // Change from object to array
        return {
            data: Object.entries(results).map(([date, value]) => [date, value]),
            group: grouping,
        };
    },

    loadReport() {
        const $elements = $(this.elementIndex.$elements).find('[data-titlecell] .element');

        if (!$elements.length) {
            this.$explorerContainer.addClass('chart-empty');
            return;
        }

        if (!this.chart) {
            this.chart = new Craft.charts.Area(this.$chart);
        }

        let data = {};

        // Get the data for elements (just for this page) assuming we'll group by day
        $elements.each(function(index, item) {
            let dateCreated = $(item).data('date-created');

            if (!data[dateCreated]) {
                data[dateCreated] = 0;
            }

            data[dateCreated]++;
        });

        const chartData = this.groupAndFillData(data);
        const dateType = chartData.group === 'hour' ? 'datetime' : 'date';

        var dataTable = {
            columns: [
                { type: dateType, label: 'Date' },
                { type: 'number', label: 'Submissions' },
            ],
            rows: chartData.data,
        };

        var chartDataTable = new Craft.charts.DataTable(dataTable);

        var chartSettings = {
            orientation: Craft.orientation,
            formats: {
                numberFormat: ',.0f',
            },
            dataScale: chartData.group,
        };

        this.chart.draw(chartDataTable, chartSettings);
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
        this.$cancelBtn = $('<button type="button" class="btn">' + Craft.t('formie', 'Cancel') + '</button>').appendTo($mainBtnGroup);
        this.$updateBtn = $('<button type="submit" class="btn submit">' + Craft.t('formie', 'Send Email Notification') + '</button>').appendTo($mainBtnGroup);
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
