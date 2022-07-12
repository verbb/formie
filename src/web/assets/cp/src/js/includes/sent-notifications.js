if (typeof Craft.Formie === typeof undefined) {
    Craft.Formie = {};
}

(function($) {
    $(document).on('click', '.js-fui-notification-modal-resend-btn', function(e) {
        e.preventDefault();

        new Craft.Formie.ResendNotificationModal($(this).data('id'));
    });
})(jQuery);

Craft.Formie.ResendNotificationModal = Garnish.Modal.extend({
    init(id) {
        this.$form = $('<form class="modal fui-resend-modal" method="post" accept-charset="UTF-8"/>').appendTo(Garnish.$bod);
        this.$body = $('<div class="body"><div class="spinner big"></div></div>').appendTo(this.$form);

        var $footer = $('<div class="footer"/>').appendTo(this.$form);
        var $mainBtnGroup = $('<div class="buttons right"/>').appendTo($footer);
        this.$cancelBtn = $('<input type="button" class="btn" value="' + Craft.t('formie', 'Cancel') + '"/>').appendTo($mainBtnGroup);
        this.$updateBtn = $('<input type="button" class="btn submit" value="' + Craft.t('formie', 'Resend Email Notification') + '"/>').appendTo($mainBtnGroup);
        this.$footerSpinner = $('<div class="spinner right hidden"/>').appendTo($footer);

        Craft.initUiElements(this.$form);

        this.addListener(this.$cancelBtn, 'click', 'onFadeOut');
        this.addListener(this.$updateBtn, 'click', 'onResend');

        this.base(this.$form);

        var data = { id };

        Craft.sendActionRequest('POST', 'formie/sent-notifications/get-resend-modal-content', { data })
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

    onResend(e) {
        e.preventDefault();

        this.$footerSpinner.removeClass('hidden');

        var data = this.$form.serialize();

        // Save everything through the normal update-cart action, just like we were doing it on the front-end
        Craft.sendActionRequest('POST', 'formie/sent-notifications/resend', { data })
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

Craft.Formie.BulkResendElementAction = Garnish.Base.extend({
    init(type) {
        var resizeTrigger = new Craft.ElementActionTrigger({
            type,
            batch: true,
            activate($selectedItems) {
                new Craft.Formie.BulkResendModal($selectedItems.find('.element'), $selectedItems);
            },
        });
    },
});

Craft.Formie.BulkResendModal = Garnish.Modal.extend({
    init($element, $selectedItems) {
        this.$element = $element;
        this.$selectedItems = $selectedItems;

        var plural = ($selectedItems.length == 1) ? '' : 's';
        var actionDescription = '<strong>' + $selectedItems.length + '</strong> notification' + plural;

        this.$form = $('<form class="modal fitted" method="post" accept-charset="UTF-8"/>').appendTo(Garnish.$bod);

        this.$body = $('<div class="body" style="max-width: 560px;">' + 
            '<h2>' + Craft.t('formie', 'Bulk Resend Email Notification') + '</h2>' +
            '<p>' + Craft.t('formie', 'You are about to resend {desc}. You can resend each notification to their original recipients, or choose specific recipients.', { desc: actionDescription }) + '</p>' +
        '</div>').appendTo(this.$form);

        var $select = Craft.ui.createSelectField({
            label: Craft.t('formie', 'Recipients'),
            name: 'recipientsType',
            options: [
                { label: Craft.t('formie', 'Original Recipients'), value: 'original' },
                { label: Craft.t('formie', 'Custom Recipients'), value: 'custom' },
            ],
            toggle: true,
            targetPrefix: 'type-',
        }).appendTo(this.$body);

        var $customContainer = $('<div/>', {
            id: 'type-custom',
            'class': 'hidden',
        }).appendTo(this.$body);

        Craft.ui.createTextField({
            label: Craft.t('formie', 'Custom Recipients'),
            instructions: Craft.t('formie', 'Provide recipients for each email notification to be sent to. For multiple recipients, separate each with a comma.'),
            name: 'to',
            required: true,
        }).appendTo($customContainer);

        this.$selectedItems.each((index, element) => {
            $('<input/>', {
                type: 'hidden',
                name: 'ids[]',
                value: $(element).data('id'),
            }).appendTo(this.$body);
        });

        var $footer = $('<div class="footer"/>').appendTo(this.$form);
        var $mainBtnGroup = $('<div class="buttons right"/>').appendTo($footer);
        this.$cancelBtn = $('<input type="button" class="btn" value="' + Craft.t('formie', 'Cancel') + '"/>').appendTo($mainBtnGroup);
        this.$updateBtn = $('<input type="button" class="btn submit" value="' + Craft.t('formie', 'Resend Email Notifications') + '"/>').appendTo($mainBtnGroup);
        this.$footerSpinner = $('<div class="spinner right hidden"/>').appendTo($footer);

        this.addListener(this.$cancelBtn, 'click', 'onFadeOut');
        this.addListener(this.$updateBtn, 'click', 'onResend');
        this.addListener($select, 'change', 'onSelectChange');

        this.base(this.$form);
    },

    onSelectChange() {
        this.updateSizeAndPosition();
    },

    onFadeOut() {
        this.$form.remove();
        this.$shade.remove();
    },

    onResend(e) {
        e.preventDefault();
        
        this.$footerSpinner.removeClass('hidden');

        var data = this.$form.serialize();

        // Save everything through the normal update-cart action, just like we were doing it on the front-end
        Craft.sendActionRequest('POST', 'formie/sent-notifications/bulk-resend', { data })
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
