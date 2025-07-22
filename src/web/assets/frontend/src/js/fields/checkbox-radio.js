import { t, eventKey } from '../utils/utils';

export class FormieCheckboxRadio {
    constructor(settings = {}) {
        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;

        if (this.$field) {
            this.initInputs();
            this.initRequiredCheckboxes();
            this.initToggleCheckboxes();
        }
    }

    initInputs() {
        const $inputs = this.$field.querySelectorAll('[type=checkbox], [type=radio]');
        const isMaxGroup = this.$field.hasAttribute('data-max-options');

        $inputs.forEach(($input) => {
            this.form.addEventListener($input, eventKey('click'), (e) => {
                const isCheckbox = e.target.type === 'checkbox';

                // Handle radio logic
                if (e.target.checked && e.target.type === 'radio') {
                    const inputName = e.target.getAttribute('name');
                    const $radioButtons = this.$field.querySelectorAll(`[name="${inputName}"]`);
                    $radioButtons.forEach(($radioButton) => {
                        $radioButton.removeAttribute('checked');
                    });
                }

                // Toggle `checked` attribute manually
                if (e.target.checked) {
                    e.target.setAttribute('checked', '');
                } else {
                    e.target.removeAttribute('checked');
                }

                // If max option group, handle disabling
                if (isCheckbox && isMaxGroup) {
                    this.enforceMaxOptions();
                }
            }, false);
        });

        // Trigger once on load in case of pre-filled values
        if (isMaxGroup) {
            this.enforceMaxOptions();
        }
    }

    initRequiredCheckboxes() {
        const $checkboxInputs = this.$field.querySelectorAll('[type="checkbox"][required]');

        $checkboxInputs.forEach(($checkboxInput) => {
            this.form.addEventListener($checkboxInput, eventKey('change'), (e) => {
                this.onCheckboxChanged($checkboxInputs, this.isChecked($checkboxInputs));
            }, false);

            // For any checked fields, trigger this event now
            if ($checkboxInput.checked) {
                $checkboxInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    }

    initToggleCheckboxes() {
        const $checkboxInputs = this.$field.querySelectorAll('[type="checkbox"]');
        const $checkboxToggles = this.$field.querySelectorAll('[type="checkbox"][data-checkbox-toggle]');

        $checkboxToggles.forEach(($checkboxToggle) => {
            this.form.addEventListener($checkboxToggle, eventKey('change'), (e) => {
                const isChecked = e.target.checked;

                // Toggle all checkboxes in this field
                $checkboxInputs.forEach(($checkboxInput) => {
                    if ($checkboxInput !== e.target) {
                        $checkboxInput.checked = isChecked;
                    }
                });
            }, false);
        });
    }

    isChecked($checkboxInputs) {
        for (let i = 0; i < $checkboxInputs.length; i++) {
            if ($checkboxInputs[i].checked) {
                return true;
            }
        }

        return false;
    }

    onCheckboxChanged($checkboxInputs, checked) {
        $checkboxInputs.forEach(($checkboxInput) => {
            if (checked) {
                $checkboxInput.removeAttribute('required');
                $checkboxInput.setAttribute('aria-required', false);
            } else {
                $checkboxInput.setAttribute('required', true);
                $checkboxInput.setAttribute('aria-required', true);
            }
        });
    }

    enforceMaxOptions() {
        const $checkboxes = this.$field.querySelectorAll('[type="checkbox"]:not([data-checkbox-toggle])');
        const max = parseInt(this.$field.getAttribute('data-max-options'), 10);
        const checked = Array.from($checkboxes).filter(($cb) => { return $cb.checked; });

        const disableRest = checked.length >= max;

        $checkboxes.forEach(($cb) => {
            if (!$cb.checked) {
                $cb.disabled = disableRest;
            } else {
                $cb.disabled = false; // always keep checked ones enabled
            }
        });
    }
}

window.FormieCheckboxRadio = FormieCheckboxRadio;
