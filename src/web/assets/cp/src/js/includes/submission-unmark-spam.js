if (typeof Craft.Formie === typeof undefined) {
    Craft.Formie = {};
}

Craft.Formie.UnmarkSpamUserModal = Garnish.Modal.extend({
    $saveSubmitBtn: null,

    init: function(settings) {
        this.id = Math.floor(Math.random() * 1000000000);
        settings = $.extend(Craft.Formie.UnmarkSpamUserModal, settings);

        let $form = $(
            '<form class="modal fitted formie-unmark-spam-modal" method="post" accept-charset="UTF-8">' +
            Craft.getCsrfInput() +
            '</form>'
        ).appendTo(Garnish.$bod);

        let $body = $(
            '<div class="body">' +
            '<div class="content-summary">' +
            '<p>' + Craft.t('formie', 'Should any additional actions be performed?') + '</p>' +
            '</div>' +
            '</div>'
        ).appendTo($form);

        Craft.ui.createLightswitchField({
            label: Craft.t('formie', 'Send Notifications'),
            instructions: Craft.t('formie', 'Whether any Email Notifications should be sent.'),
            name: 'sendNotifications',
        }).appendTo($body);

        Craft.ui.createLightswitchField({
            label: Craft.t('formie', 'Trigger Integrations'),
            instructions: Craft.t('formie', 'Whether any Integrations should be triggered.'),
            name: 'triggerIntegrations',
        }).appendTo($body);

        let $buttons = $('<div class="buttons right"/>').appendTo($body);
        let $cancelBtn = $('<button/>', {
            type: 'button',
            class: 'btn',
            text: Craft.t('app', 'Cancel'),
        }).appendTo($buttons);

        this.$saveSubmitBtn = Craft.ui.createSubmitButton({
            label: Craft.t('formie', 'Unmark as Spam'),
            spinner: true,
        }).appendTo($buttons);

        // var idParam;

        // if (Array.isArray(this.userId)) {
        //   idParam = ['and'];

        //   for (let i = 0; i < this.userId.length; i++) {
        //     idParam.push('not ' + this.userId[i]);
        //   }
        // } else {
        //   idParam = 'not ' + this.userId;
        // }

        // this.userSelect = new Craft.BaseElementSelectInput({
        //   id: 'transferselect' + this.id,
        //   name: 'transferContentTo',
        //   elementType: 'craft\\elements\\User',
        //   criteria: {
        //     id: idParam,
        //   },
        //   limit: 1,
        //   modalSettings: {
        //     closeOtherModals: false,
        //   },
        //   onSelectElements: () => {
        //     this.updateSizeAndPosition();

        //     if (!this.$deleteActionRadios.first().prop('checked')) {
        //       this.$deleteActionRadios.first().trigger('click');
        //     } else {
        //       this.validateDeleteInputs();
        //     }
        //   },
        //   onRemoveElements: this.validateDeleteInputs.bind(this),
        //   selectable: false,
        //   editable: false,
        // });

        this.addListener($cancelBtn, 'click', 'hide');
        this.addListener($form, 'submit', 'handleSubmit');

        this.base($form, settings);
    },

    handleSubmit: function (ev) {
        this.$saveSubmitBtn.addClass('loading');
        this.disable();

        // Let the onSubmit callback prevent the form from getting submitted
        try {
            if (this.settings.onSubmit() === false) {
                ev.preventDefault();
            }
        } catch (e) {
            ev.preventDefault();
            this.$saveSubmitBtn.removeClass('loading');
            throw e;
        }
    },
},
{
    defaults: {
        // contentSummary: [],
        onSubmit: $.noop,
    },
});
