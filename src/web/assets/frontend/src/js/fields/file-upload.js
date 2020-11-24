import { eventKey } from '../utils/utils';

export class FormieFileUpload {
    constructor(settings = {}) {
        this.formId = '#formie-form-' + settings.formId;
        this.$form = document.querySelector(this.formId);

        if (this.$form) {
            this.form = this.$form.form;

            this.form.addEventListener(this.$form, eventKey('registerFormieValidation'), this.registerValidation.bind(this));
        } else {
            console.error('Unable to find ' + this.formId);
        }
    }

    registerValidation(e) {
        // Add our custom validations logic and methods
        e.detail.validatorSettings.customValidations = {
            ...e.detail.validatorSettings.customValidations,
            ...this.getFileSizeLimitRule(),
            ...this.getFileLimitRule(),
        };

        // Add our custom messages
        e.detail.validatorSettings.messages = {
            ...e.detail.validatorSettings.messages,
            ...this.getFileSizeLimitMessage(),
            ...this.getFileLimitMessage(),
        };
    }

    getFileSizeLimitRule() {
        return {
            fileSizeLimit(field) {
                const type = field.getAttribute('type');
                const sizeLimit = field.getAttribute('data-size-limit');
                const sizeBytes = parseFloat(sizeLimit) * 1024 * 1024;

                if (type !== 'file' || !sizeBytes) {
                    return;
                }

                for (const file of field.files) {
                    if (file.size > sizeBytes) {
                        return true;
                    }
                }
            },
        };
    }

    getFileSizeLimitMessage() {
        return {
            fileSizeLimit(field) {
                return t('File must be smaller than {filesize} MB.', {
                    filesize: field.getAttribute('data-size-limit'),
                });
            },
        };
    }

    getFileLimitRule() {
        return {
            fileLimit(field) {
                const type = field.getAttribute('type');
                const fileLimit = parseInt(field.getAttribute('data-file-limit'));

                if (type !== 'file' || !fileLimit) {
                    return;
                }

                if (field.files.length > fileLimit) {
                    return true;
                }
            },
        };
    }

    getFileLimitMessage() {
        return {
            fileLimit(field) {
                return t('Choose up to {files} files.', {
                    files: field.getAttribute('data-file-limit'),
                });
            },
        };
    }
}

window.FormieFileUpload = FormieFileUpload;
