import { eventKey } from '../utils/utils';

import SignaturePad from 'signature_pad';

export class FormieSignature {
    constructor(settings = {}) {
        this.$form = settings.$form;
        this.form = this.$form.form;
        this.$field = settings.$field;
        this.$input = this.$field.querySelector('input');
        this.$canvas = this.$field.querySelector('canvas');
        this.$clearBtn = this.$field.querySelector('[data-signature-clear]');

        this.backgroundColor = settings.backgroundColor;
        this.penColor = settings.penColor;
        this.penWeight = settings.penWeight;

        if (this.$canvas) {
            this.initPad();
        } else {
            console.error('Unable to find canvas.');
        }
    }

    initPad() {
        this.signaturePad = new SignaturePad(this.$canvas, {
            backgroundColor: this.backgroundColor,
            penColor: this.penColor,
            dotSize: this.penWeight,
            minWidth: this.penWeight,
            maxWidth: this.penWeight,
        });

        this.signaturePad.addEventListener('endStroke', (e) => {
            // Save the data-url image for the server
            this.$input.value = this.signaturePad.toDataURL();
        });

        // Clear the canvas
        if (this.$clearBtn) {
            this.$clearBtn.addEventListener('click', () => {
                this.signaturePad.clear();
                this.$input.value = '';
            });
        }

        // If the hidden input already has a value, we should use that. Normally captured
        // during validation errors, or when editing an existing submission.
        if (this.$input.value) {
            const $img = document.createElement('img');
            $img.src = this.$input.value;

            this.signaturePad.clear();

            $img.onload = () => {
                // Handle retina devices
                const ratio = Math.max(window.devicePixelRatio || 1, 1);

                this.$canvas.getContext('2d').drawImage($img, 0, 0, this.$canvas.width / ratio, this.$canvas.height / ratio);
            };
        }

        // Handle retina devices to properly scale things
        window.addEventListener('resize', this.resizeCanvas);
        this.resizeCanvas();

        // For ajax forms, we want to refresh the field when the page is toggled
        // for clicking on tabs, or for going to the next page. Canvas size will be tiny
        // if hidden (the case for multi-page forms with this field on a later page).
        if (this.form.settings.submitMethod === 'ajax') {
            this.form.addEventListener(this.$form, 'onFormiePageToggle', () => {
                // Supply a little delay so the DOM is ready
                setTimeout(() => {
                    this.resizeCanvas();
                }, 100);
            });
        }
    }

    resizeCanvas() {
        if (this.$canvas) {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);

            this.$canvas.width = this.$canvas.offsetWidth * ratio;
            this.$canvas.height = this.$canvas.offsetHeight * ratio;
            this.$canvas.getContext('2d').scale(ratio, ratio);
            this.signaturePad.clear();
        }
    }
}

window.FormieSignature = FormieSignature;
