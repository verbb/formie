import { createElement } from 'react';
import { AppErrorBoundary } from '@verbb/plugin-kit-react/utils';

import { bootstrapShadowReactApp, mountFormieReactApp } from '@utils';
import { SendNotificationDialog } from '../../SendNotificationDialog.jsx';

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

let activeSendNotificationDialogCleanup = null;

Craft.Formie.SendNotificationModal = function SendNotificationModal(id, trigger = null) {
    activeSendNotificationDialogCleanup?.();

    const container = document.createElement('div');
    container.id = `formie-send-notification-${crypto.randomUUID()}`;
    document.body.append(container);

    const boot = bootstrapShadowReactApp({
        containerSelector: `#${container.id}`,
        pluginHandle: 'formie',
        styleNamespace: 'submissions',
    });

    let app = null;
    let closed = false;
    const cleanup = () => {
        if (closed) {
            return;
        }

        closed = true;
        app?.unmount();
        container.remove();
        trigger?.focus();

        if (activeSendNotificationDialogCleanup === cleanup) {
            activeSendNotificationDialogCleanup = null;
        }
    };

    app = mountFormieReactApp({
        ...boot,
        children: createElement(AppErrorBoundary, {
            consoleLabel: 'Formie send notification dialog crashed:',
            heading: Craft.t('formie', 'Something went wrong'),
            message: Craft.t('formie', 'The send notification dialog could not be opened. Please refresh the page and try again.'),
            detailsLabel: Craft.t('formie', 'Show error details'),
            reloadLabel: Craft.t('formie', 'Reload'),
        }, createElement(SendNotificationDialog, {
            submissionId: String(id),
            onClose: cleanup,
        })),
    });
    activeSendNotificationDialogCleanup = cleanup;
};

document.addEventListener('click', (event) => {
    const trigger = event.target.closest?.('.js-fui-submission-modal-send-btn');

    if (!trigger) {
        return;
    }

    event.preventDefault();
    new Craft.Formie.SendNotificationModal(trigger.dataset.id, trigger);
});

Craft.registerElementIndexClass('verbb\\formie\\elements\\Submission', Craft.Formie.SubmissionIndex);
