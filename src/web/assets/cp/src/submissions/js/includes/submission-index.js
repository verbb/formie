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
        const $toolbar = $container.find('#toolbar:first');

        Craft.ui.createDateRangePicker({
            onChange: function(startDate, endDate) {
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
        const { editableForms } = Craft.Formie;

        if (editableForms) {
            for (let i = 0; i < editableForms.length; i++) {
                const form = editableForms[i];

                if (this.getSourceByKey(`form:${form.id}`)) {
                    this.editableForms.push(form);
                }
            }
        }

        this.base();
    },

    setupStateButton() {
        const $btn = $('<button/>', {
            type: 'button',
            class: 'btn menubtn statusmenubtn',
        }).append(
            $('<span/>', {
                class: 'status disabled',
            }),
            $('<span/>', {
                text: Craft.t('formie', 'All'),
            }),
        );

        const $menu = $('<div/>', { class: 'menu' }).append(
            $('<ul/>', { class: 'padded' }).append(
                $('<li/>').append(
                    $('<a/>', { 'data-state': 'all' }).append(
                        $('<span/>', { class: 'status disabled' }),
                        $('<span/>', { text: Craft.t('formie', 'All') }),
                    ),
                ),
                $('<li/>').append(
                    $('<a/>', { 'data-state': 'complete' }).append(
                        $('<span/>', { class: 'icon', 'data-icon': 'check' }),
                        $('<span/>', { text: Craft.t('formie', 'Complete') }),
                    ),
                ),
                $('<li/>').append(
                    $('<a/>', { 'data-state': 'incomplete' }).append(
                        $('<span/>', { class: 'icon', 'data-icon': 'draft' }),
                        $('<span/>', { text: Craft.t('formie', 'Incomplete') }),
                    ),
                ),
                $('<li/>').append(
                    $('<a/>', { 'data-state': 'spam' }).append(
                        $('<span/>', { class: 'icon', 'data-icon': 'bug' }),
                        $('<span/>', { text: Craft.t('formie', 'Spam') }),
                    ),
                ),
            ),
        );

        const self = this;

        var menu = new Garnish.Menu($menu, {
            onOptionSelect(option) {
                const $option = $(option);
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
        const $option = menu.$options.filter(`[data-state=${currentState}]`);

        if ($option.length) {
            menu.selectOption($option[0]);
        }
    },

    getViewClass(mode) {
        return this.base(mode);
    },

    getDefaultSort() {
        return ['dateCreated', 'desc'];
    },

    getDefaultSourceKey() {
        if (this.settings.context === 'index' && typeof defaultFormieFormHandle !== 'undefined') {
            for (let i = 0; i < this.$sources.length; i++) {
                const $source = $(this.$sources[i]);

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

        const handle = this.$source.data('handle');
        let i, href, label;

        if (this.editableForms.length) {
            // Remove the old button, if there is one
            if (this.$newSubmissionBtnGroup) {
                this.$newSubmissionBtnGroup.remove();
            }

            let selectedForm;

            if (handle) {
                for (i = 0; i < this.editableForms.length; i++) {
                    if (this.editableForms[i].handle === handle) {
                        selectedForm = this.editableForms[i];
                        break;
                    }
                }
            }

            this.$newSubmissionBtnGroup = $('<div class="btngroup submit"/>');
            let $menuBtn;

            if (selectedForm) {
                href = this._getFormTriggerHref(selectedForm);
                label = (this.settings.context === 'index' ? Craft.t('formie', 'New submission') : Craft.t('formie', 'New {form} submission', { form: selectedForm.name }));
                this.$newSubmissionBtn = $(`<a class="btn submit add icon" ${href} role="button" tabindex="0">${Craft.escapeHtml(label)}</a>`).appendTo(this.$newSubmissionBtnGroup);

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
                let menuHtml = '<div class="menu"><ul>';

                for (i = 0; i < this.editableForms.length; i++) {
                    const form = this.editableForms[i];

                    if ((this.settings.context === 'index' && $.inArray(this.siteId, form.sites) !== -1) || (this.settings.context !== 'index' && form !== selectedForm)) {
                        href = this._getFormTriggerHref(form);
                        label = (this.settings.context === 'index' ? form.name : Craft.t('formie', 'New {form} submission', { form: form.name }));
                        menuHtml += `<li><a ${href}>${Craft.escapeHtml(label)}</a></li>`;
                    }
                }

                menuHtml += '</ul></div>';

                $(menuHtml).appendTo(this.$newSubmissionBtnGroup);
                const menuBtn = new Garnish.MenuBtn($menuBtn);

                if (this.settings.context !== 'index') {
                    menuBtn.on('optionSelect', (ev) => {
                        this._openCreateSubmissionModal(ev.option.getAttribute('data-id'));
                    });
                }
            }

            this.addButton(this.$newSubmissionBtnGroup);
        }

        if (this.settings.context === 'index') {
            let uri = 'formie/submissions';

            if (handle) {
                uri += `/${handle}`;
            }

            Craft.setPath(uri);
        }
    },

    getViewParams() {
        const params = this.base();

        if (this.startDate || this.endDate) {
            const dateAttr = this.$source.data('date-attr') || 'dateCreated';

            params.criteria[dateAttr] = ['and'];

            if (this.startDate) {
                params.criteria[dateAttr].push(`>=${this.startDate.getTime() / 1000}`);
            }

            if (this.endDate) {
                params.criteria[dateAttr].push(`<${this.endDate.getTime() / 1000 + 86400}`);
            }
        }

        return params;
    },

    getSite() {
        if (!this.siteId) {
            return undefined;
        }
        return Craft.sites.find((s) => { return s.id == this.siteId; });
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

        let form;

        for (let i = 0; i < this.editableForms.length; i++) {
            if (this.editableForms[i].id == formId) {
                form = this.editableForms[i];
                break;
            }
        }

        if (!form) {
            return;
        }

        this.$newSubmissionBtn.addClass('inactive');
        const newSubmissionBtnText = this.$newSubmissionBtn.text();
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
            onSaveElement: (response) => {
                const formSourceKey = `form:${form.id}`;

                if (this.sourceKey !== formSourceKey) {
                    this.selectSourceByKey(formSourceKey);
                }

                this.selectElementAfterUpdate(response.id);
                this.updateElements();
            },
        });
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

        const $footer = $('<div class="footer"/>').appendTo(this.$form);
        const $mainBtnGroup = $('<div class="buttons right"/>').appendTo($footer);
        this.$cancelBtn = $(`<button type="button" class="btn">${Craft.t('formie', 'Cancel')}</button>`).appendTo($mainBtnGroup);
        this.$updateBtn = $(`<button type="submit" class="btn submit">${Craft.t('formie', 'Send Email Notification')}</button>`).appendTo($mainBtnGroup);
        this.$footerSpinner = $('<div class="spinner right hidden"/>').appendTo($footer);

        Craft.initUiElements(this.$form);

        this.addListener(this.$cancelBtn, 'click', 'onFadeOut');
        this.addListener(this.$updateBtn, 'click', 'onSend');

        this.base(this.$form);

        const data = { id };

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

        const data = this.$form.serialize();

        // Save everything through the normal update-cart action, just like we were doing it on the front-end
        Craft.sendActionRequest('POST', 'formie/submissions/send-notification', { data })
            .then((response) => {
                location.reload();
            })
            .catch(({ response }) => {
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
