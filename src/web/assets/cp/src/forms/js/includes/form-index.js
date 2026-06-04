/** global: Craft */
/** global: Garnish */

if (typeof Craft.Formie === 'undefined') {
    Craft.Formie = {};
}

Craft.Formie.FormIndex = Craft.BaseElementIndex.extend({
    editableGroups: null,
    $newFormBtnGroup: null,
    $newFormBtn: null,

    init(elementType, $container, settings) {
        this.editableGroups = [];
        this.on('selectSource', this.updateButton.bind(this));

        this.base(elementType, $container, settings);
    },

    afterInit() {
        const { editableFormGroups } = Craft.Formie;

        if (editableFormGroups) {
            for (const group of editableFormGroups) {
                if (this.getSourceByKey(`group:${group.uid}`)) {
                    this.editableGroups.push(group);
                }
            }
        }

        this.base();
    },

    updateButton() {
        if (!this.$source || !Craft.Formie.canCreateForms) {
            return;
        }

        if (this.$newFormBtnGroup) {
            this.$newFormBtnGroup.remove();
        }

        const selectedSourceHandle = this.$source.data('handle');
        const selectedGroup = this.editableGroups.find(
            (group) => group.handle === selectedSourceHandle,
        );

        this.$newFormBtnGroup = $('<div class="btngroup submit" data-wrapper/>');
        let $menuBtn;
        const menuId = `new-form-menu-${Craft.randomString(10)}`;

        if (this.editableGroups.length) {
            if (selectedGroup) {
                const visibleLabel = Craft.uppercaseFirst(
                    Craft.t('app', 'New {type}', {
                        type: Craft.elementTypeNames['verbb\\formie\\elements\\Form'][2],
                    }),
                );
                const ariaLabel = Craft.t('formie', 'New form in the {group} group', {
                    group: selectedGroup.name,
                });

                this.$newFormBtn = Craft.ui
                    .createButton({
                        label: visibleLabel,
                        ariaLabel,
                        role: 'link',
                    })
                    .addClass('submit add icon')
                    .appendTo(this.$newFormBtnGroup);

                this.addListener(this.$newFormBtn, 'click mousedown', (ev) => {
                    if (
                        (ev.type === 'click' && Garnish.isCtrlKeyPressed(ev)) ||
                        (ev.type === 'mousedown' && ev.originalEvent.button === 1)
                    ) {
                        window.open(this._getNewFormUrl(selectedGroup));
                    } else if (ev.type === 'click') {
                        this._createForm(selectedGroup);
                    }
                });

                if (this.editableGroups.length > 1) {
                    $menuBtn = $('<button/>', {
                        type: 'button',
                        class: 'btn submit menubtn btngroup-btn-last',
                        'aria-controls': menuId,
                        'data-disclosure-trigger': '',
                        'aria-label': Craft.t('formie', 'New form, choose a group'),
                    }).appendTo(this.$newFormBtnGroup);
                }
            } else {
                this.$newFormBtn = $menuBtn = Craft.ui
                    .createButton({
                        label: Craft.uppercaseFirst(
                            Craft.t('app', 'New {type}', {
                                type: Craft.elementTypeNames['verbb\\formie\\elements\\Form'][2],
                            }),
                        ),
                        ariaLabel: Craft.t('formie', 'New form, choose a group'),
                    })
                    .addClass('submit add icon menubtn btngroup-btn-last')
                    .attr('aria-controls', menuId)
                    .attr('data-disclosure-trigger', '')
                    .appendTo(this.$newFormBtnGroup);
            }

            this.addButton(this.$newFormBtnGroup);

            if ($menuBtn) {
                const $menuContainer = $('<div/>', {
                    id: menuId,
                    class: 'menu menu--disclosure',
                }).appendTo(this.$newFormBtnGroup);
                const $ul = $('<ul/>').appendTo($menuContainer);

                if (!selectedGroup) {
                    const $ungroupedLi = $('<li/>').appendTo($ul);
                    const $ungroupedLink = $('<a/>', {
                        role: 'link',
                        href: '#',
                        text: Craft.t('formie', 'New Form'),
                    }).appendTo($ungroupedLi);

                    this.addListener($ungroupedLink, 'click', (ev) => {
                        ev.preventDefault();
                        $menuBtn.data('trigger').hide();
                        this._createForm();
                    });
                }

                for (const group of this.editableGroups) {
                    if (group === selectedGroup) {
                        continue;
                    }

                    const $li = $('<li/>').appendTo($ul);
                    const $a = $('<a/>', {
                        role: 'link',
                        href: '#',
                        text: Craft.t('formie', 'New {group} form', {
                            group: group.name,
                        }),
                    }).appendTo($li);

                    this.addListener($a, 'click', (ev) => {
                        ev.preventDefault();
                        $menuBtn.data('trigger').hide();
                        this._createForm(group);
                    });
                }

                new Garnish.DisclosureMenu($menuBtn);
            }

            return;
        }

        this.$newFormBtn = Craft.ui
            .createButton({
                label: Craft.t('formie', 'New Form'),
                ariaLabel: Craft.t('formie', 'New Form'),
                role: 'link',
            })
            .addClass('submit add icon')
            .appendTo(this.$newFormBtnGroup);

        this.addListener(this.$newFormBtn, 'click mousedown', (ev) => {
            if (
                (ev.type === 'click' && Garnish.isCtrlKeyPressed(ev)) ||
                (ev.type === 'mousedown' && ev.originalEvent.button === 1)
            ) {
                window.open(this._getNewFormUrl());
            } else if (ev.type === 'click') {
                this._createForm();
            }
        });

        this.addButton(this.$newFormBtnGroup);
    },

    _getNewFormUrl(group = null) {
        const params = {};

        if (group?.uid) {
            params.source = `group:${group.uid}`;
        }

        return Craft.getUrl('formie/forms/new', params);
    },

    _createForm(group = null) {
        document.location.href = this._getNewFormUrl(group);
    },
});

Craft.registerElementIndexClass(
    'verbb\\formie\\elements\\Form',
    Craft.Formie.FormIndex,
);
